<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Services\CardVideoRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class RenderBrandedVideo implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $tilawaId) {}

    /**
     * Landscape full-length video over the mastered audio. The primary path
     * composites the animated video card (drifting qari photo, rising extra
     * text); when Chrome/Browsershot is unavailable it falls back to the old
     * still-cover render so the pipeline never dead-ends. This is the last
     * link of the brand chain, so on success it flips brand_status to 'ready'.
     */
    public function handle(CardVideoRenderer $renderer): void
    {
        $tilawa = Tilawa::with('qari')->find($this->tilawaId);

        if ($tilawa === null) {
            return;
        }

        try {
            $result = $renderer->render($tilawa);

            $this->replaceArtifacts($tilawa, $result['video'], $result['card_image']);

            return;
        } catch (Throwable $e) {
            Log::warning("Card video render failed for tilawa {$tilawa->id}, falling back to the still cover: {$e->getMessage()}");
        }

        $this->renderStillCoverVideo($tilawa);
    }

    private function replaceArtifacts(Tilawa $tilawa, string $videoRelative, ?string $cardRelative): void
    {
        $disk = Storage::disk(config('publishing.disk'));

        foreach ([$tilawa->brand_video_path, $tilawa->brand_card['card_image'] ?? null] as $stale) {
            if ($stale && $stale !== $cardRelative) {
                $disk->delete($stale);
            }
        }

        $card = $tilawa->brand_card ?? [];

        if ($cardRelative !== null) {
            $card['card_image'] = $cardRelative;
        } else {
            unset($card['card_image']);
        }

        $tilawa->update([
            'brand_video_path' => $videoRelative,
            'brand_status' => 'ready',
            'brand_error' => null,
            'brand_card' => $card ?: null,
        ]);
    }

    private function renderStillCoverVideo(Tilawa $tilawa): void
    {
        $disk = Storage::disk(config('publishing.disk'));

        $audioAbs = $disk->path($tilawa->master_audio_path ?: $tilawa->audio_path);
        if (! is_file($audioAbs)) {
            throw new RuntimeException("Audio not found for tilawa {$tilawa->id}.");
        }

        $coverAbs = $this->resolveCover($tilawa, $disk);
        if ($coverAbs === null) {
            throw new RuntimeException('No cover image available for the branded video.');
        }

        $disk->makeDirectory(config('publishing.video_dir'));
        $disk->makeDirectory(config('publishing.poster_dir'));

        $videoRelative = config('publishing.video_dir').'/'.Str::uuid()->toString().'.mp4';
        $posterRelative = config('publishing.poster_dir').'/'.Str::uuid()->toString().'.jpg';
        $videoAbs = $disk->path($videoRelative);
        $posterAbs = $disk->path($posterRelative);

        $this->runFfmpeg($this->buildRenderCommand($tilawa, $audioAbs, $coverAbs, $videoAbs, $disk));

        if (! is_file($videoAbs)) {
            throw new RuntimeException('ffmpeg produced no branded video.');
        }

        $this->runFfmpeg([
            config('youtube.ffmpeg_path'), '-y', '-ss', '1', '-i', $videoAbs, '-frames:v', '1', '-q:v', '3', $posterAbs,
        ]);

        $this->replaceArtifacts($tilawa, $videoRelative, null);
    }

    /**
     * @return list<string>
     */
    private function buildRenderCommand(Tilawa $tilawa, string $audioAbs, string $coverAbs, string $videoAbs, $disk): array
    {
        $w = (int) config('publishing.video.width');
        $h = (int) config('publishing.video.height');
        $fps = (int) config('publishing.video.fps');

        $logo = config('publishing.logo_path');
        $hasLogo = $logo && is_file($logo);

        $args = [
            config('youtube.ffmpeg_path'), '-y',
            '-i', $audioAbs,
            '-loop', '1', '-i', $coverAbs,
        ];

        if ($hasLogo) {
            array_push($args, '-loop', '1', '-i', $logo);
        }

        $filters = ["[1:v]scale={$w}:{$h}:force_original_aspect_ratio=increase,crop={$w}:{$h},format=yuv420p[bg]"];

        if ($hasLogo) {
            $logoW = (int) round($w * 0.12);
            $margin = (int) round($w * 0.03);
            $filters[] = "[2:v]scale={$logoW}:-1[logo]";
            $filters[] = "[bg][logo]overlay=W-w-{$margin}:{$margin}[v1]";
        } else {
            $filters[] = '[bg]null[v1]';
        }

        $subtitleAbs = $tilawa->subtitle_path ? $disk->path($tilawa->subtitle_path) : null;

        if ($subtitleAbs && is_file($subtitleAbs)) {
            $escaped = str_replace([':', '\\'], ['\\:', '/'], $subtitleAbs);
            $style = config('publishing.subtitles.burn_style');
            $filters[] = "[v1]subtitles='{$escaped}':force_style='{$style}'[v]";
        } else {
            $filters[] = '[v1]null[v]';
        }

        array_push($args, '-filter_complex', implode(';', $filters));

        return array_merge($args, [
            '-map', '[v]',
            '-map', '0:a',
            '-c:v', 'libx264',
            '-preset', (string) config('publishing.video.preset'),
            '-pix_fmt', 'yuv420p',
            '-r', (string) $fps,
            '-c:a', 'aac',
            '-b:a', '192k',
            '-shortest',
            $videoAbs,
        ]);
    }

    private function resolveCover(Tilawa $tilawa, $disk): ?string
    {
        $candidates = [];

        if ($tilawa->brand_cover_path) {
            $candidates[] = $disk->path($tilawa->brand_cover_path);
        }

        if ($tilawa->cover_image) {
            $candidates[] = $disk->path($tilawa->cover_image);
        }

        if ($tilawa->qari?->image) {
            $candidates[] = $disk->path($tilawa->qari->image);
        }

        $candidates[] = public_path('images/default-cover.jpg');

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $command
     */
    private function runFfmpeg(array $command): void
    {
        $result = Process::timeout((int) config('publishing.render_timeout'))->run($command);

        if (! $result->successful()) {
            throw new RuntimeException('ffmpeg failed: '.trim($result->errorOutput() ?: $result->output()));
        }
    }

    public function failed(Throwable $exception): void
    {
        Tilawa::find($this->tilawaId)?->update([
            'brand_status' => 'failed',
            'brand_error' => Str::limit($exception->getMessage(), 1000),
        ]);
    }
}
