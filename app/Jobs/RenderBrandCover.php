<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Services\BrandCoverService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RenderBrandCover implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $tilawaId) {}

    public function handle(BrandCoverService $covers): void
    {
        $tilawa = Tilawa::with('qari')->find($this->tilawaId);

        if ($tilawa === null) {
            return;
        }

        // Reuse an existing cover on re-render/retry — it only depends on the
        // recitation's metadata, which does not change between attempts.
        if ($tilawa->brand_cover_path && Storage::disk(config('publishing.disk'))->exists($tilawa->brand_cover_path)) {
            return;
        }

        $tilawa->update(['brand_cover_path' => $covers->render($tilawa)]);
    }

    public function failed(Throwable $exception): void
    {
        Tilawa::find($this->tilawaId)?->update([
            'brand_status' => 'failed',
            'brand_error' => Str::limit($exception->getMessage(), 1000),
        ]);
    }
}
