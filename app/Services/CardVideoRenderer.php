<?php

namespace App\Services;

use App\Models\Tilawa;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CardVideoRenderer
{
    public function __construct(private VideoCardService $cards) {}

    /**
     * Full-length branded video built from the video card, composited in
     * layers so it moves instead of sitting still: the qari photo drifts
     * gently up and down behind the card overlay, and the optional extra
     * text (description / info about the tilawa) rises and fades in.
     *
     * @return array{video: string, card_image: string}
     */
    public function render(Tilawa $tilawa): array
    {
        $disk = Storage::disk(config('publishing.disk'));

        $audioAbs = $disk->path($tilawa->master_audio_path ?: $tilawa->audio_path);
        if (! is_file($audioAbs)) {
            throw new RuntimeException("Audio not found for tilawa {$tilawa->id}.");
        }

        $data = $this->cards->dataFor($tilawa);

        $photoAbs = $tilawa->qari?->image ? $disk->path($tilawa->qari->image) : null;
        if ($photoAbs !== null && ! is_file($photoAbs)) {
            $photoAbs = null;
        }

        $animateText = $data['animate_text'] && filled($data['extraText']);

        $htmlData = [
            'tilawaTitle' => $data['tilawaTitle'],
            'qariName' => $data['qariName'],
            'surahName' => $data['surahName'],
            'extraText' => $data['extraText'],
            'rareBadge' => $data['rareBadge'],
            'qariImage' => $this->cards->dataUri($photoAbs),
        ];

        $cardRelative = $this->cards->renderLayer($htmlData, 'full');
        $overlayRelative = $this->cards->renderLayer($htmlData, 'overlay', ['holdTextSpace' => $animateText]);
        $textRelative = $animateText ? $this->cards->renderLayer($htmlData, 'text') : null;

        $disk->makeDirectory(config('publishing.video_dir'));
        $videoRelative = config('publishing.video_dir').'/'.Str::uuid()->toString().'.mp4';

        $subtitleAbs = $tilawa->subtitle_path ? $disk->path($tilawa->subtitle_path) : null;
        if ($subtitleAbs !== null && ! is_file($subtitleAbs)) {
            $subtitleAbs = null;
        }

        try {
            $command = $this->buildCommand(
                $audioAbs,
                $photoAbs,
                $disk->path($overlayRelative),
                $textRelative ? $disk->path($textRelative) : null,
                $subtitleAbs,
                $disk->path($videoRelative),
                $data['animate_photo'],
            );

            $result = Process::timeout((int) config('publishing.render_timeout'))->run($command);

            if (! $result->successful() || ! $disk->exists($videoRelative)) {
                throw new RuntimeException('ffmpeg card video render failed: '.trim($result->errorOutput() ?: $result->output()));
            }
        } finally {
            $disk->delete(array_filter([$overlayRelative, $textRelative]));
        }

        return ['video' => $videoRelative, 'card_image' => $cardRelative];
    }

    /**
     * The photo and text layers are motion inputs, everything else is the
     * static card overlay; input order is audio, black base, then the layers.
     *
     * @return list<string>
     */
    public function buildCommand(string $audioAbs, ?string $photoAbs, string $overlayAbs, ?string $textAbs, ?string $subtitleAbs, string $outAbs, bool $animatePhoto): array
    {
        $w = (int) config('publishing.video.width');
        $h = (int) config('publishing.video.height');
        $fps = (int) config('publishing.video.fps');

        $args = [
            config('youtube.ffmpeg_path'), '-y',
            '-i', $audioAbs,
            '-f', 'lavfi', '-i', "color=c=black:s={$w}x{$h}:r={$fps}",
        ];

        foreach (array_filter([$photoAbs, $overlayAbs, $textAbs]) as $imageAbs) {
            array_push($args, '-loop', '1', '-framerate', (string) $fps, '-i', $imageAbs);
        }

        return array_merge($args, [
            '-filter_complex', $this->filterComplex($photoAbs !== null, $animatePhoto, $textAbs !== null, $subtitleAbs),
            '-map', '[v]',
            '-map', '0:a',
            '-c:v', 'libx264',
            '-preset', (string) config('publishing.video.preset'),
            '-r', (string) $fps,
            '-c:a', 'aac',
            '-b:a', '192k',
            '-shortest',
            $outAbs,
        ]);
    }

    /**
     * The compositing graph. The photo is contained inside the card's photo
     * window (right 48%, above the footer) and, when animated, drifts on a
     * slow sine wave; the text layer rises into place with an alpha fade-in
     * during the first seconds, mirroring the editor's CSS preview.
     */
    public function filterComplex(bool $hasPhoto, bool $animatePhoto, bool $hasText, ?string $subtitleAbs): string
    {
        $w = (int) config('publishing.video.width');
        $h = (int) config('publishing.video.height');

        $regionX = (int) round($w * 0.52);
        $regionW = $w - $regionX;
        $regionH = $h - (int) round($h * 0.1);

        $drift = (int) round($h * (float) config('publishing.animation.photo_drift'));
        $period = (float) config('publishing.animation.photo_period');
        $rise = (int) round($h * (float) config('publishing.animation.text_rise'));
        $riseSeconds = (float) config('publishing.animation.text_seconds');

        $photoIndex = 2;
        $overlayIndex = $hasPhoto ? 3 : 2;
        $textIndex = $overlayIndex + 1;

        $filters = [];
        $base = '[1:v]';

        if ($hasPhoto) {
            $filters[] = "[{$photoIndex}:v]scale={$regionW}:{$regionH}:force_original_aspect_ratio=decrease[ph]";

            $y = $animatePhoto
                ? "({$regionH}-h)/2+{$drift}*sin(2*PI*t/{$period})"
                : "({$regionH}-h)/2";

            $filters[] = "{$base}[ph]overlay=x='{$regionX}+({$regionW}-w)/2':y='{$y}'[card]";
            $base = '[card]';
        }

        $filters[] = "{$base}[{$overlayIndex}:v]overlay=0:0[framed]";
        $base = '[framed]';

        if ($hasText) {
            $filters[] = "[{$textIndex}:v]format=rgba,fade=t=in:st=0.3:d=2:alpha=1[txt]";
            $filters[] = "{$base}[txt]overlay=x=0:y='if(lt(t,{$riseSeconds}),pow(1-t/{$riseSeconds},2)*{$rise},0)'[texted]";
            $base = '[texted]';
        }

        if ($subtitleAbs !== null) {
            $escaped = str_replace([':', '\\'], ['\\:', '/'], $subtitleAbs);
            $style = config('publishing.subtitles.burn_style');
            $filters[] = "{$base}subtitles='{$escaped}':force_style='{$style}'[subbed]";
            $base = '[subbed]';
        }

        $filters[] = "{$base}format=yuv420p[v]";

        return implode(';', $filters);
    }
}
