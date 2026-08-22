<?php

use App\Livewire\Admin\CollectionManager;
use App\Models\Collection;
use App\Models\Tilawa;
use App\Models\User;
use App\Services\AutoCollectionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    $this->admin = User::factory()->create()->assignRole('admin');
});

it('renders the collections admin page for admins', function () {
    $collection = Collection::factory()->create(['title_ar' => 'مختارات رمضان']);

    $this->actingAs($this->admin)
        ->get(route('admin.collections.index'))
        ->assertOk()
        ->assertSee($collection->title_ar);
});

it('keeps non-admins out of the collections admin page', function () {
    $creator = User::factory()->create()->assignRole('creator');

    $this->actingAs($creator)->get(route('admin.collections.index'))->assertForbidden();
});

it('creates a hand-picked collection with its recitations in order', function () {
    $first = Tilawa::factory()->approved()->create();
    $second = Tilawa::factory()->approved()->create();

    Livewire::actingAs($this->admin)
        ->test(CollectionManager::class)
        ->call('create')
        ->set('title_ar', 'تلاوات خاشعة')
        ->set('title_en', 'Soothing')
        ->call('addTilawa', $first->id)
        ->call('addTilawa', $second->id)
        ->call('save')
        ->assertHasNoErrors();

    $collection = Collection::firstWhere('title_ar', 'تلاوات خاشعة');

    expect($collection->type)->toBe(Collection::TYPE_MANUAL)
        ->and($collection->slug)->toBe('soothing')
        ->and($collection->tilawat->pluck('id')->all())->toBe([$first->id, $second->id]);
});

it('reorders picked recitations before saving', function () {
    $first = Tilawa::factory()->approved()->create();
    $second = Tilawa::factory()->approved()->create();

    Livewire::actingAs($this->admin)
        ->test(CollectionManager::class)
        ->call('create')
        ->set('title_ar', 'ترتيب')
        ->call('addTilawa', $first->id)
        ->call('addTilawa', $second->id)
        ->call('moveUp', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect(Collection::firstWhere('title_ar', 'ترتيب')->tilawat->pluck('id')->all())
        ->toBe([$second->id, $first->id]);
});

it('creates an auto collection driven by a rule', function () {
    Livewire::actingAs($this->admin)
        ->test(CollectionManager::class)
        ->call('create')
        ->set('title_ar', 'الأكثر إعجابًا')
        ->set('type', Collection::TYPE_AUTO)
        ->set('auto_rule', AutoCollectionResolver::RULE_MOST_LIKED)
        ->set('auto_limit', 5)
        ->call('save')
        ->assertHasNoErrors();

    $collection = Collection::firstWhere('title_ar', 'الأكثر إعجابًا');

    expect($collection->isAuto())->toBeTrue()
        ->and($collection->auto_rule)->toBe(AutoCollectionResolver::RULE_MOST_LIKED)
        ->and($collection->auto_limit)->toBe(5);
});

it('rejects an unknown auto rule', function () {
    Livewire::actingAs($this->admin)
        ->test(CollectionManager::class)
        ->call('create')
        ->set('title_ar', 'قاعدة خاطئة')
        ->set('type', Collection::TYPE_AUTO)
        ->set('auto_rule', 'made_up_rule')
        ->call('save')
        ->assertHasErrors('auto_rule');

    expect(Collection::count())->toBe(0);
});

it('requires an arabic title', function () {
    Livewire::actingAs($this->admin)
        ->test(CollectionManager::class)
        ->call('create')
        ->call('save')
        ->assertHasErrors('title_ar');
});

it('drops the picked recitations when a collection switches to auto', function () {
    $tilawa = Tilawa::factory()->approved()->create();
    $collection = Collection::factory()->create();
    $collection->tilawat()->attach($tilawa->id, ['sort_order' => 0]);

    Livewire::actingAs($this->admin)
        ->test(CollectionManager::class)
        ->call('edit', $collection->id)
        ->set('type', Collection::TYPE_AUTO)
        ->set('auto_rule', AutoCollectionResolver::RULE_RECENT)
        ->call('save')
        ->assertHasNoErrors();

    expect($collection->fresh()->tilawat()->count())->toBe(0);
});

it('previews what an auto rule resolves to before saving', function () {
    $top = Tilawa::factory()->approved()->create(['plays_count' => 400]);

    Livewire::actingAs($this->admin)
        ->test(CollectionManager::class)
        ->call('create')
        ->set('type', Collection::TYPE_AUTO)
        ->set('auto_rule', AutoCollectionResolver::RULE_MOST_PLAYED)
        ->assertSee($top->title_ar);
});

it('toggles a collection between visible and hidden', function () {
    $collection = Collection::factory()->create(['is_active' => true]);

    Livewire::actingAs($this->admin)
        ->test(CollectionManager::class)
        ->call('toggleActive', $collection->id);

    expect($collection->fresh()->is_active)->toBeFalse();
});

it('deletes a collection only after the modal is confirmed', function () {
    $collection = Collection::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(CollectionManager::class)
        ->call('confirmDelete', $collection->id)
        ->assertSee(__('Confirm deletion'))
        ->call('performDelete');

    expect(Collection::find($collection->id))->toBeNull();
});

it('excludes already picked recitations from the search results', function () {
    $picked = Tilawa::factory()->approved()->create(['title_ar' => 'سورة الرحمن كاملة']);
    $other = Tilawa::factory()->approved()->create(['title_ar' => 'سورة الرحمن مجودة']);

    Livewire::actingAs($this->admin)
        ->test(CollectionManager::class)
        ->call('create')
        ->call('addTilawa', $picked->id)
        ->set('search', 'سورة الرحمن')
        ->assertSee($other->title_ar)
        ->assertSet('picked', [$picked->id])
        ->assertSeeHtml('wire:click="addTilawa('.$other->id.')"')
        ->assertDontSeeHtml('wire:click="addTilawa('.$picked->id.')"');
});
