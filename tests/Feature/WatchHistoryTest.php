<?php

use App\Livewire\WatchHistoryList;
use App\Models\Tilawa;
use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('stores and upserts a watch record', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create();

    $this->actingAs($user)
        ->postJson(route('api.history.store'), ['id' => $tilawa->id, 'position' => 30, 'duration' => 300])
        ->assertOk()
        ->assertJson(['ok' => true]);

    $row = WatchHistory::where('user_id', $user->id)->where('tilawa_id', $tilawa->id)->first();
    expect($row->position)->toBe(30)
        ->and($row->duration)->toBe(300)
        ->and($row->last_watched_at)->not->toBeNull();

    $this->actingAs($user)
        ->postJson(route('api.history.store'), ['id' => $tilawa->id, 'position' => 90, 'duration' => 300, 'completed' => true])
        ->assertOk();

    expect(WatchHistory::where('user_id', $user->id)->count())->toBe(1);
    expect($row->fresh()->position)->toBe(90)
        ->and($row->fresh()->completed)->toBeTrue();
});

it('merges guest history keeping furthest progress and completed flag', function () {
    $user = User::factory()->create();
    $a = Tilawa::factory()->approved()->create();
    $b = Tilawa::factory()->approved()->create();

    WatchHistory::factory()->for($user)->for($a)->create(['position' => 100, 'completed' => false]);

    $this->actingAs($user)
        ->postJson(route('api.history.sync'), ['items' => [
            ['id' => $a->id, 'position' => 50, 'duration' => 300, 'completed' => true],
            ['id' => $b->id, 'position' => 20, 'duration' => 300, 'completed' => false],
        ]])
        ->assertOk()
        ->assertJson(['synced' => 2]);

    $rowA = WatchHistory::where('user_id', $user->id)->where('tilawa_id', $a->id)->first();
    expect($rowA->position)->toBe(100)   // kept the larger existing position
        ->and($rowA->completed)->toBeTrue(); // OR-ed in the incoming completed
    expect(WatchHistory::where('user_id', $user->id)->count())->toBe(2);
});

it('returns only in-progress active items for the rail in player payload shape', function () {
    $user = User::factory()->create();
    $active = Tilawa::factory()->approved()->create();
    $done = Tilawa::factory()->approved()->create();
    $hidden = Tilawa::factory()->create(); // pending → not active

    WatchHistory::factory()->for($user)->for($active)->create(['position' => 40, 'completed' => false]);
    WatchHistory::factory()->for($user)->for($done)->completed()->create();
    WatchHistory::factory()->for($user)->for($hidden)->create(['position' => 10, 'completed' => false]);

    $response = $this->actingAs($user)->getJson(route('api.history.index'))->assertOk();

    $items = $response->json('items');
    expect($items)->toHaveCount(1)
        ->and($items[0]['id'])->toBe($active->id)
        ->and($items[0])->toHaveKeys(['id', 'url', 'title', 'qari', 'cover', 'duration', 'download', 'position', 'completed']);
});

it('removes one item and clears all history', function () {
    $user = User::factory()->create();
    $a = Tilawa::factory()->approved()->create();
    $b = Tilawa::factory()->approved()->create();
    WatchHistory::factory()->for($user)->for($a)->create();
    WatchHistory::factory()->for($user)->for($b)->create();

    $this->actingAs($user)->deleteJson(route('api.history.destroy', $a))->assertOk();
    expect(WatchHistory::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)->deleteJson(route('api.history.clear'))->assertOk();
    expect(WatchHistory::where('user_id', $user->id)->count())->toBe(0);
});

it('requires authentication for every history endpoint', function () {
    $tilawa = Tilawa::factory()->approved()->create();

    $this->getJson(route('api.history.index'))->assertUnauthorized();
    $this->postJson(route('api.history.store'), ['id' => $tilawa->id, 'position' => 10])->assertUnauthorized();
    $this->postJson(route('api.history.sync'), ['items' => []])->assertUnauthorized();
    $this->deleteJson(route('api.history.destroy', $tilawa))->assertUnauthorized();
    $this->deleteJson(route('api.history.clear'))->assertUnauthorized();
});

it('renders the profile history list and removes a row', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create();
    WatchHistory::factory()->for($user)->for($tilawa)->create();

    Livewire::actingAs($user)
        ->test(WatchHistoryList::class)
        ->assertSee($tilawa->title)
        ->call('remove', $tilawa->id)
        ->assertDontSee($tilawa->title);

    expect(WatchHistory::where('user_id', $user->id)->count())->toBe(0);
});
