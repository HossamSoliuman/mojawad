<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Services\AudioDurationService;
use App\Services\AyahDetectionService;
use App\Services\SourceCleanService;
use App\Support\TilawaTitle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class IngestSourceAudio implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * Cleaning a full-surah recitation shells out to ffmpeg for up to the render
     * timeout, so the worker must give the job at least that long before killing
     * it — while staying under the queue's retry_after so it is never reclaimed.
     */
    public int $timeout;

    public function __construct(public int $sourceId)
    {
        $this->timeout = (int) config('publishing.render_timeout') + 60;
    }

    public function handle(SourceCleanService $cleaner): void
    {
        $source = TilawatSource::find($this->sourceId);

        if ($source === null || $source->status === 'completed') {
            return;
        }

        $source->update(['status' => 'processing', 'error' => null]);

        $disk = Storage::disk(config('publishing.disk'));

        $sourceDisk = $source->source_type === 'library'
            ? Storage::disk(config('publishing.library_disk'))
            : $disk;
        $sourceAbs = $sourceDisk->path($source->source_url);

        if (! is_file($sourceAbs)) {
            throw new RuntimeException("Source audio not found: {$source->source_url}");
        }

        $cleanRelative = config('publishing.clean_dir').'/'.Str::uuid()->toString().'.mp3';
        $disk->makeDirectory(config('publishing.clean_dir'));

        $cleaner->clean($sourceAbs, $disk->path($cleanRelative));

        $surahs = $source->surahs ?? [];
        $isMultiSurah = count($surahs) > 1;

        if ($isMultiSurah) {
            $title = TilawaTitle::forSurahs($surahs);
        } elseif ($source->surah_number) {
            $title = TilawaTitle::forSurahs([$source->surah_number], $source->part);
        } else {
            $title = $source->source_title ?: TilawaTitle::surahOnly(null);
        }

        $tilawa = Tilawa::create([
            'qari_id' => $source->qari_id,
            'surah_number' => $source->surah_number,
            'surahs' => $isMultiSurah ? $surahs : null,
            'title_ar' => $title,
            'title_en' => null,
            'slug' => $this->uniqueSlug($title),
            'audio_path' => $cleanRelative,
            'duration' => AudioDurationService::getSeconds($cleanRelative),
            'ayah_from' => null,
            'ayah_to' => null,
            'ayah_confidence' => null,
            'cover_image' => null,
            'status' => 'inactive',
            'review_status' => 'pending',
            'brand_status' => 'none',
            'is_featured' => false,
            'uploaded_by' => $source->created_by,
        ]);

        $tilawa->logReview('submitted', null, null);

        $source->update([
            'tilawa_id' => $tilawa->id,
            'status' => 'completed',
            'processed_at' => now(),
            'error' => null,
        ]);

        if (AyahDetectionService::enabled() && ! $isMultiSurah) {
            DetectAyahRange::dispatch($tilawa->id);
        }
    }

    public function failed(Throwable $exception): void
    {
        TilawatSource::find($this->sourceId)?->update([
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
