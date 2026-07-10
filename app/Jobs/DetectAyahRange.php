<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Services\AyahDetectionService;
use App\Support\TilawaTitle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class DetectAyahRange implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $tilawaId) {}

    public function handle(AyahDetectionService $detector): void
    {
        $tilawa = Tilawa::find($this->tilawaId);

        if ($tilawa === null) {
            return;
        }

        $result = $detector->detect($tilawa);

        $confidence = $result['score'] >= (float) config('publishing.ayah_match_min_score') ? 'high' : 'low';

        $title = TilawaTitle::withRange($tilawa->surah_number, $result['from'], $result['to']);

        $tilawa->update([
            'ayah_from' => $result['from'],
            'ayah_to' => $result['to'],
            'ayah_confidence' => $confidence,
            'title_ar' => $title,
            'slug' => $this->uniqueSlug($title, $tilawa->id),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Tilawa::find($this->tilawaId)?->update(['ayah_confidence' => 'manual']);
    }

    private function uniqueSlug(string $title, int $ignoreId): string
    {
        $base = Str::slug($title) ?: 'tilawa';
        $slug = $base;

        while (Tilawa::where('slug', $slug)->whereKeyNot($ignoreId)->exists()) {
            $slug = $base.'-'.Str::random(6);
        }

        return $slug;
    }
}
