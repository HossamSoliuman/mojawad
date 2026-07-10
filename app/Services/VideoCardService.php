<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Browsershot\Browsershot;

class VideoCardService
{
    /**
     * The Card Lab flow: render the landscape HTML card to a PNG with headless
     * Chrome. Unlike BrandCoverService there is deliberately no ffmpeg fallback —
     * the lab exists to prove the HTML path works, so failures must surface.
     *
     * @param  array{qariName?: ?string, surahName?: ?string, extraText?: ?string, qariImage?: ?string}  $data
     */
    public function render(array $data): string
    {
        $disk = Storage::disk(config('publishing.disk'));
        $disk->makeDirectory(config('publishing.card_lab_dir'));

        $relative = config('publishing.card_lab_dir').'/'.Str::uuid()->toString().'.png';
        $outAbs = $disk->path($relative);

        Browsershot::html($this->html($data))
            ->windowSize((int) config('publishing.video.width'), (int) config('publishing.video.height'))
            ->deviceScaleFactor(1)
            ->waitUntilNetworkIdle()
            ->setScreenshotType('png')
            ->save($outAbs);

        if (! is_file($outAbs)) {
            throw new RuntimeException('Browsershot produced no card image.');
        }

        return $relative;
    }

    /**
     * The exact markup Browsershot captures — also embedded in the admin lab
     * as a live preview so what you see is what gets rendered.
     *
     * @param  array{qariName?: ?string, surahName?: ?string, extraText?: ?string, qariImage?: ?string}  $data
     */
    public function html(array $data): string
    {
        return View::make('brand.video-card', [
            'qariName' => $data['qariName'] ?? null,
            'surahName' => $data['surahName'] ?? null,
            'extraText' => $data['extraText'] ?? null,
            'qariImage' => $data['qariImage'] ?? null,
            'social' => array_filter(config('publishing.social')),
            'width' => (int) config('publishing.video.width'),
            'height' => (int) config('publishing.video.height'),
        ])->render();
    }

    /**
     * Card PNG + the first N seconds of the recitation audio → a short mp4,
     * proving the full image-over-audio video flow end to end.
     */
    public function sampleVideo(string $cardRelative, string $audioRelative, int $seconds = 15): string
    {
        $disk = Storage::disk(config('publishing.disk'));

        $cardAbs = $disk->path($cardRelative);
        $audioAbs = $disk->path($audioRelative);

        if (! is_file($cardAbs) || ! is_file($audioAbs)) {
            throw new RuntimeException('Card image or audio file is missing.');
        }

        $relative = config('publishing.card_lab_dir').'/'.Str::uuid()->toString().'.mp4';
        $outAbs = $disk->path($relative);

        $w = (int) config('publishing.video.width');
        $h = (int) config('publishing.video.height');

        $result = Process::timeout((int) config('publishing.render_timeout'))->run([
            config('youtube.ffmpeg_path'), '-y',
            '-t', (string) $seconds, '-i', $audioAbs,
            '-loop', '1', '-i', $cardAbs,
            '-filter_complex', "[1:v]scale={$w}:{$h}:force_original_aspect_ratio=increase,crop={$w}:{$h},format=yuv420p[v]",
            '-map', '[v]',
            '-map', '0:a',
            '-c:v', 'libx264',
            '-preset', 'veryfast',
            '-r', (string) config('publishing.video.fps'),
            '-c:a', 'aac',
            '-b:a', '192k',
            '-shortest',
            $outAbs,
        ]);

        if (! $result->successful() || ! is_file($outAbs)) {
            throw new RuntimeException('ffmpeg sample render failed: '.trim($result->errorOutput() ?: $result->output()));
        }

        return $relative;
    }

    /**
     * Browsershot rejects HTML containing file:// URLs, so local images are
     * inlined as base64 data URIs instead.
     */
    public function dataUri(?string $path): ?string
    {
        if (! $path || ! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
