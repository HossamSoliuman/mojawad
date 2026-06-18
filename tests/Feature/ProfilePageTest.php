<?php

use App\Models\ListenEvent;
use App\Models\Tilawa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('redirects guests away from the profile', function () {
    $this->get(route('profile'))->assertRedirect(route('login'));
});

it('renders the profile dashboard for an authenticated user with data', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create();
    ListenEvent::factory()->create([
        'user_id' => $user->id,
        'tilawa_id' => $tilawa->id,
        'qari_id' => $tilawa->qari_id,
        'seconds' => 60,
        'listened_at' => Carbon::today()->setTime(8, 0),
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee($user->name)
        ->assertSee(__('Total listening time'))
        ->assertSee(__('Best time of day'))
        ->assertSee(__('Favorite reciters'));
});

it('renders the profile for a user with no listening data', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee(__('Not enough data yet.'));
});
