<?php

namespace App\Jobs;

use App\Models\Tilawa;
use getID3;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessTilawaDuration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 5;

    public function __construct(public Tilawa $tilawa) {}

    public function handle(): void
    {
        $absolutePath = Storage::disk('public')->path($this->tilawa->audio_path);

        if (! file_exists($absolutePath)) {
            Log::error("[ProcessTilawaDuration] File not found for Tilawa #{$this->tilawa->id}: {$absolutePath}");
            return;
        }

        $getID3   = new getID3();
        $fileInfo = $getID3->analyze($absolutePath);

        $seconds = (int) round((float) ($fileInfo['playtime_seconds'] ?? 0));

        if ($seconds <= 0) {
            Log::warning("[ProcessTilawaDuration] Could not read duration for Tilawa #{$this->tilawa->id}. Raw info: " . json_encode($fileInfo['error'] ?? 'none'));
            return;
        }

        $this->tilawa->updateQuietly(['duration' => $seconds]);

        Log::info("[ProcessTilawaDuration] Tilawa #{$this->tilawa->id} duration set to {$seconds}s");
    }
}
