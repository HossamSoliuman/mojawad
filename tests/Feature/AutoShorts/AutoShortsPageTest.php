<?php

use App\Jobs\PrepareAutoShort;
use App\Jobs\RenderVideoClip;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\User;
use App\Models\VideoClip;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('queues auto shorts for a qari from the clips page', function () {
    Bus::fake();
    $admin = User::factory()->create()->assignRole('admin');
    $qari = Qari::factory()->create();
    Tilawa::factory()->approved()->count(3)->create(['qari_id' => $qari->id, 'duration' => 600]);

    $this->actingAs($admin)
        ->get(route('admin.clips.index'))
        ->assertOk()
        ->assertSee(__('Auto shorts'))
        ->assertSee($qari->name);

    $this->actingAs($admin)
        ->post(route('admin.clips.auto'), ['qari_id' => $qari->id, 'count' => 2])
        ->assertRedirect(route('admin.clips.index'))
        ->assertSessionHas('success');

    expect(VideoClip::where('template', 'mosque')->count())->toBe(2);
    Bus::assertDispatchedTimes(PrepareAutoShort::class, 2);
});

it('validates the auto shorts request', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.clips.auto'), ['qari_id' => 999, 'count' => 50])
        ->assertSessionHasErrors(['qari_id', 'count']);
});

it('plans a mosque short again when it is retried before it got subtitles', function () {
    Bus::fake();
    $admin = User::factory()->create()->assignRole('admin');
    $clip = VideoClip::factory()->failed()->create(['template' => 'mosque', 'subtitles' => null]);

    $this->actingAs($admin)->post(route('admin.clips.retry', $clip))->assertRedirect();

    Bus::assertDispatched(PrepareAutoShort::class);
    Bus::assertNotDispatched(RenderVideoClip::class);
});
