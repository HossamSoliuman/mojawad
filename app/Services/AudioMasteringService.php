<?php

namespace App\Services;

use App\Models\Tilawa;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AudioMasteringService
{
    /** Anything quieter than this counts as silence when trimming head/tail. */
    private const SILENCE_THRESHOLD = '-50dB';

    /** Shortest run of silence worth trimming, and how much of it is kept. */
    private const SILENCE_MIN_SECONDS = 0.1;

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

        $analysis = $this->analyze($inputAbs);

        $normalized = $this->tempFile();
        $this->normalize($inputAbs, $normalized, $analysis);

        $stitched = $this->applyStingers($normalized);

        $disk->makeDirectory(config('publishing.master_dir'));
        $masterRelative = config('publishing.master_dir').'/'.Str::uuid()->toString().'.mp3';

        $this->tagAndEmbed($stitched, $disk->path($masterRelative), $tilawa);

        $this->cleanup([$normalized, $stitched]);

        return $masterRelative;
    }

    /**
     * One measurement pass answering everything the render pass needs: the
     * loudness stats for two-pass loudnorm, where the head/tail silence ends,
     * and the total duration used to place the fade-out.
     *
     * Both filters only report — they pass the audio through untouched — so a
     * single decode covers both.
     *
     * @return array{loudness: array<string, string>, start: float, end: float|null, duration: float|null}
     */
    public function analyze(string $inputAbs): array
    {
        $target = config('publishing.loudness');

        $result = Process::timeout((int) config('publishing.render_timeout'))->run([
            config('youtube.ffmpeg_path'),
            '-i', $inputAbs,
            '-af', 'silencedetect=n='.self::SILENCE_THRESHOLD.':d='.self::SILENCE_MIN_SECONDS
                .",loudnorm=I={$target['i']}:TP={$target['tp']}:LRA={$target['lra']}:print_format=json",
            '-f', 'null',
            '-',
        ]);

        $output = $result->errorOutput() ?: $result->output();
        $bounds = $this->parseSilenceBounds($output);

        return [
            'loudness' => $this->parseLoudness($output),
            'start' => $bounds['start'],
            'end' => $bounds['end'],
            'duration' => $this->parseDuration($output),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseLoudness(string $output): array
    {
        if (preg_match('/\{[^}]*"input_i"[^}]*\}/s', $output, $m)) {
            $json = json_decode($m[0], true);

            if (is_array($json)) {
                return array_map('strval', $json);
            }
        }

        return [];
    }

    /**
     * Head/tail silence bounds read from silencedetect's stderr lines. Only a
     * silence that opens the file or runs to its end counts — the pauses
     * between ayat in the middle of a recitation must survive untouched. As in
     * the old silenceremove chain, a sliver of the silence is left in place so
     * the fades have something to breathe into.
     *
     * @return array{start: float, end: float|null}
     */
    private function parseSilenceBounds(string $output): array
    {
        preg_match_all('/silence_(start|end):\s*(-?[\d.]+)/', $output, $matches, PREG_SET_ORDER);

        $start = 0.0;
        $end = null;

        $opensFileWithSilence = isset($matches[0], $matches[1])
            && $matches[0][1] === 'start'
            && (float) $matches[0][2] <= self::SILENCE_MIN_SECONDS
            && $matches[1][1] === 'end';

        if ($opensFileWithSilence) {
            $start = max(0.0, (float) $matches[1][2] - self::SILENCE_MIN_SECONDS);
        }

        $last = end($matches);

        if ($last !== false && $last[1] === 'start') {
            $end = (float) $last[2] + self::SILENCE_MIN_SECONDS;
        }

        return $end !== null && $end <= $start
            ? ['start' => 0.0, 'end' => null]
            : ['start' => $start, 'end' => $end];
    }

    private function parseDuration(string $output): ?float
    {
        if (preg_match('/Duration:\s*(\d+):(\d{2}):(\d{2}(?:\.\d+)?)/', $output, $m)) {
            return ((int) $m[1] * 3600) + ((int) $m[2] * 60) + (float) $m[3];
        }

        return null;
    }

    /**
     * @param  array{loudness: array<string, string>, start: float, end: float|null, duration: float|null}  $analysis
     */
    private function normalize(string $inputAbs, string $outAbs, array $analysis): void
    {
        $this->runFfmpeg([
            config('youtube.ffmpeg_path'),
            '-y',
            '-i', $inputAbs,
            '-af', implode(',', $this->normalizeFilters($analysis)),
            '-c:a', 'libmp3lame',
            '-b:a', (string) config('publishing.mp3_bitrate'),
            $outAbs,
        ]);
    }

    /**
     * The render chain. Everything here streams in constant memory: `atrim`
     * cuts the head/tail silence the analysis located and `afade=t=out` is
     * placed by arithmetic. The previous chain reached the same result with
     * four `areverse` passes, but `areverse` has to buffer the whole stream —
     * on a 40-minute recitation that is hundreds of megabytes per pass, and
     * ffmpeg aborted with "Cannot allocate memory".
     *
     * @param  array{loudness: array<string, string>, start: float, end: float|null, duration: float|null}  $analysis
     * @return list<string>
     */
    public function normalizeFilters(array $analysis): array
    {
        $target = config('publishing.loudness');
        $fade = config('publishing.fade');
        $measured = $analysis['loudness'];

        $filters = [];
        $playable = $analysis['duration'];

        $trimming = config('publishing.silence_trim')
            && ($analysis['start'] > 0.0 || $analysis['end'] !== null);

        if ($trimming) {
            $trim = 'atrim=start='.$this->seconds($analysis['start']);

            if ($analysis['end'] !== null) {
                $trim .= ':end='.$this->seconds($analysis['end']);
                $playable = $analysis['end'];
            }

            $filters[] = $trim;
            $filters[] = 'asetpts=N/SR/TB';

            $playable = $playable === null ? null : $playable - $analysis['start'];
        }

        $loudnorm = "loudnorm=I={$target['i']}:TP={$target['tp']}:LRA={$target['lra']}";

        if (isset($measured['input_i'], $measured['input_tp'], $measured['input_lra'], $measured['input_thresh'], $measured['target_offset'])) {
            $loudnorm .= ":measured_I={$measured['input_i']}:measured_TP={$measured['input_tp']}"
                .":measured_LRA={$measured['input_lra']}:measured_thresh={$measured['input_thresh']}"
                .":offset={$measured['target_offset']}:linear=true";
        }

        $filters[] = $loudnorm;
        $filters[] = "afade=t=in:st=0:d={$fade['in']}";

        if ($playable !== null && $playable > (float) $fade['out']) {
            $filters[] = 'afade=t=out:st='.$this->seconds($playable - (float) $fade['out']).":d={$fade['out']}";
        }

        return $filters;
    }

    /**
     * ffmpeg wants a plain decimal, never scientific notation or a comma.
     */
    private function seconds(float $value): string
    {
        return number_format($value, 3, '.', '');
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
            throw new RuntimeException('ffmpeg failed: '.$this->ffmpegError($result));
        }
    }

    /**
     * ffmpeg opens stderr with its build banner and configure flags, so the
     * line that actually explains the failure is at the very end. Keep the
     * tail — brand_error is capped at 1000 characters and the banner alone
     * overruns it, which leaves the admin staring at a build string.
     */
    private function ffmpegError(ProcessResult $result): string
    {
        return trim(substr(trim($result->errorOutput() ?: $result->output()), -800));
    }
}
