<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Models\VideoClip;
use App\Services\AutoShortPlanner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PrepareAutoShort implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    /**
     * Windows of the recitation tried in turn until one yields a passage.
     */
    private const ATTEMPTS = 3;

    public function __construct(public int $clipId) {}

    /**
     * Find a clean passage in the recitation, store its start, length and
     * subtitles on the clip, then render it straight away so shorts appear
     * one by one rather than after every passage has been planned.
     */
    public function handle(AutoShortPlanner $planner): void
    {
        $clip = VideoClip::with('tilawa.qari')->find($this->clipId);

        if ($clip === null || $clip->status === 'completed') {
            return;
        }

        $clip->update(['status' => 'processing', 'error' => null]);

        $tilawa = $clip->tilawa ?? throw new RuntimeException('The tilawa for this clip no longer exists.');

        $plan = null;

        foreach ($this->windowStarts($tilawa, $clip->id) as $start) {
            $plan = $planner->plan($tilawa, $start);

            if ($plan !== null) {
                break;
            }
        }

        if ($plan === null) {
            throw new RuntimeException('No clean passage for a short was found in this recitation.');
        }

        $surah = $plan['surah'] ?? $tilawa->surah_number;

        $clip->update([
            'clip_start' => $plan['start'],
            'clip_duration' => $plan['duration'],
            'subtitles' => ['surah' => $surah, 'cues' => $plan['cues']],
            'caption_ar' => collect([
                $surah ? __('Surah :name', ['name' => config('surahs.'.$surah)], 'ar') : null,
                $tilawa->qari?->name_ar ? __('Sheikh :name', ['name' => $tilawa->qari->name_ar], 'ar') : null,
            ])->filter()->implode(' | '),
            'status' => 'pending',
        ]);

        RenderVideoClip::dispatchSync($clip->id);
    }

    /**
     * Where to look in the recitation: spread over its length and seeded by
     * the clip id, so shorts from long recitations do not all open the same
     * way.
     *
     * @return list<float>
     */
    public function windowStarts(Tilawa $tilawa, int $seed): array
    {
        $latest = (float) $tilawa->duration - (float) config('clips.auto.scan_window');

        if ($latest <= 0) {
            return [0.0];
        }

        $base = fmod($seed * 0.6180339887, 1.0);
        $starts = [];

        for ($attempt = 0; $attempt < self::ATTEMPTS; $attempt++) {
            $starts[] = round($latest * fmod($base + $attempt * 0.37, 1.0), 1);
        }

        return $starts;
    }

    public function failed(Throwable $exception): void
    {
        VideoClip::find($this->clipId)?->update([
            'status' => 'failed',
            'error' => Str::limit($exception->getMessage(), 1000),
        ]);
    }
}
