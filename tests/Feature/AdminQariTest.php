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
