<?php

use App\Models\Tilawa;
use App\Services\PostingScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

beforeEach(function () {
    config([
        'publishing.schedule.timezone' => 'Africa/Cairo',
        'publishing.schedule.daily' => ['20:00'],
        'publishing.schedule.friday' => ['08:00'],
    ]);

    $this->travelTo(CarbonImmutable::parse('2026-10-28 10:00', 'Africa/Cairo'));
});

/**
 * Unsaved recitations from a compact spec: each entry is [qari_id, surah_number].
 *
 * @param  list<array{0: int, 1: int}>  $spec
 * @return Collection<int, Tilawa>
 */
function scheduleQueue(array $spec, int $firstId = 1): Collection
{
    return collect($spec)->values()->map(function (array $entry, int $index) use ($firstId): Tilawa {
        $tilawa = new Tilawa(['qari_id' => $entry[0], 'surah_number' => $entry[1]]);
        $tilawa->id = $firstId + $index;

        return $tilawa;
    });
}

/**
 * @param  Collection<int, array{tilawa: Tilawa, at: CarbonImmutable, kahf: bool, in_progress: bool}>  $plan
 * @return list<string>
 */
function planSlots(Collection $plan): array
{
    return $plan->map(fn (array $entry) => $entry['at']->format('D H:i').' q'.$entry['tilawa']->qari_id)->all();
}

it('books one recitation a day from today, Friday morning included', function () {
    $plan = app(PostingScheduler::class)->plan(scheduleQueue([[1, 2], [2, 3], [3, 4]]), collect(), collect());

    expect($plan->map(fn (array $entry) => $entry['at']->format('D Y-m-d H:i'))->all())->toBe([
        'Wed 2026-10-28 20:00',
        'Thu 2026-10-29 20:00',
        'Fri 2026-10-30 08:00',
    ])->and($plan->pluck('kahf')->unique()->all())->toBe([false]);
});

it('keeps Friday for an Al-Kahf recitation', function () {
    $queue = scheduleQueue([[1, 18], [2, 2], [3, 3], [4, 4]]);

    $plan = app(PostingScheduler::class)->plan($queue, collect(), collect());

    $friday = $plan->first(fn (array $entry) => $entry['at']->isFriday());

    expect($friday['tilawa']->id)->toBe(1)
        ->and($friday['kahf'])->toBeTrue()
        ->and($plan->map(fn (array $entry) => $entry['at']->format('D'))->all())->toBe(['Wed', 'Thu', 'Fri', 'Sat']);
});

it('counts a recitation that covers Al-Kahf among other surahs', function () {
    $tilawa = new Tilawa(['qari_id' => 1, 'surah_number' => null, 'surahs' => [18, 19]]);

    expect(app(PostingScheduler::class)->isKahf($tilawa))->toBeTrue();
});

it('books one Al-Kahf recitation per Friday and leaves the days between empty', function () {
    $queue = scheduleQueue([[1, 18], [2, 18], [3, 2]]);

    $plan = app(PostingScheduler::class)->plan($queue, collect(), collect());

    expect($plan->map(fn (array $entry) => $entry['at']->format('Y-m-d').($entry['kahf'] ? ' kahf' : ''))->all())->toBe([
        '2026-10-28',
        '2026-10-30 kahf',
        '2026-11-06 kahf',
    ]);
});

it('keeps the qari of an Al-Kahf Friday away from the days around it', function () {
    $queue = scheduleQueue([[1, 2], [1, 3], [2, 4], [2, 5], [3, 6], [1, 18]]);

    $plan = app(PostingScheduler::class)->plan($queue, collect(), collect());
    $qaris = $plan->map(fn (array $entry) => $entry['tilawa']->qari_id)->all();

    expect($plan->firstWhere('kahf', true)['at']->isFriday())->toBeTrue();

    foreach (range(1, count($qaris) - 1) as $i) {
        expect($qaris[$i])->not->toBe($qaris[$i - 1], implode(' | ', planSlots($plan)));
    }
});

it('starts tomorrow once today\'s slot has been posted', function () {
    $plan = app(PostingScheduler::class)->plan(scheduleQueue([[1, 2]]), collect(), collect(), postedToday: 1);

    expect($plan->first()['at']->format('Y-m-d H:i'))->toBe('2026-10-29 20:00');
});

it('keeps half-posted recitations on top for today', function () {
    $inProgress = scheduleQueue([[5, 7]], firstId: 50);

    $plan = app(PostingScheduler::class)->plan(scheduleQueue([[1, 2], [5, 3]]), $inProgress, collect());

    expect($plan->first()['tilawa']->id)->toBe(50)
        ->and($plan->first()['in_progress'])->toBeTrue()
        ->and($plan->first()['at']->format('Y-m-d'))->toBe('2026-10-28')
        ->and(planSlots($plan))->toBe(['Wed 20:00 q5', 'Thu 20:00 q1', 'Fri 08:00 q5']);
});

it('lays out the coming week with what is booked each day', function () {
    $scheduler = app(PostingScheduler::class);
    $plan = $scheduler->plan(scheduleQueue([[1, 2], [2, 18]]), collect(), collect());

    $days = $scheduler->days($plan);

    expect($days)->toHaveCount(7)
        ->and(collect($days)->map(fn (array $day) => count($day['posts']))->all())->toBe([1, 0, 1, 0, 0, 0, 0])
        ->and($days[2]['posts'][0]['kahf'])->toBeTrue();
});
