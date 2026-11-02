<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Models\VideoClip;
use App\Services\ShortSubtitleWriter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class RenderVideoClip implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $clipId) {}

    public function handle(): void
    {
        $clip = VideoClip::with('tilawa.qari')->find($this->clipId);

        if ($clip === null || $clip->status === 'completed') {
            return;
        }

        $clip->update(['status' => 'processing', 'error' => null]);

        $tilawa = $clip->tilawa;

        if ($tilawa === null) {
            throw new RuntimeException('The tilawa for this clip no longer exists.');
        }

        $disk = Storage::disk(config('clips.disk'));
        $audioPath = $disk->path($tilawa->audio_path);

        if (! is_file($audioPath)) {
            throw new RuntimeException("Source audio not found: {$tilawa->audio_path}");
        }

        $duration = min((int) $clip->clip_duration, (int) config('clips.max_duration'));

        $outputRelative = config('clips.output_dir').'/'.Str::uuid()->toString().'.mp4';
        $posterRelative = config('clips.poster_dir').'/'.Str::uuid()->toString().'.jpg';

        $disk->makeDirectory(config('clips.output_dir'));
        $disk->makeDirectory(config('clips.poster_dir'));

        $outputPath = $disk->path($outputRelative);
        $posterPath = $disk->path($posterRelative);

        if ($clip->template === 'mosque') {
            $this->renderMosque($clip, $tilawa, $audioPath, $outputPath, $duration);
        } else {
            $this->runFfmpeg($this->buildRenderCommand($clip, $tilawa, $audioPath, $outputPath, $duration));
        }

        if (! is_file($outputPath)) {
            throw new RuntimeException('ffmpeg produced no output file.');
        }

        $this->runFfmpeg($this->buildPosterCommand($outputPath, $posterPath, $clip->template === 'mosque' ? min(3, $duration / 3) : 1));

        $clip->update([
            'status' => 'completed',
            'output_path' => $outputRelative,
            'poster_path' => is_file($posterPath) ? $posterRelative : null,
            'duration' => $duration,
            'error' => null,
        ]);
    }

    /**
     * @param  list<string>  $command
     */
    private function runFfmpeg(array $command, ?string $workingDirectory = null): void
    {
        $process = Process::timeout((int) config('clips.render_timeout'));

        if ($workingDirectory !== null) {
            $process = $process->path($workingDirectory);
        }

        $result = $process->run($command);

        if (! $result->successful()) {
            throw new RuntimeException('ffmpeg failed: '.trim($result->errorOutput() ?: $result->output()));
        }
    }

    /**
     * Compose the sharp cover background, scaled to fill the frame (or a solid
     * dark fallback when no cover resolves), with the browser-captured overlay
     * PNG — which carries the legibility gradient and text — and the trimmed,
     * loudness-normalised audio.
     *
     * @return list<string>
     */
    private function buildRenderCommand(VideoClip $clip, Tilawa $tilawa, string $audioPath, string $outputPath, int $duration): array
    {
        $width = (int) config('clips.width');
        $height = (int) config('clips.height');
        $fps = (int) config('clips.fps');

        $overlayPath = $clip->overlay_path
            ? Storage::disk(config('clips.disk'))->path($clip->overlay_path)
            : null;
        $hasOverlay = $overlayPath !== null && is_file($overlayPath);

        $coverPath = $this->resolveCoverPath($tilawa);
        $useCover = $coverPath !== null;

        $args = [
            config('youtube.ffmpeg_path'),
            '-y',
            '-ss', (string) $clip->clip_start,
            '-t', (string) $duration,
            '-i', $audioPath,
        ];

        if ($hasOverlay) {
            array_push($args, '-loop', '1', '-i', $overlayPath);
        }

        if ($useCover) {
            array_push($args, '-loop', '1', '-i', $coverPath);
        }

        $overlayIndex = 1;
        $coverIndex = $hasOverlay ? 2 : 1;

        $filters = [];

        if ($useCover) {
            $filters[] = "[{$coverIndex}:v]scale={$width}:{$height}:force_original_aspect_ratio=increase,crop={$width}:{$height},format=rgba[bg]";
        } else {
            $filters[] = "color=c=0x0b0f14:s={$width}x{$height}:d={$duration}[bg]";
        }

        if ($hasOverlay) {
            $filters[] = "[bg][{$overlayIndex}:v]overlay=0:0[v]";
        } else {
            $filters[] = '[bg]null[v]';
        }

        array_push($args, '-filter_complex', implode(';', $filters));

        return array_merge($args, [
            '-map', '[v]',
            '-map', '0:a',
            '-af', 'loudnorm',
            '-c:v', 'libx264',
            '-preset', 'veryfast',
            '-pix_fmt', 'yuv420p',
            '-r', (string) $fps,
            '-c:a', 'aac',
            '-b:a', '192k',
            '-t', (string) $duration,
            '-shortest',
            $outputPath,
        ]);
    }

    /**
     * The auto-short look: a mosque photo slowly zooming in, darkened for
     * legibility, with the qari, surah and recited ayat burned in by libass
     * from an ASS script. ffmpeg runs inside a scratch directory holding the
     * script and the fonts so the filter needs no escaped absolute paths.
     */
    private function renderMosque(VideoClip $clip, Tilawa $tilawa, string $audioPath, string $outputPath, int $duration): void
    {
        $workDir = storage_path('app/clip-work/'.Str::uuid()->toString());
        File::ensureDirectoryExists($workDir.'/fonts');

        try {
            foreach (File::files(config('clips.fonts_dir')) as $font) {
                if (in_array(strtolower($font->getExtension()), ['ttf', 'otf'], true)) {
                    File::copy($font->getPathname(), $workDir.'/fonts/'.$font->getFilename());
                }
            }

            $surahNumber = (int) ($clip->subtitles['surah'] ?? $tilawa->surah_number);

            File::put($workDir.'/subs.ass', app(ShortSubtitleWriter::class)->ass(
                $clip->subtitles['cues'] ?? [],
                $tilawa->qari?->name_ar ? __('Sheikh :name', ['name' => $tilawa->qari->name_ar], 'ar') : '',
                $surahNumber ? __('Surah :name', ['name' => config('surahs.'.$surahNumber)], 'ar') : '',
                $duration,
            ));

            $this->runFfmpeg($this->buildMosqueCommand($clip, $tilawa, $audioPath, $outputPath, $duration), $workDir);
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    /**
     * @return list<string>
     */
    private function buildMosqueCommand(VideoClip $clip, Tilawa $tilawa, string $audioPath, string $outputPath, int $duration): array
    {
        $width = (int) config('clips.width');
        $height = (int) config('clips.height');
        $fps = (int) config('clips.fps');
        $zoom = (float) config('clips.zoom') - 1;
        $background = $this->resolveBackgroundPath($clip) ?? $this->resolveCoverPath($tilawa);

        $args = [
            config('youtube.ffmpeg_path'),
            '-y',
            '-ss', (string) $clip->clip_start,
            '-t', (string) $duration,
            '-i', $audioPath,
        ];

        $shade = 'eq=brightness=-0.03:saturation=0.95,drawbox=x=0:y=0:w=iw:h=ih:color=black@0.48:t=fill,vignette=PI/4.5';

        if ($background !== null) {
            $coverWidth = (int) (ceil($width * (1 + $zoom) / 2) * 2);
            $coverHeight = (int) (ceil($height * (1 + $zoom) / 2) * 2);

            array_push($args, '-i', $background);
            $base = "[1:v]scale={$coverWidth}:{$coverHeight}:force_original_aspect_ratio=increase,crop={$coverWidth}:{$coverHeight},{$shade},"
                ."loop=loop=-1:size=1:start=0,fps={$fps},"
                ."scale=w='trunc({$width}*(1+{$zoom}*t/{$duration})/2)*2':h='trunc({$height}*(1+{$zoom}*t/{$duration})/2)*2':eval=frame,crop={$width}:{$height}";
        } else {
            $base = "color=c=0x0b0f14:s={$width}x{$height}:r={$fps}:d={$duration}";
        }

        $filter = $base
            .',ass=subs.ass:fontsdir=fonts:shaping=complex'
            .',format=yuv420p[v]';

        $fadeOut = max(0, $duration - 1.5);

        return array_merge($args, [
            '-filter_complex', $filter,
            '-map', '[v]',
            '-map', '0:a',
            '-af', "loudnorm,afade=t=in:d=0.3,afade=t=out:st={$fadeOut}:d=1.5",
            '-c:v', 'libx264',
            '-preset', 'veryfast',
            '-crf', '21',
            '-pix_fmt', 'yuv420p',
            '-r', (string) $fps,
            '-c:a', 'aac',
            '-b:a', '192k',
            '-t', (string) $duration,
            '-movflags', '+faststart',
            '-shortest',
            $outputPath,
        ]);
    }

    /**
     * The clip's chosen background photo, or the first photo of the
     * template's set when none (or a missing one) was stored.
     */
    private function resolveBackgroundPath(VideoClip $clip): ?string
    {
        $directory = config('clips.backgrounds.'.$clip->template);

        if (! is_string($directory) || ! is_dir($directory)) {
            return null;
        }

        if ($clip->background_path && is_file($directory.'/'.basename($clip->background_path))) {
            return $directory.'/'.basename($clip->background_path);
        }

        return collect(File::files($directory))->map(fn ($file) => $file->getPathname())->sort()->first();
    }

    /**
     * @return list<string>
     */
    private function buildPosterCommand(string $outputPath, string $posterPath, float $at = 1): array
    {
        return [
            config('youtube.ffmpeg_path'),
            '-y',
            '-ss', (string) $at,
            '-i', $outputPath,
            '-frames:v', '1',
            '-q:v', '3',
            $posterPath,
        ];
    }

    /**
     * Resolve a local image for the "cover-blur" background, mirroring the
     * fallback chain of Tilawa::cover_url (own cover → qari image → default
     * cover) so the rendered video matches the editor preview. Most tilawat
     * have no own cover_image and rely on the qari image, so checking only
     * cover_image left the render falling back to the waveform. Returns null
     * only when no usable local image exists.
     */
    private function resolveCoverPath(Tilawa $tilawa): ?string
    {
        $disk = Storage::disk(config('clips.disk'));

        $candidates = [];

        if ($tilawa->cover_image) {
            $candidates[] = public_path('storage/'.$tilawa->cover_image);
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

    public function failed(Throwable $exception): void
    {
        VideoClip::find($this->clipId)?->update([
            'status' => 'failed',
            'error' => Str::limit($exception->getMessage(), 1000),
        ]);
    }
}
