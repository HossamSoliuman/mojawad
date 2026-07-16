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

function indexAdmin(): User
{
    return User::factory()->create()->assignRole('admin');
}

it('lists qaris with their recitation counts on the By Qari tab', function () {
    $qari = Qari::factory()->create(['name_ar' => 'Grid Reciter', 'name_en' => 'Grid Reciter']);
    Tilawa::factory()->count(3)->approved()->create(['qari_id' => $qari->id]);

    $this->actingAs(indexAdmin())
        ->get(route('admin.tilawat.index', ['tab' => 'qaris']))
        ->assertOk()
        ->assertSee('class="qari-grid"', false)
        ->assertSee('class="qari-card"', false)
        ->assertSee('Grid Reciter');
});

it('shows only the selected qari recitations with a back link', function () {
    $qari = Qari::factory()->create(['name_ar' => 'Solo Reciter', 'name_en' => 'Solo Reciter']);
    Tilawa::factory()->approved()->create([
        'qari_id' => $qari->id,
        'title_ar' => 'تلاوة القارئ المختار',
    ]);
    Tilawa::factory()->approved()->create(['title_ar' => 'تلاوة قارئ اخر']);

    $this->actingAs(indexAdmin())
        ->get(route('admin.tilawat.index', ['tab' => 'qaris', 'qari' => $qari->id]))
        ->assertOk()
        ->assertSee('class="qari-back"', false)
        ->assertSee('تلاوة القارئ المختار')
        ->assertDontSee('تلاوة قارئ اخر');
});

it('scopes the By Qari tab to a creators own uploads', function () {
    $creator = User::factory()->create();
    $creator->assignRole('creator');

    $mine = Qari::factory()->create(['name_ar' => 'My Reciter', 'name_en' => 'My Reciter']);
    Tilawa::factory()->create(['qari_id' => $mine->id, 'uploaded_by' => $creator->id]);

    $foreign = Qari::factory()->create(['name_ar' => 'Foreign Reciter', 'name_en' => 'Foreign Reciter']);
    Tilawa::factory()->create(['qari_id' => $foreign->id]);

    $this->actingAs($creator)
        ->get(route('admin.tilawat.index', ['tab' => 'qaris']))
        ->assertOk()
        ->assertSee('My Reciter')
        ->assertDontSee('Foreign Reciter');
});
