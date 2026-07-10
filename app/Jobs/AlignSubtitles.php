<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Services\SubtitleAlignService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class AlignSubtitles implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $tilawaId) {}

    /**
     * Subtitles are an optional enhancement — if aeneas is missing or alignment
     * fails, we log and continue so the rest of the brand chain still produces a
     * (subtitle-less) video rather than failing the whole release.
     */
    public function handle(SubtitleAlignService $aligner): void
    {
        $tilawa = Tilawa::find($this->tilawaId);

        if ($tilawa === null) {
            return;
        }

        try {
            $path = $aligner->align($tilawa);

            if ($path !== null) {
                $tilawa->update(['subtitle_path' => $path]);
            }
        } catch (Throwable $e) {
            Log::warning("[AlignSubtitles] tilawa {$this->tilawaId}: ".$e->getMessage());
        }
    }
}
