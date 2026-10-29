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
 * A recitation already posted or scheduled on the platforms, going live at a Cairo time.
 *
 * @return array{tilawa: Tilawa, at: CarbonImmutable, posted: bool}
 */
function bookedPost(int $qariId, int $surah, string $at, bool $posted = true, int $id = 90): array
{
    return ['tilawa' => scheduleQueue([[$qariId, $surah]], $id)->first(), 'at' => CarbonImmutable::parse($at, 'Africa/Cairo'), 'posted' => $posted];
}

/**
 * @param  Collection<int, array{tilawa: Tilawa, at: CarbonImmutable, kahf: bool, in_progress: bool, posted: bool}>  $plan
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
    $plan = app(PostingScheduler::class)->plan(scheduleQueue([[1, 2]]), collect([bookedPost(9, 2, '2026-10-28 09:00')]), collect());

    expect(planSlots($plan))->toBe(['Wed 09:00 q9', 'Thu 20:00 q1'])
        ->and($plan->first()['posted'])->toBeTrue()
        ->and($plan->last()['posted'])->toBeFalse();
});

it('catches up today on half-posted recitations that first went out earlier', function () {
    $inProgress = bookedPost(5, 7, '2026-10-20 20:00', posted: false, id: 50);

    $plan = app(PostingScheduler::class)->plan(scheduleQueue([[1, 2], [5, 3]]), collect([$inProgress]), collect());

    expect($plan->first()['tilawa']->id)->toBe(50)
        ->and($plan->first()['in_progress'])->toBeTrue()
        ->and($plan->first()['at']->format('Y-m-d'))->toBe('2026-10-28')
        ->and(planSlots($plan))->toBe(['Wed 20:00 q5', 'Thu 20:00 q1', 'Fri 08:00 q5']);
});

it('keeps a half-posted recitation at the time it was scheduled for', function () {
    $inProgress = bookedPost(5, 7, '2026-10-31 20:00', posted: false, id: 50);

    $plan = app(PostingScheduler::class)->plan(scheduleQueue([[1, 2], [2, 3], [3, 4], [4, 5]]), collect([$inProgress]), collect());

    expect($plan->map(fn (array $entry) => $entry['at']->format('D H:i'))->all())->toBe(['Wed 20:00', 'Thu 20:00', 'Fri 08:00', 'Sat 20:00', 'Sun 20:00'])
        ->and($plan[3]['tilawa']->id)->toBe(50)
        ->and($plan[3]['in_progress'])->toBeTrue();
});

it('books the queue around posts already scheduled on the platforms', function () {
    $scheduled = bookedPost(7, 18, '2026-10-30 08:00');

    $plan = app(PostingScheduler::class)->plan(scheduleQueue([[1, 2], [2, 3], [3, 4]]), collect([$scheduled]), collect());

    expect($plan->map(fn (array $entry) => $entry['at']->format('D H:i').($entry['posted'] ? ' posted' : ''))->all())
        ->toBe(['Wed 20:00', 'Thu 20:00', 'Fri 08:00 posted', 'Sat 20:00'])
        ->and($plan[2]['kahf'])->toBeTrue();
});

it('moves a waiting Al-Kahf to the next Friday when this one is already scheduled', function () {
    $scheduled = bookedPost(7, 18, '2026-10-30 08:00');

    $plan = app(PostingScheduler::class)->plan(scheduleQueue([[1, 18], [2, 2]]), collect([$scheduled]), collect());

    expect($plan->map(fn (array $entry) => $entry['at']->format('Y-m-d H:i').($entry['kahf'] ? ' kahf' : ''))->all())->toBe([
        '2026-10-28 20:00',
        '2026-10-30 08:00 kahf',
        '2026-11-06 08:00 kahf',
    ])->and($plan->last()['tilawa']->id)->toBe(1);
});

it('keeps the qaris around a scheduled post apart', function () {
    $scheduled = bookedPost(1, 2, '2026-10-29 20:00');

    $plan = app(PostingScheduler::class)->plan(scheduleQueue([[1, 3], [2, 4], [3, 5]]), collect([$scheduled]), collect());

    expect(planSlots($plan))->toHaveCount(4)
        ->and($plan[1]['posted'])->toBeTrue()
        ->and($plan[0]['tilawa']->qari_id)->not->toBe(1)
        ->and($plan[2]['tilawa']->qari_id)->not->toBe(1)
        ->and(planSlots($plan)[3])->toBe('Sat 20:00 q1');
});

it('lays out the coming week with what is booked each day', function () {
    $scheduler = app(PostingScheduler::class);
    $plan = $scheduler->plan(scheduleQueue([[1, 2], [2, 18]]), collect(), collect());

    $days = $scheduler->days($plan);

    expect($days)->toHaveCount(7)
        ->and(collect($days)->map(fn (array $day) => count($day['posts']))->all())->toBe([1, 0, 1, 0, 0, 0, 0])
        ->and($days[2]['posts'][0]['kahf'])->toBeTrue();
});
