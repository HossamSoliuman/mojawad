<?php

namespace App\Services;

use App\Models\Tilawa;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Browsershot\Browsershot;
use Throwable;

class BrandCoverService
{
    /**
     * Render one branded square cover per recitation. This image is embedded in
     * the mastered audio (Spotify/Anghami branding) and reused as the video
     * background, so it is produced first.
     */
    public function render(Tilawa $tilawa): string
    {
        $disk = Storage::disk(config('publishing.disk'));
        $disk->makeDirectory(config('publishing.cover_dir'));

        $relative = config('publishing.cover_dir').'/'.Str::uuid()->toString().'.png';
        $outAbs = $disk->path($relative);

        $rendered = config('publishing.cover.driver') === 'browsershot'
            ? $this->tryBrowsershot($tilawa, $outAbs)
            : false;

        if (! $rendered) {
            $this->ffmpegFallback($tilawa, $outAbs);
        }

        if (! is_file($outAbs)) {
            throw new RuntimeException('Cover render produced no file.');
        }

        return $relative;
    }

    private function tryBrowsershot(Tilawa $tilawa, string $outAbs): bool
    {
        try {
            $size = (int) config('publishing.cover.size');

            Browsershot::html($this->html($tilawa))
                ->windowSize($size, $size)
                ->deviceScaleFactor(1)
                ->waitUntilNetworkIdle()
                ->setScreenshotType('png')
                ->save($outAbs);

            return is_file($outAbs);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function html(Tilawa $tilawa): string
    {
        $disk = Storage::disk(config('publishing.disk'));

        return View::make('brand.cover', [
            'surahName' => $tilawa->surah_name,
            'qariName' => $tilawa->qari?->name,
            'ayahFrom' => $tilawa->ayah_from,
            'ayahTo' => $tilawa->ayah_to,
            'qariImage' => $tilawa->qari?->image ? $this->embeddable($disk->path($tilawa->qari->image)) : null,
            'logo' => $this->embeddable(config('publishing.logo_path')),
            'size' => (int) config('publishing.cover.size'),
        ])->render();
    }

    /**
     * A pure-ffmpeg fallback used when Chrome/Node is unavailable: the qari photo
     * (or a solid brand colour) darkened, with a centred logo. Arabic drawtext is
     * intentionally omitted here — ffmpeg does not shape Arabic — so the audio
     * ID3 tags remain the reliable text branding on that path.
     */
    private function ffmpegFallback(Tilawa $tilawa, string $outAbs): void
    {
        $size = (int) config('publishing.cover.size');
        $disk = Storage::disk(config('publishing.disk'));

        $qariImage = $tilawa->qari?->image ? $disk->path($tilawa->qari->image) : null;
        $hasQari = $qariImage !== null && is_file($qariImage);
        $logo = config('publishing.logo_path');
        $hasLogo = $logo && is_file($logo);

        $args = [config('youtube.ffmpeg_path'), '-y'];

        if ($hasQari) {
            array_push($args, '-i', $qariImage);
        } else {
            array_push($args, '-f', 'lavfi', '-i', "color=c=0x0b0f14:s={$size}x{$size}");
        }

        if ($hasLogo) {
            array_push($args, '-i', $logo);
        }

        $filters = $hasQari
            ? "[0:v]scale={$size}:{$size}:force_original_aspect_ratio=increase,crop={$size}:{$size},eq=brightness=-0.25[bg]"
            : '[0:v]null[bg]';

        if ($hasLogo) {
            $logoW = (int) round($size * 0.4);
            $filters .= ";[1:v]scale={$logoW}:-1[logo];[bg][logo]overlay=(W-w)/2:(H-h)/2[out]";
            $map = '[out]';
        } else {
            $map = '[bg]';
        }

        array_push($args, '-filter_complex', $filters, '-map', $map, '-frames:v', '1', $outAbs);

        $result = Process::timeout((int) config('publishing.render_timeout'))->run($args);

        if (! $result->successful()) {
            throw new RuntimeException('ffmpeg cover fallback failed: '.trim($result->errorOutput() ?: $result->output()));
        }
    }

    /**
     * Browsershot rejects HTML containing file:// URLs, so local images must
     * be inlined as base64 data URIs (remote URLs pass through untouched).
     */
    private function embeddable(?string $pathOrUrl): ?string
    {
        if (! $pathOrUrl) {
            return null;
        }

        if (Str::startsWith($pathOrUrl, ['http://', 'https://', 'data:'])) {
            return $pathOrUrl;
        }

        return app(VideoCardService::class)->dataUri($pathOrUrl);
    }
}
