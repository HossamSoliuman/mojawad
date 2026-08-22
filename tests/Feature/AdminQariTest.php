<?php

use App\Models\Qari;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    $this->admin = User::factory()->create()->assignRole('admin');
});

it('renders the qari index as a card grid', function () {
    $qari = Qari::factory()->create(['name_ar' => 'الشيخ عبد الباسط']);

    $this->actingAs($this->admin)
        ->get(route('admin.qaris.index'))
        ->assertOk()
        ->assertSee('qari-cards', false)
        ->assertSee('qari-card-img', false)
        ->assertSee($qari->name_ar)
        ->assertDontSee('<table class="tbl">', false);
});

it('shows the enlarged avatar hero on the create form', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.qaris.create'))
        ->assertOk()
        ->assertSee('qari-avatar-hero', false)
        ->assertSee('qari-avatar-frame', false)
        ->assertSee('id="qari-avatar-preview"', false);
});

it('shows the current image in the enlarged avatar hero on the edit form', function () {
    $qari = Qari::factory()->create(['image' => 'qari-images/sheikh.jpg']);

    $this->actingAs($this->admin)
        ->get(route('admin.qaris.edit', $qari))
        ->assertOk()
        ->assertSee('qari-avatar-hero', false)
        ->assertSee($qari->image_url, false);
});

it('toggles the featured flag from the index card without wiping the name', function () {
    $qari = Qari::factory()->create([
        'name_ar' => 'اسم عربي',
        'name_en' => 'English Name',
        'is_featured' => false,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.qaris.update', $qari), [
            'name_ar' => $qari->name_ar,
            'name_en' => $qari->name_en,
            'biography_ar' => $qari->biography_ar,
            'biography_en' => $qari->biography_en,
            'status' => $qari->status,
            'is_featured' => '1',
        ])
        ->assertRedirect(route('admin.qaris.index'));

    $qari->refresh();

    expect($qari->is_featured)->toBeTrue()
        ->and($qari->name_ar)->toBe('اسم عربي')
        ->and($qari->name_en)->toBe('English Name');
});

it('renders a status toggle instead of a status dropdown on the index cards', function () {
    Qari::factory()->create(['status' => 'active']);
    Qari::factory()->create(['status' => 'inactive']);

    $this->actingAs($this->admin)
        ->get(route('admin.qaris.index'))
        ->assertOk()
        ->assertSee('status-toggle is-on', false)
        ->assertSee(__('Not Active'))
        ->assertDontSee('<select name="status" onchange="this.form.submit()"', false);
});

it('lists the qaris in their sort order', function () {
    Qari::factory()->create(['name_ar' => 'ثالث', 'sort_order' => 30]);
    Qari::factory()->create(['name_ar' => 'أول', 'sort_order' => 10]);
    Qari::factory()->create(['name_ar' => 'ثاني', 'sort_order' => 20]);

    $qaris = $this->actingAs($this->admin)
        ->get(route('admin.qaris.index'))
        ->assertOk()
        ->viewData('qaris');

    expect($qaris->pluck('name_ar')->all())->toBe(['أول', 'ثاني', 'ثالث']);
});

it('saves the sort order from the index card', function () {
    $qari = Qari::factory()->create(['sort_order' => 0]);

    $this->actingAs($this->admin)
        ->put(route('admin.qaris.update', $qari), [
            'name_ar' => $qari->name_ar,
            'name_en' => $qari->name_en,
            'biography_ar' => $qari->biography_ar,
            'biography_en' => $qari->biography_en,
            'status' => $qari->status,
            'is_featured' => '0',
            'sort_order' => '7',
        ])
        ->assertRedirect(route('admin.qaris.index'));

    expect($qari->refresh()->sort_order)->toBe(7);
});

it('keeps the sort order when a card form omits it', function () {
    $qari = Qari::factory()->create(['sort_order' => 5, 'is_featured' => false]);

    $this->actingAs($this->admin)
        ->put(route('admin.qaris.update', $qari), [
            'name_ar' => $qari->name_ar,
            'name_en' => $qari->name_en,
            'biography_ar' => $qari->biography_ar,
            'biography_en' => $qari->biography_en,
            'status' => $qari->status,
            'is_featured' => '1',
        ]);

    expect($qari->refresh()->sort_order)->toBe(5);
});

it('stores a new qari with its sort order', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.qaris.store'), [
            'name_ar' => 'قارئ جديد',
            'status' => 'active',
            'sort_order' => '3',
        ])
        ->assertRedirect(route('admin.qaris.index'));

    expect(Qari::where('name_ar', 'قارئ جديد')->first()->sort_order)->toBe(3);
});

it('changes the status from the index card', function () {
    $qari = Qari::factory()->create(['status' => 'active']);

    $this->actingAs($this->admin)
        ->put(route('admin.qaris.update', $qari), [
            'name_ar' => $qari->name_ar,
            'name_en' => $qari->name_en,
            'biography_ar' => $qari->biography_ar,
            'biography_en' => $qari->biography_en,
            'status' => 'inactive',
            'is_featured' => $qari->is_featured ? '1' : '0',
        ])
        ->assertRedirect(route('admin.qaris.index'));

    expect($qari->refresh()->status)->toBe('inactive');
});
