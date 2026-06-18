<?php

use App\Livewire\SavesList;
use App\Models\SavedTilawa;
use App\Models\Tilawa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('toggles a save on and off', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create();

    $this->actingAs($user)
        ->postJson(route('api.save', $tilawa))
        ->assertOk()
        ->assertJson(['saved' => true]);

    expect(SavedTilawa::where('user_id', $user->id)->where('tilawa_id', $tilawa->id)->exists())->toBeTrue();

    $this->actingAs($user)
        ->postJson(route('api.save', $tilawa))
        ->assertOk()
        ->assertJson(['saved' => false]);

    expect(SavedTilawa::where('user_id', $user->id)->where('tilawa_id', $tilawa->id)->exists())->toBeFalse();
});

it('requires authentication to toggle or sync saves', function () {
    $tilawa = Tilawa::factory()->approved()->create();

    $this->postJson(route('api.save', $tilawa))->assertUnauthorized();
    $this->postJson(route('api.saves.sync'), ['ids' => [$tilawa->id]])->assertUnauthorized();
});

it('returns the saved ids for the signed-in user', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create();
    SavedTilawa::create(['user_id' => $user->id, 'tilawa_id' => $tilawa->id]);

    $this->actingAs($user)
        ->getJson(route('api.saves.ids'))
        ->assertOk()
        ->assertJson(['ids' => [$tilawa->id]]);
});

it('merges guest saves into the account without duplicates', function () {
    $user = User::factory()->create();
    $a = Tilawa::factory()->approved()->create();
    $b = Tilawa::factory()->approved()->create();
    SavedTilawa::create(['user_id' => $user->id, 'tilawa_id' => $a->id]);

    $this->actingAs($user)
        ->postJson(route('api.saves.sync'), ['ids' => [$a->id, $b->id]])
        ->assertOk()
        ->assertJson(['synced' => 1]);

    expect(SavedTilawa::where('user_id', $user->id)->count())->toBe(2);
});

it('renders the saved page', function () {
    $this->get(route('saved'))->assertOk();
});

it('lists a signed-in user saved tilawat in the component', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create();
    SavedTilawa::create(['user_id' => $user->id, 'tilawa_id' => $tilawa->id]);

    Livewire::actingAs($user)
        ->test(SavesList::class)
        ->assertSee($tilawa->title);
});
