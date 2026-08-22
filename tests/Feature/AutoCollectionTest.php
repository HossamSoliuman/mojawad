<?php

use App\Models\Collection;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\WatchHistory;
use App\Services\AutoCollectionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ranks the most played recitations', function () {
    $quiet = Tilawa::factory()->approved()->create(['plays_count' => 3]);
    $loud = Tilawa::factory()->approved()->create(['plays_count' => 900]);

    $items = app(AutoCollectionResolver::class)->resolve(AutoCollectionResolver::RULE_MOST_PLAYED, 10);

    expect($items->pluck('id')->all())->toBe([$loud->id, $quiet->id]);
});

it('ranks the most liked recitations', function () {
    $few = Tilawa::factory()->approved()->create(['likes_count' => 1]);
    $many = Tilawa::factory()->approved()->create(['likes_count' => 77]);

    $items = app(AutoCollectionResolver::class)->resolve(AutoCollectionResolver::RULE_MOST_LIKED, 10);

    expect($items->pluck('id')->all())->toBe([$many->id, $few->id]);
});

it('picks the least played recitations of prominent qaris as rare gems', function () {
    $bigQari = Qari::factory()->create(['status' => 'active', 'is_featured' => true]);
    $rare = Tilawa::factory()->approved()->create(['qari_id' => $bigQari->id, 'plays_count' => 2]);
    $famous = Tilawa::factory()->approved()->create(['qari_id' => $bigQari->id, 'plays_count' => 5000]);

    $items = app(AutoCollectionResolver::class)->resolve(AutoCollectionResolver::RULE_RARE_GEMS, 10);

    expect($items->first()->id)->toBe($rare->id)
        ->and($items->pluck('id'))->toContain($famous->id);
});

it('ranks trending recitations by plays in the last week', function () {
    $hot = Tilawa::factory()->approved()->create();
    $stale = Tilawa::factory()->approved()->create();

    WatchHistory::factory()->count(3)->create(['tilawa_id' => $hot->id, 'last_watched_at' => now()->subDay()]);
    WatchHistory::factory()->create(['tilawa_id' => $stale->id, 'last_watched_at' => now()->subMonth()]);

    $items = app(AutoCollectionResolver::class)->resolve(AutoCollectionResolver::RULE_TRENDING, 10);

    expect($items->pluck('id')->all())->toBe([$hot->id]);
});

it('never returns inactive recitations', function () {
    Tilawa::factory()->create(['status' => 'pending', 'plays_count' => 9999]);
    $active = Tilawa::factory()->approved()->create(['plays_count' => 1]);

    $items = app(AutoCollectionResolver::class)->resolve(AutoCollectionResolver::RULE_MOST_PLAYED, 10);

    expect($items->pluck('id')->all())->toBe([$active->id]);
});

it('honours the configured limit', function () {
    Tilawa::factory()->count(6)->approved()->create();

    expect(app(AutoCollectionResolver::class)->resolve(AutoCollectionResolver::RULE_RECENT, 4))->toHaveCount(4);
});

it('returns nothing for an unknown rule', function () {
    Tilawa::factory()->approved()->create();

    expect(app(AutoCollectionResolver::class)->resolve('not_a_rule', 10))->toBeEmpty();
});

it('shows an auto collection page filled from live stats', function () {
    $top = Tilawa::factory()->approved()->create(['title_ar' => 'تلاوة الأكثر استماعا', 'plays_count' => 500]);
    Tilawa::factory()->approved()->create(['plays_count' => 1]);

    $collection = Collection::factory()->auto(AutoCollectionResolver::RULE_MOST_PLAYED, 1)->create([
        'title_ar' => 'الأكثر استماعًا',
    ]);

    $this->get(route('collections.show', $collection))
        ->assertOk()
        ->assertSee($top->title_ar)
        ->assertSee(__('Updates automatically'));
});

it('counts auto collection items on the index without any pivot rows', function () {
    Tilawa::factory()->count(3)->approved()->create();

    $collection = Collection::factory()->auto(AutoCollectionResolver::RULE_RECENT, 2)->create();

    expect($collection->tilawat()->count())->toBe(0)
        ->and($collection->itemsCount())->toBe(2);

    $this->get(route('collections.index'))->assertOk()->assertSee($collection->title_ar);
});
