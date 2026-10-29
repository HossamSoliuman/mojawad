<?php

use App\Models\Tilawa;
use App\Services\PublishingMixer;
use Illuminate\Support\Collection;

/**
 * Unsaved recitations from a compact spec: each entry is [qari_id, surah_number].
 *
 * @param  list<array{0: int, 1: int|null}>  $spec
 * @return Collection<int, Tilawa>
 */
function mixQueue(array $spec): Collection
{
    return collect($spec)->values()->map(function (array $entry, int $index): Tilawa {
        $tilawa = new Tilawa(['qari_id' => $entry[0], 'surah_number' => $entry[1]]);
        $tilawa->id = $index + 1;

        return $tilawa;
    });
}

/**
 * @param  Collection<int, Tilawa>  $mixed
 */
function longestQariRun(Collection $mixed): int
{
    $longest = 0;
    $run = 0;
    $previous = null;

    foreach ($mixed as $tilawa) {
        $run = $tilawa->qari_id === $previous ? $run + 1 : 1;
        $longest = max($longest, $run);
        $previous = $tilawa->qari_id;
    }

    return $longest;
}

it('never puts the same qari twice in a row when it can be avoided', function () {
    $queue = mixQueue([
        [1, 1], [1, 2], [1, 3], [1, 4], [1, 5],
        [2, 6], [2, 7], [2, 8],
        [3, 9], [3, 10],
        [4, 11],
    ]);

    $mixed = app(PublishingMixer::class)->mix($queue);

    expect($mixed)->toHaveCount(11)
        ->and($mixed->pluck('id')->sort()->values()->all())->toBe(range(1, 11))
        ->and(longestQariRun($mixed))->toBe(1);
});

it('spreads a dominant qari evenly instead of piling it at the bottom', function () {
    $queue = mixQueue([
        [1, 1], [1, 2], [1, 3], [1, 4], [1, 5], [1, 6], [1, 7], [1, 8],
        [2, 9], [3, 10], [4, 11],
    ]);

    $mixed = app(PublishingMixer::class)->mix($queue);

    expect(longestQariRun($mixed))->toBe(2);
});

it('does not open with the qari that was posted last', function () {
    $queue = mixQueue([[1, 1], [1, 2], [2, 3], [2, 4]]);
    $recentlyPosted = mixQueue([[2, 5]]);

    $mixed = app(PublishingMixer::class)->mix($queue, $recentlyPosted);

    expect($mixed->pluck('qari_id')->all())->toBe([1, 2, 1, 2]);
});

it('does not continue a run the latest posts started', function () {
    $queue = mixQueue([[1, 1], [1, 2], [1, 3], [2, 4], [2, 5], [2, 6]]);
    $recentlyPosted = mixQueue([[1, 7], [1, 8]]);

    $mixed = app(PublishingMixer::class)->mix($queue, $recentlyPosted);

    expect($mixed->pluck('qari_id')->all())->toBe([2, 1, 2, 1, 2, 1]);
});

it('keeps the same surah apart', function () {
    $queue = mixQueue([[1, 2], [1, 3], [2, 2], [2, 5]]);

    $surahs = app(PublishingMixer::class)->mix($queue)->pluck('surah_number')->all();

    foreach (range(1, count($surahs) - 1) as $i) {
        expect($surahs[$i])->not->toBe($surahs[$i - 1]);
    }
});

it('mixes the same queue the same way every time', function () {
    $queue = mixQueue([[1, 1], [1, 2], [2, 3], [3, 4], [2, 5], [1, 6], [3, 7]]);

    $first = app(PublishingMixer::class)->mix($queue)->pluck('id')->all();
    $second = app(PublishingMixer::class)->mix($queue)->pluck('id')->all();

    expect($first)->toBe($second);
});
