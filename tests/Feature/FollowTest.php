<?php

use App\Models\Qari;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('toggles a follow on and off and reports follower count', function () {
    $user = User::factory()->create();
    $qari = Qari::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.follow', $qari))
        ->assertOk()
        ->assertJson(['following' => true, 'followers' => 1]);

    expect($user->followedQaris()->whereKey($qari->id)->exists())->toBeTrue();

    $this->actingAs($user)
        ->postJson(route('api.follow', $qari))
        ->assertOk()
        ->assertJson(['following' => false, 'followers' => 0]);

    expect($user->followedQaris()->whereKey($qari->id)->exists())->toBeFalse();
});

it('requires authentication to follow', function () {
    $qari = Qari::factory()->create();

    $this->postJson(route('api.follow', $qari))->assertUnauthorized();
});

it('syncs guest follows into the account', function () {
    $user = User::factory()->create();
    $a = Qari::factory()->create();
    $b = Qari::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.follows.sync'), ['ids' => [$a->id, $b->id]])
        ->assertOk()
        ->assertJson(['synced' => 2]);

    expect($user->followedQaris()->count())->toBe(2);
});

it('returns the followed qari ids', function () {
    $user = User::factory()->create();
    $qari = Qari::factory()->create();
    $user->followedQaris()->attach($qari->id);

    $this->actingAs($user)
        ->getJson(route('api.follows.ids'))
        ->assertOk()
        ->assertJson(['ids' => [$qari->id]]);
});
