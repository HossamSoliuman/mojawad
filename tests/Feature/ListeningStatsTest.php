<?php

use App\Models\ListenEvent;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\User;
use App\Models\WatchHistory;
use App\Services\ListeningStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function seedEvent(User $user, Tilawa $tilawa, int $seconds, Carbon $at): ListenEvent
{
    return ListenEvent::factory()->create([
        'user_id' => $user->id,
        'tilawa_id' => $tilawa->id,
        'qari_id' => $tilawa->qari_id,
        'seconds' => $seconds,
        'listened_at' => $at,
    ]);
}

it('sums all-time and this-week listening seconds', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create();

    seedEvent($user, $tilawa, 120, Carbon::now());
    seedEvent($user, $tilawa, 60, Carbon::now()->subWeeks(2));

    $totals = (new ListeningStats($user))->totals();

    expect($totals['all_time_seconds'])->toBe(180)
        ->and($totals['week_seconds'])->toBe(120);
});

it('counts completed recitations in totals', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create();
    WatchHistory::factory()->for($user)->for($tilawa)->completed()->create();

    expect((new ListeningStats($user))->totals()['completed'])->toBe(1);
});

it('computes a consecutive-day streak ending today', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create();

    seedEvent($user, $tilawa, 30, Carbon::today());
    seedEvent($user, $tilawa, 30, Carbon::today()->subDay());
    seedEvent($user, $tilawa, 30, Carbon::today()->subDays(2));
    // gap on day 3, then an older event that should not extend the streak
    seedEvent($user, $tilawa, 30, Carbon::today()->subDays(5));

    expect((new ListeningStats($user))->streak())->toBe(3);
});

it('returns zero streak when the last listen was over a day ago', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create();

    seedEvent($user, $tilawa, 30, Carbon::today()->subDays(3));

    expect((new ListeningStats($user))->streak())->toBe(0);
});

it('buckets seconds into a 24-hour histogram', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create();

    seedEvent($user, $tilawa, 40, Carbon::today()->setTime(9, 15));
    seedEvent($user, $tilawa, 20, Carbon::today()->setTime(9, 50));
    seedEvent($user, $tilawa, 30, Carbon::today()->setTime(21, 0));

    $hist = (new ListeningStats($user))->hourHistogram();

    expect($hist)->toHaveCount(24)
        ->and($hist[9])->toBe(60)
        ->and($hist[21])->toBe(30)
        ->and($hist[0])->toBe(0);
});

it('builds a zero-filled daily trend of the requested length', function () {
    $user = User::factory()->create();
    $tilawa = Tilawa::factory()->approved()->create();

    seedEvent($user, $tilawa, 50, Carbon::today());
    seedEvent($user, $tilawa, 25, Carbon::today()->subDays(2));

    $trend = (new ListeningStats($user))->dailyTrend(7);

    expect($trend)->toHaveCount(7)
        ->and($trend[6]['date'])->toBe(Carbon::today()->toDateString())
        ->and($trend[6]['seconds'])->toBe(50)
        ->and($trend[4]['seconds'])->toBe(25)
        ->and($trend[5]['seconds'])->toBe(0);
});

it('ranks top qaris by summed listening time', function () {
    $user = User::factory()->create();
    $popular = Qari::factory()->create();
    $quiet = Qari::factory()->create();
    $popularTilawa = Tilawa::factory()->for($popular)->approved()->create();
    $quietTilawa = Tilawa::factory()->for($quiet)->approved()->create();

    seedEvent($user, $popularTilawa, 200, Carbon::now());
    seedEvent($user, $quietTilawa, 50, Carbon::now());

    $top = (new ListeningStats($user))->topQaris(5);

    expect($top)->toHaveCount(2)
        ->and($top[0]['qari']->id)->toBe($popular->id)
        ->and($top[0]['seconds'])->toBe(200)
        ->and($top[1]['qari']->id)->toBe($quiet->id);
});

it('ranks most-listened tilawat by summed listening time', function () {
    $user = User::factory()->create();
    $a = Tilawa::factory()->approved()->create();
    $b = Tilawa::factory()->approved()->create();

    seedEvent($user, $a, 30, Carbon::now());
    seedEvent($user, $b, 90, Carbon::now());

    $top = (new ListeningStats($user))->topTilawat(5);

    expect($top[0]['tilawa']->id)->toBe($b->id)
        ->and($top[0]['seconds'])->toBe(90);
});
