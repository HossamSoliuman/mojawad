<?php

use App\Models\Tilawa;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('increments the play count for guests and signed-in visitors alike', function () {
    $tilawa = Tilawa::factory()->approved()->create(['plays_count' => 0]);

    $this->postJson(route('api.play', $tilawa))
        ->assertOk()
        ->assertJson(['plays' => 1]);

    expect($tilawa->fresh()->plays_count)->toBe(1);

    $this->postJson(route('api.play', $tilawa))
        ->assertOk()
        ->assertJson(['plays' => 2]);
});
