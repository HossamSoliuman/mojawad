<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Services\CardVideoRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RenderBrandedVideo implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $tilawaId) {}

    /**
     * Landscape full-length video over the mastered audio, compositing the
     * customized video card. This is the last link of the brand chain, so on
     * success it flips brand_status to 'ready'.
     */
    public function handle(CardVideoRenderer $renderer): void
    {
        $tilawa = Tilawa::with('qari')->find($this->tilawaId);

        if ($tilawa === null) {
            return;
        }

        $result = $renderer->render($tilawa);

        $this->replaceArtifacts($tilawa, $result['video'], $result['card_image']);
    }

    private function replaceArtifacts(Tilawa $tilawa, string $videoRelative, string $cardRelative): void
    {
        $disk = Storage::disk(config('publishing.disk'));

        foreach ([$tilawa->brand_video_path, $tilawa->brand_card['card_image'] ?? null] as $stale) {
            if ($stale && $stale !== $cardRelative) {
                $disk->delete($stale);
            }
        }

        $card = $tilawa->brand_card ?? [];

        $card['card_image'] = $cardRelative;

        $tilawa->update([
            'brand_video_path' => $videoRelative,
            'brand_status' => 'ready',
            'brand_error' => null,
            'brand_card' => $card,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Tilawa::find($this->tilawaId)?->update([
            'brand_status' => 'failed',
            'brand_error' => Str::limit($exception->getMessage(), 1000),
        ]);
    }
}
