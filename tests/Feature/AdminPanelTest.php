<?php

use App\Models\Qari;
use App\Models\Tilawa;
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
    Qari::factory()->create([
        'name_ar' => 'Studio Reciter',
        'name_en' => 'Studio Reciter',
    ]);

    $this->actingAs($creator)
        ->get(route('admin.upload'))
        ->assertOk()
        ->assertSee('class="tilawa-create"', false)
        ->assertSee('class="tu-workspace"', false)
        ->assertSee('id="dropzone"', false)
        ->assertSee('Studio Reciter');
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

it('renders the tilawat index with the searchable qari filter', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $qari = Qari::factory()->create(['name_ar' => 'الشيخ المرشد']);

    $this->actingAs($admin)
        ->get(route('admin.tilawat.index'))
        ->assertOk()
        ->assertSee('qariFilter()', false)
        ->assertSee(json_encode($qari->name), false);
});

it('filters tilawat by the selected qari', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $wanted = Qari::factory()->create();
    $other = Qari::factory()->create();

    $mine = Tilawa::factory()->create(['qari_id' => $wanted->id, 'title_ar' => 'تلاوة مطلوبة']);
    $hidden = Tilawa::factory()->create(['qari_id' => $other->id, 'title_ar' => 'تلاوة أخرى']);

    $this->actingAs($admin)
        ->get(route('admin.tilawat.index', ['qari' => $wanted->id]))
        ->assertOk()
        ->assertSee($mine->title_ar)
        ->assertDontSee($hidden->title_ar);
});
