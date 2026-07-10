<?php

namespace App\Services;

use App\Models\Tilawa;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AudioMasteringService
{
    /**
     * Produce a separately mastered, tagged copy used only for publishing. The
     * cleaned audio_path is left untouched so the site keeps streaming it.
     *
     * Two-pass loudnorm (measure → apply) avoids the drift single-pass loudnorm
     * has on long files. Then silence-trim + fades, optional intro/outro stinger,
     * and finally ID3 tags + embedded cover — on Spotify/Anghami these tags are
     * the branding.
     */
    public function master(Tilawa $tilawa): string
    {
        $disk = Storage::disk(config('publishing.disk'));
        $inputAbs = $disk->path($tilawa->audio_path);

        if (! is_file($inputAbs)) {
            throw new RuntimeException("Cleaned audio not found for tilawa {$tilawa->id}.");
        }

        $measured = $this->measureLoudness($inputAbs);

        $normalized = $this->tempFile();
        $this->normalize($inputAbs, $normalized, $measured);

        $stitched = $this->applyStingers($normalized);

        $disk->makeDirectory(config('publishing.master_dir'));
        $masterRelative = config('publishing.master_dir').'/'.Str::uuid()->toString().'.mp3';

        $this->tagAndEmbed($stitched, $disk->path($masterRelative), $tilawa);

        $this->cleanup([$normalized, $stitched]);

        return $masterRelative;
    }

    /**
     * @return array<string, string>
     */
    private function measureLoudness(string $inputAbs): array
    {
        $target = config('publishing.loudness');

        $result = Process::timeout((int) config('publishing.render_timeout'))->run([
            config('youtube.ffmpeg_path'),
            '-i', $inputAbs,
            '-af', "loudnorm=I={$target['i']}:TP={$target['tp']}:LRA={$target['lra']}:print_format=json",
            '-f', 'null',
            '-',
        ]);

        $output = $result->errorOutput() ?: $result->output();

        if (preg_match('/\{[^}]*"input_i"[^}]*\}/s', $output, $m)) {
            $json = json_decode($m[0], true);

            if (is_array($json)) {
                return array_map('strval', $json);
            }
        }

        return [];
    }

    /**
     * @param  array<string, string>  $measured
     */
    private function normalize(string $inputAbs, string $outAbs, array $measured): void
    {
        $target = config('publishing.loudness');
        $fade = config('publishing.fade');

        $filters = [];

        if (config('publishing.silence_trim')) {
            $filters[] = 'silenceremove=start_periods=1:start_silence=0.1:start_threshold=-50dB';
            $filters[] = 'areverse';
            $filters[] = 'silenceremove=start_periods=1:start_silence=0.1:start_threshold=-50dB';
            $filters[] = 'areverse';
        }

        $loudnorm = "loudnorm=I={$target['i']}:TP={$target['tp']}:LRA={$target['lra']}";

        if (isset($measured['input_i'], $measured['input_tp'], $measured['input_lra'], $measured['input_thresh'], $measured['target_offset'])) {
            $loudnorm .= ":measured_I={$measured['input_i']}:measured_TP={$measured['input_tp']}"
                .":measured_LRA={$measured['input_lra']}:measured_thresh={$measured['input_thresh']}"
                .":offset={$measured['target_offset']}:linear=true";
        }

        $filters[] = $loudnorm;

        // Fade in at the head; fade out at the tail via reverse trick (no length math).
        $filters[] = "afade=t=in:st=0:d={$fade['in']}";
        $filters[] = 'areverse';
        $filters[] = "afade=t=in:st=0:d={$fade['out']}";
        $filters[] = 'areverse';

        $this->runFfmpeg([
            config('youtube.ffmpeg_path'),
            '-y',
            '-i', $inputAbs,
            '-af', implode(',', $filters),
            '-c:a', 'libmp3lame',
            '-b:a', (string) config('publishing.mp3_bitrate'),
            $outAbs,
        ]);
    }

    private function applyStingers(string $normalized): string
    {
        $intro = config('publishing.intro_audio');
        $outro = config('publishing.outro_audio');

        if (! $intro && ! $outro) {
            return $normalized;
        }

        $inputs = array_values(array_filter([$intro, $normalized, $outro], fn ($p) => $p && is_file($p)));
        $out = $this->tempFile();

        $args = [config('youtube.ffmpeg_path'), '-y'];
        foreach ($inputs as $file) {
            array_push($args, '-i', $file);
        }

        $concat = '';
        foreach (array_keys($inputs) as $i) {
            $concat .= "[{$i}:a]";
        }
        $concat .= 'concat=n='.count($inputs).':v=0:a=1[a]';

        array_push($args, '-filter_complex', $concat, '-map', '[a]', '-c:a', 'libmp3lame', '-b:a', (string) config('publishing.mp3_bitrate'), $out);

        $this->runFfmpeg($args);

        return $out;
    }

    private function tagAndEmbed(string $audioAbs, string $outAbs, Tilawa $tilawa): void
    {
        $id3 = config('publishing.id3');
        $coverAbs = $tilawa->brand_cover_path
            ? Storage::disk(config('publishing.disk'))->path($tilawa->brand_cover_path)
            : null;
        $hasCover = $coverAbs !== null && is_file($coverAbs);

        $args = [config('youtube.ffmpeg_path'), '-y', '-i', $audioAbs];

        if ($hasCover) {
            array_push($args, '-i', $coverAbs);
        }

        array_push($args, '-map', '0:a');

        if ($hasCover) {
            array_push($args, '-map', '1:v');
        }

        array_push($args,
            '-c', 'copy',
            '-id3v2_version', '3',
            '-metadata', 'title='.$tilawa->title_ar,
            '-metadata', 'artist='.($tilawa->qari?->name ?? ''),
            '-metadata', 'album='.($tilawa->qari?->name ?? $id3['album_artist']),
            '-metadata', 'album_artist='.$id3['album_artist'],
            '-metadata', 'genre='.$id3['genre'],
            '-metadata', 'publisher='.$id3['publisher'],
        );

        if ($hasCover) {
            array_push($args, '-metadata:s:v', 'title=Album cover', '-metadata:s:v', 'comment=Cover (front)', '-disposition:v', 'attached_pic');
        }

        $args[] = $outAbs;

        $this->runFfmpeg($args);
    }

    private function tempFile(): string
    {
        return tempnam(sys_get_temp_dir(), 'master').'.mp3';
    }

    /**
     * @param  list<string>  $paths
     */
    private function cleanup(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
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
}
