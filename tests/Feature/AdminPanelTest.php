<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

it('blocks admins without the creator role from the uploader', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.upload'))
        ->assertForbidden();
});

it('lets a creator open the uploader', function () {
    $creator = User::factory()->create()->assignRole('creator');

    $this->actingAs($creator)
        ->get(route('admin.upload'))
        ->assertOk();
});

it('shows the dashboard with reports data to an admin', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

it('shows the reports page to an admin', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.reports'))
        ->assertOk();
});

it('blocks creators from the reports page', function () {
    $creator = User::factory()->create()->assignRole('creator');

    $this->actingAs($creator)
        ->get(route('admin.reports'))
        ->assertForbidden();
});

it('redirects a creator from the dashboard to the uploader', function () {
    $creator = User::factory()->create()->assignRole('creator');

    $this->actingAs($creator)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.upload'));
});

it('no longer exposes the legacy tilawa create routes', function () {
    expect(Route::has('admin.tilawat.create'))->toBeFalse()
        ->and(Route::has('admin.tilawat.store'))->toBeFalse();
});
