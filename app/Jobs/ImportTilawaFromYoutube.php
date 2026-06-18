<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Services\AudioDurationService;
use App\Services\YoutubeImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class ImportTilawaFromYoutube implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $sourceId) {}

    public function handle(YoutubeImportService $youtube): void
    {
        $source = TilawatSource::find($this->sourceId);

        if ($source === null || $source->status === 'completed') {
            return;
        }

        $source->update(['status' => 'processing', 'error' => null]);

        $meta = $youtube->probe($source->source_url);

        $source->update([
            'source_video_id' => $meta['video_id'],
            'source_title' => $meta['title'],
            'thumbnail_url' => $meta['thumbnail'],
        ]);

        $duplicate = TilawatSource::where('source_video_id', $meta['video_id'])
            ->where('status', 'completed')
            ->whereKeyNot($source->id)
            ->first();

        if ($duplicate !== null) {
            throw new \RuntimeException("This video was already imported (source #{$duplicate->id}).");
        }

        $audioPath = $youtube->download($source->source_url);

        $isAdmin = $source->creator?->hasRole('admin') ?? false;

        $tilawa = Tilawa::create([
            'qari_id' => $source->qari_id,
            'title_ar' => $meta['title'],
            'title_en' => null,
            'slug' => $this->uniqueSlug($meta['title']),
            'audio_path' => $audioPath,
            'duration' => AudioDurationService::getSeconds($audioPath),
            'cover_image' => null,
            'status' => $isAdmin ? 'active' : 'pending',
            'review_status' => $isAdmin ? 'approved' : 'pending',
            'is_featured' => false,
            'uploaded_by' => $source->created_by,
        ]);

        $tilawa->logReview($isAdmin ? 'approved' : 'submitted', null, $isAdmin ? $source->created_by : null);

        $source->update([
            'tilawa_id' => $tilawa->id,
            'status' => 'completed',
            'processed_at' => now(),
            'error' => null,
        ]);

        Cache::forget('homepage_data');
    }

    public function failed(Throwable $exception): void
    {
        $source = TilawatSource::find($this->sourceId);

        $source?->update([
            'status' => 'failed',
            'error' => Str::limit($exception->getMessage(), 1000),
            'processed_at' => now(),
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'tilawa';
        $slug = $base;

        while (Tilawa::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::random(6);
        }

        return $slug;
    }
}
