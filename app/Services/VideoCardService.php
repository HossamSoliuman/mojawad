<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Tilawa;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Browsershot\Browsershot;

class VideoCardService
{
    /**
     * Settings key holding the site-wide social handles shown in every card
     * footer, so they are edited once and shared across all cards.
     */
    public const SOCIAL_KEY = 'card.social';

    /**
     * The social handles for the footer form: the config defaults with any
     * saved global overrides layered on top. A saved empty string is kept so
     * clearing a handle hides that platform on every card.
     *
     * @return array<string, string>
     */
    public function socialHandles(): array
    {
        $saved = array_filter((array) Setting::get(self::SOCIAL_KEY, []), fn ($value): bool => $value !== null);

        return array_merge(config('publishing.social'), $saved);
    }

    /**
     * The handles actually printed on a card — the editable set with blanks
     * dropped so empty platforms disappear from the footer.
     *
     * @return array<string, string>
     */
    public function social(): array
    {
        return array_filter($this->socialHandles(), fn ($value): bool => $value !== null && $value !== '');
    }

    /**
     * The card texts and animation switches for a recitation: saved editor
     * settings win, otherwise sensible defaults from the recitation itself.
     * The extra text is the optional description/info line the admin can
     * animate into the video.
     *
     * @return array{tilawaTitle: string, qariName: ?string, surahName: ?string, extraText: ?string, rareBadge: string, animate_photo: bool, animate_text: bool}
     */
    public function dataFor(Tilawa $tilawa): array
    {
        $saved = $tilawa->brand_card ?? [];

        return [
            'tilawaTitle' => $tilawa->title_ar,
            'qariName' => $saved['qari_name'] ?? $tilawa->qari?->name,
            'surahName' => $saved['surah_name'] ?? $tilawa->surah_label,
            'extraText' => $saved['extra_text'] ?? $tilawa->description_ar,
            'rareBadge' => array_key_exists('rare_badge', $saved) ? (string) $saved['rare_badge'] : __('rare recitation'),
            'animate_photo' => (bool) ($saved['animate_photo'] ?? true),
            'animate_text' => (bool) ($saved['animate_text'] ?? true),
        ];
    }

    /**
     * The Card Lab flow: render the landscape HTML card to a PNG with headless
     * Chrome. Unlike BrandCoverService there is deliberately no ffmpeg fallback —
     * the lab exists to prove the HTML path works, so failures must surface.
     *
     * @param  array{tilawaTitle?: ?string, qariName?: ?string, surahName?: ?string, extraText?: ?string, rareBadge?: ?string, qariImage?: ?string}  $data
     */
    public function render(array $data): string
    {
        $disk = Storage::disk(config('publishing.disk'));
        $disk->makeDirectory(config('publishing.card_lab_dir'));

        $relative = config('publishing.card_lab_dir').'/'.Str::uuid()->toString().'.png';

        $this->capture($this->html($data), $disk->path($relative), false);

        return $relative;
    }

    /**
     * One compositing layer of the card (full | overlay | text) rendered to a
     * PNG — overlay and text layers keep transparency so ffmpeg can animate the
     * qari photo and the extra text underneath/above them.
     *
     * @param  array{tilawaTitle?: ?string, qariName?: ?string, surahName?: ?string, extraText?: ?string, qariImage?: ?string}  $data
     * @param  array{holdTextSpace?: bool}  $options
     */
    public function renderLayer(array $data, string $layer, array $options = []): string
    {
        $disk = Storage::disk(config('publishing.disk'));
        $disk->makeDirectory(config('publishing.cards_dir'));

        $relative = config('publishing.cards_dir').'/'.Str::uuid()->toString().'-'.$layer.'.png';

        $html = $this->html($data, [
            'layer' => $layer,
            'holdTextSpace' => (bool) ($options['holdTextSpace'] ?? false),
        ]);

        $this->capture($html, $disk->path($relative), $layer !== 'full');

        return $relative;
    }

    /**
     * The exact markup Browsershot captures — also embedded in the admin lab
     * and the Production card editor as a live preview so what you see is what
     * gets rendered.
     *
     * @param  array{tilawaTitle?: ?string, qariName?: ?string, surahName?: ?string, extraText?: ?string, rareBadge?: ?string, qariImage?: ?string}  $data
     * @param  array{layer?: string, holdTextSpace?: bool, animatePreview?: bool, animatePhoto?: bool, animateText?: bool}  $options
     */
    public function html(array $data, array $options = []): string
    {
        return View::make('brand.video-card', [
            'tilawaTitle' => $data['tilawaTitle'] ?? null,
            'qariName' => $data['qariName'] ?? null,
            'surahName' => $data['surahName'] ?? null,
            'extraText' => $data['extraText'] ?? null,
            'rareBadge' => $data['rareBadge'] ?? null,
            'qariImage' => $data['qariImage'] ?? null,
            'social' => $this->social(),
            'width' => (int) config('publishing.video.width'),
            'height' => (int) config('publishing.video.height'),
            'layer' => $options['layer'] ?? 'full',
            'holdTextSpace' => (bool) ($options['holdTextSpace'] ?? false),
            'animatePreview' => (bool) ($options['animatePreview'] ?? false),
            'animatePhoto' => (bool) ($options['animatePhoto'] ?? true),
            'animateText' => (bool) ($options['animateText'] ?? true),
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
            '-preset', (string) config('publishing.video.preset'),
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

    private function capture(string $html, string $outAbs, bool $transparent): void
    {
        $shot = $this->browsershot($html)
            ->windowSize((int) config('publishing.video.width'), (int) config('publishing.video.height'))
            ->deviceScaleFactor(1)
            ->waitUntilNetworkIdle()
            ->setScreenshotType('png');

        if ($transparent) {
            $shot->hideBackground();
        }

        $shot->save($outAbs);

        if (! is_file($outAbs)) {
            throw new RuntimeException('Browsershot produced no card image.');
        }
    }

    protected function browsershot(string $html): Browsershot
    {
        $shot = Browsershot::html($html);
        $chromePath = config('publishing.browsershot.chrome_path');

        if (is_string($chromePath) && $chromePath !== '') {
            $shot->setChromePath($chromePath);
        }

        return $shot;
    }
}
