<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Services\AudioMasteringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MasterTilawaAudio implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $tilawaId) {}

    public function handle(AudioMasteringService $mastering): void
    {
        $tilawa = Tilawa::with('qari')->find($this->tilawaId);

        if ($tilawa === null) {
            return;
        }

        // Mastering is the slow two-pass loudnorm over the full recitation; when a
        // master already exists (e.g. re-rendering the video or retrying a failed
        // render), reuse it instead of paying that cost again.
        if ($tilawa->master_audio_path && Storage::disk(config('publishing.disk'))->exists($tilawa->master_audio_path)) {
            return;
        }

        $tilawa->update(['master_audio_path' => $mastering->master($tilawa)]);
    }

    public function failed(Throwable $exception): void
    {
        Tilawa::find($this->tilawaId)?->update([
            'brand_status' => 'failed',
            'brand_error' => Str::limit($exception->getMessage(), 1000),
        ]);
    }
}
