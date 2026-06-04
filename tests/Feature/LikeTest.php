<?php

use App\Models\Like;
use App\Models\Tilawa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('toggles a like on and off and tracks the count', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create(['likes_count' => 0]);

    $this->actingAs($user)
        ->postJson(route('api.like', $tilawa))
        ->assertOk()
        ->assertJson(['liked' => true, 'count' => 1]);

    expect(Like::where('user_id', $user->id)->where('tilawa_id', $tilawa->id)->exists())->toBeTrue();

    $this->actingAs($user)
        ->postJson(route('api.like', $tilawa))
        ->assertOk()
        ->assertJson(['liked' => false, 'count' => 0]);

    expect(Like::where('user_id', $user->id)->where('tilawa_id', $tilawa->id)->exists())->toBeFalse();
});

it('requires authentication to toggle or sync likes', function () {
    $tilawa = Tilawa::factory()->approved()->create();

    $this->postJson(route('api.like', $tilawa))->assertUnauthorized();
    $this->postJson(route('api.likes.sync'), ['ids' => [$tilawa->id]])->assertUnauthorized();
});

it('merges guest likes into the account without double counting', function () {
    $user = User::factory()->create();
    $a = Tilawa::factory()->approved()->create(['likes_count' => 0]);
    $b = Tilawa::factory()->approved()->create(['likes_count' => 0]);

    Like::create(['user_id' => $user->id, 'tilawa_id' => $a->id]);
    $a->increment('likes_count');

    $this->actingAs($user)
        ->postJson(route('api.likes.sync'), ['ids' => [$a->id, $b->id]])
        ->assertOk()
        ->assertJson(['synced' => 1]);

    expect(Like::where('user_id', $user->id)->count())->toBe(2);
    expect($a->fresh()->likes_count)->toBe(1);
    expect($b->fresh()->likes_count)->toBe(1);
});
