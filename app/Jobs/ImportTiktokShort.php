<?php

namespace App\Jobs;

use App\Models\Short;
use App\Services\TiktokImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImportTiktokShort implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $shortId) {}

    public function handle(TiktokImportService $tiktok): void
    {
        $short = Short::find($this->shortId);

        if ($short === null || $short->import_status === 'completed') {
            return;
        }

        if (! $short->source_url) {
            throw new RuntimeException('This short has no TikTok source URL.');
        }

        $short->update(['import_status' => 'processing', 'import_error' => null]);

        $result = $tiktok->download($short->source_url);

        $updates = [
            'media_path' => $result['path'],
            'import_status' => 'completed',
            'import_error' => null,
        ];

        if (filled($result['title'])) {
            $updates['title_ar'] = $result['title'];
        }

        $short->update($updates);

        Cache::forget('active_hero_shorts');
    }

    public function failed(Throwable $exception): void
    {
        Short::find($this->shortId)?->update([
            'import_status' => 'failed',
            'import_error' => Str::limit($exception->getMessage(), 1000),
        ]);
    }
}
