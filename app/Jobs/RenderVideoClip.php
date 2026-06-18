<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Models\VideoClip;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
        $clip = VideoClip::with('tilawa')->find($this->clipId);

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

        $this->runFfmpeg($this->buildRenderCommand($clip, $tilawa, $audioPath, $outputPath, $duration));

        if (! is_file($outputPath)) {
            throw new RuntimeException('ffmpeg produced no output file.');
        }

        $this->runFfmpeg($this->buildPosterCommand($outputPath, $posterPath));

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
    private function runFfmpeg(array $command): void
    {
        $result = Process::timeout((int) config('clips.render_timeout'))->run($command);

        if (! $result->successful()) {
            throw new RuntimeException('ffmpeg failed: '.trim($result->errorOutput() ?: $result->output()));
        }
    }

    /**
     * Compose the dark background (+ optional waveform / blurred cover) with the
     * browser-captured overlay PNG and the trimmed, loudness-normalised audio.
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
        $useCover = $clip->template === 'cover-blur' && $coverPath !== null;

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
            $filters[] = "[{$coverIndex}:v]scale={$width}:{$height}:force_original_aspect_ratio=increase,crop={$width}:{$height},boxblur=24:2,format=rgba[cover]";
            $filters[] = "color=c=black@0.5:s={$width}x{$height}:d={$duration}[scrim]";
            $filters[] = '[cover][scrim]overlay=0:0[bg]';
        } else {
            $filters[] = "color=c=0x0b0f14:s={$width}x{$height}:d={$duration}[base]";
            $filters[] = "[0:a]showwaves=s={$width}x500:mode=cline:rate={$fps}:colors=0x34d399|0x6ee7b7,format=rgba[wave]";
            $filters[] = '[base][wave]overlay=(W-w)/2:(H-h)/2[bg]';
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
     * @return list<string>
     */
    private function buildPosterCommand(string $outputPath, string $posterPath): array
    {
        return [
            config('youtube.ffmpeg_path'),
            '-y',
            '-ss', '1',
            '-i', $outputPath,
            '-frames:v', '1',
            '-q:v', '3',
            $posterPath,
        ];
    }

    private function resolveCoverPath(Tilawa $tilawa): ?string
    {
        if (! $tilawa->cover_image) {
            return null;
        }

        $path = public_path('storage/'.$tilawa->cover_image);

        return is_file($path) ? $path : null;
    }

    public function failed(Throwable $exception): void
    {
        VideoClip::find($this->clipId)?->update([
            'status' => 'failed',
            'error' => Str::limit($exception->getMessage(), 1000),
        ]);
    }
}
