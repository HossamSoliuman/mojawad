<?php

use App\Models\Tilawa;
use App\Services\AutoShortPlanner;
use App\Services\RecitationTranscriber;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{start: float, end: float, score: float, surah: ?int, opens: bool, closes: bool, cues: list<array{start: float, end: float, text: string}>}
 */
function planSegment(float $start, float $end, bool $opens = true, bool $closes = true, ?int $surah = 55): array
{
    return [
        'start' => $start,
        'end' => $end,
        'score' => 0.9,
        'surah' => $surah,
        'opens' => $opens,
        'closes' => $closes,
        'cues' => [['start' => $start, 'end' => $end, 'text' => "آية {$start}"]],
    ];
}

it('cuts the passage on ayah boundaries close to the target length', function () {
    $planner = app(AutoShortPlanner::class);

    $plan = $planner->choose([
        planSegment(102, 110, opens: false),
        planSegment(112, 125, closes: false),
        planSegment(127, 140),
        planSegment(143, 158),
        planSegment(160, 170),
        planSegment(173, 190),
        planSegment(192, 210),
    ], 100, 220);

    expect($plan['start'])->toBe(159.6)
        ->and($plan['duration'])->toBe(51)
        ->and($plan['surah'])->toBe(55)
        ->and($plan['cues'][0]['start'])->toBe(0.4)
        ->and($plan['cues'])->toHaveCount(3);
});

it('holds each cue through the pause until the next one', function () {
    $plan = app(AutoShortPlanner::class)->choose([
        planSegment(1, 10),
        planSegment(12, 20),
        planSegment(30, 40),
    ], 0, 45, true);

    expect($plan['start'])->toBe(0.6)
        ->and($plan['cues'][0]['end'])->toBe(11.35)
        ->and($plan['cues'][1]['end'])->toBe(25.4)
        ->and(end($plan['cues'])['end'])->toBe((float) $plan['duration']);
});

it('skips a segment that may be cut by the start of the window', function () {
    $plan = app(AutoShortPlanner::class)->choose([
        planSegment(100.2, 120),
        planSegment(122, 150),
        planSegment(152, 175),
    ], 100, 200);

    expect($plan['start'])->toBe(121.6);
});

it('returns nothing when no passage is long enough', function () {
    expect(app(AutoShortPlanner::class)->choose([planSegment(1, 8)], 0, 10, true))->toBeNull();
});

it('transcribes a window of the recitation and plans a short from it', function () {
    Storage::fake('public');
    $tilawa = Tilawa::factory()->approved()->create(['surah_number' => 112, 'surahs' => null, 'duration' => 300]);

    $phrases = [];
    foreach ([30 => 'قل هو الله أحد', 40 => 'الله الصمد', 50 => 'لم يلد ولم يولد', 62 => 'ولم يكن له كفوا أحد'] as $start => $text) {
        $phrases[] = ['start' => (float) $start, 'end' => $start + 6.0, 'text' => $text];
    }

    $transcriber = Mockery::mock(RecitationTranscriber::class);
    $transcriber->shouldReceive('transcribe')
        ->once()
        ->withArgs(fn ($path, $start, $duration) => $start === 20.0 && $duration === 150.0)
        ->andReturn($phrases);

    $plan = (new AutoShortPlanner($transcriber))->plan($tilawa, 20);

    expect($plan['start'])->toBe(29.6)
        ->and($plan['surah'])->toBe(112)
        ->and($plan['cues'])->toHaveCount(4)
        ->and($plan['cues'][0]['text'])->toEndWith('۝١')
        ->and(end($plan['cues'])['text'])->toEndWith('۝٤');
});

it('never runs a short across speech that is not in the surah', function () {
    $barrier = array_merge(planSegment(140, 150), ['matched' => false, 'barrier' => true, 'cues' => []]);
    $takbir = array_merge(planSegment(112, 114), ['matched' => false, 'barrier' => false, 'cues' => []]);

    $plan = app(AutoShortPlanner::class)->choose([
        planSegment(102, 110),
        $takbir,
        planSegment(116, 138),
        $barrier,
        planSegment(152, 170),
        planSegment(172, 199),
    ], 100, 230);

    expect($plan['start'])->toBe(151.6)
        ->and($plan['cues'])->toHaveCount(2);

    $plan = app(AutoShortPlanner::class)->choose([
        planSegment(102, 110),
        $takbir,
        planSegment(116, 138),
        $barrier,
    ], 100, 160);

    expect($plan['start'])->toBe(101.6)
        ->and($plan['cues'])->toHaveCount(2);
});
