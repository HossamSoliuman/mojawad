<?php

use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

function editAdmin(): User
{
    return User::factory()->create()->assignRole('admin');
}

it('renders the tilawa editor as a structured workspace', function () {
    Qari::factory()->create([
        'name_ar' => 'Searchable Reciter',
        'name_en' => 'Searchable Reciter',
    ]);

    $tilawa = Tilawa::factory()->approved()->create([
        'title_ar' => 'تلاوة للاختبار',
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
    ]);

    $this->actingAs(editAdmin())
        ->get(route('admin.tilawat.edit', $tilawa))
        ->assertOk()
        ->assertSee('class="tilawa-editor"', false)
        ->assertSee('class="te-layout"', false)
        ->assertSee('data-search-grid-select', false)
        ->assertSee('data-columns="2"', false)
        ->assertSee('data-columns="3"', false)
        ->assertSee('data-select-search', false)
        ->assertSee('name="surah_number"', false)
        ->assertSee('name="audio_tmp"', false)
        ->assertSee('Searchable Reciter')
        ->assertSee('تلاوة للاختبار');
});

it('persists the surah, ayah range and slug when an admin updates a tilawa', function () {
    $tilawa = Tilawa::factory()->approved()->create([
        'surah_number' => null,
        'ayah_from' => null,
        'ayah_to' => null,
    ]);

    $this->actingAs(editAdmin())
        ->put(route('admin.tilawat.update', $tilawa), [
            'qari_id' => $tilawa->qari_id,
            'title_ar' => 'سورة الفاتحة',
            'slug' => 'surah-al-fatiha-edited',
            'surah_number' => 1,
            'ayah_from' => 1,
            'ayah_to' => 7,
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.tilawat.index'));

    $tilawa->refresh();

    expect($tilawa->surah_number)->toBe(1)
        ->and($tilawa->ayah_from)->toBe(1)
        ->and($tilawa->ayah_to)->toBe(7)
        ->and($tilawa->slug)->toBe('surah-al-fatiha-edited');
});

it('rejects an ayah range where the end is before the start', function () {
    $tilawa = Tilawa::factory()->approved()->create();

    $this->actingAs(editAdmin())
        ->put(route('admin.tilawat.update', $tilawa), [
            'qari_id' => $tilawa->qari_id,
            'title_ar' => $tilawa->title_ar,
            'ayah_from' => 10,
            'ayah_to' => 3,
        ])
        ->assertSessionHasErrors('ayah_to');
});

it('rejects a slug already used by another tilawa', function () {
    Tilawa::factory()->create(['slug' => 'taken-slug']);
    $tilawa = Tilawa::factory()->approved()->create();

    $this->actingAs(editAdmin())
        ->put(route('admin.tilawat.update', $tilawa), [
            'qari_id' => $tilawa->qari_id,
            'title_ar' => $tilawa->title_ar,
            'slug' => 'taken-slug',
        ])
        ->assertSessionHasErrors('slug');

    expect($tilawa->fresh()->slug)->not->toBe('taken-slug');
});

it('keeps the current slug when the field is left empty', function () {
    $tilawa = Tilawa::factory()->approved()->create(['slug' => 'keep-this-slug']);

    $this->actingAs(editAdmin())
        ->put(route('admin.tilawat.update', $tilawa), [
            'qari_id' => $tilawa->qari_id,
            'title_ar' => $tilawa->title_ar,
            'slug' => '',
        ])
        ->assertRedirect(route('admin.tilawat.index'));

    expect($tilawa->fresh()->slug)->toBe('keep-this-slug');
});
