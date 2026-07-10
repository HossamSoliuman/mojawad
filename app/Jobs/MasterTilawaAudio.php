<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Services\AudioMasteringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
