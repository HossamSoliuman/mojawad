<?php

use App\Jobs\PrepareAutoShort;
use App\Jobs\RenderVideoClip;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\User;
use App\Models\VideoClip;
use App\Services\AutoShortPlanner;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('queues one mosque short per recitation, spreading surahs and backgrounds', function () {
    Bus::fake();
    $admin = User::factory()->create()->assignRole('admin');
    $qari = Qari::factory()->create();

    Tilawa::factory()->approved()->create(['qari_id' => $qari->id, 'surah_number' => 55, 'duration' => 600, 'plays_count' => 9]);
    Tilawa::factory()->approved()->create(['qari_id' => $qari->id, 'surah_number' => 55, 'duration' => 600, 'plays_count' => 5]);
    Tilawa::factory()->approved()->create(['qari_id' => $qari->id, 'surah_number' => 18, 'duration' => 600, 'plays_count' => 1]);
    Tilawa::factory()->approved()->create(['qari_id' => $qari->id, 'surah_number' => 103, 'duration' => 46]);
    $done = Tilawa::factory()->approved()->create(['qari_id' => $qari->id, 'surah_number' => 36, 'duration' => 600]);
    VideoClip::factory()->create(['tilawa_id' => $done->id, 'template' => 'mosque']);

    $this->artisan('clips:auto-shorts', ['qaris' => [$qari->id], '--count' => 2])
        ->expectsOutputToContain('2 shorts queued')
        ->assertSuccessful();

    $clips = VideoClip::where('template', 'mosque')->where('tilawa_id', '!=', $done->id)->with('tilawa')->get();

    expect($clips)->toHaveCount(2)
        ->and($clips->pluck('tilawa.surah_number')->sort()->values()->all())->toBe([18, 55])
        ->and($clips->pluck('background_path')->unique())->toHaveCount(2)
        ->and($clips->every(fn ($clip) => $clip->created_by === $admin->id && $clip->status === 'pending'))->toBeTrue();

    Bus::assertDispatchedTimes(PrepareAutoShort::class, 2);
});

it('plans the passage, stores the subtitles and renders the short', function () {
    Bus::fake([RenderVideoClip::class]);
    $qari = Qari::factory()->create(['name_ar' => 'محمود خليل الحصري']);
    $tilawa = Tilawa::factory()->approved()->create(['qari_id' => $qari->id, 'surah_number' => 55, 'duration' => 900]);
    $clip = VideoClip::factory()->create(['tilawa_id' => $tilawa->id, 'template' => 'mosque', 'overlay_path' => null]);

    $planner = Mockery::mock(AutoShortPlanner::class);
    $planner->shouldReceive('plan')->once()->andReturnNull();
    $planner->shouldReceive('plan')->once()->andReturn([
        'start' => 301.6,
        'duration' => 52,
        'surah' => 55,
        'cues' => [['start' => 0.4, 'end' => 8.0, 'text' => 'ٱلرَّحۡمَٰنُ ۝١']],
    ]);

    (new PrepareAutoShort($clip->id))->handle($planner);

    $clip->refresh();

    expect($clip->clip_start)->toBe(301.6)
        ->and($clip->clip_duration)->toBe(52)
        ->and($clip->subtitles['cues'])->toHaveCount(1)
        ->and($clip->caption_ar)->toBe('سورة الرحمن | الشيخ محمود خليل الحصري');

    Bus::assertDispatchedSync(RenderVideoClip::class);
});

it('marks the short failed when no passage is found', function () {
    $tilawa = Tilawa::factory()->approved()->create(['duration' => 900]);
    $clip = VideoClip::factory()->create(['tilawa_id' => $tilawa->id, 'template' => 'mosque']);

    $planner = Mockery::mock(AutoShortPlanner::class);
    $planner->shouldReceive('plan')->times(3)->andReturnNull();

    $job = new PrepareAutoShort($clip->id);

    expect(fn () => $job->handle($planner))->toThrow(RuntimeException::class);

    $job->failed(new RuntimeException('No clean passage'));

    expect($clip->fresh()->status)->toBe('failed');
});

it('spreads the scan windows over the recitation', function () {
    $tilawa = Tilawa::factory()->make(['duration' => 1000]);

    $starts = (new PrepareAutoShort(1))->windowStarts($tilawa, 7);

    expect($starts)->toHaveCount(3)
        ->and(max($starts))->toBeLessThanOrEqual(880.0)
        ->and(count(array_unique($starts)))->toBe(3)
        ->and((new PrepareAutoShort(1))->windowStarts(Tilawa::factory()->make(['duration' => 100]), 7))->toBe([0.0]);
});
