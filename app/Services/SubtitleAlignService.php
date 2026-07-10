<?php

namespace App\Services;

use App\Models\Tilawa;
use App\Support\QuranCorpus;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SubtitleAlignService
{
    /**
     * Forced alignment — NOT transcription. We already have the exact ayat text
     * (from the corpus / detected range); aeneas only finds *when* each ayah is
     * spoken and writes one .vtt cue per ayah. The same timing artifact is reused
     * by the burned-in video, the YouTube caption track, and the website player.
     */
    public static function enabled(): bool
    {
        return (bool) config('publishing.subtitles.enabled') && QuranCorpus::available();
    }

    /**
     * Returns the stored subtitle path, or null when alignment is gated off or
     * the ayah range is not yet known (video then renders without burn-in).
     */
    public function align(Tilawa $tilawa): ?string
    {
        if (! self::enabled() || ! $tilawa->ayah_from || ! $tilawa->ayah_to) {
            return null;
        }

        $ayat = QuranCorpus::range((int) $tilawa->surah_number, (int) $tilawa->ayah_from, (int) $tilawa->ayah_to);

        if ($ayat === []) {
            return null;
        }

        $disk = Storage::disk(config('publishing.disk'));
        $audioAbs = $disk->path($tilawa->master_audio_path ?: $tilawa->audio_path);

        if (! is_file($audioAbs)) {
            throw new RuntimeException("Audio not found for subtitle alignment of tilawa {$tilawa->id}.");
        }

        $textFile = tempnam(sys_get_temp_dir(), 'ayat').'.txt';
        file_put_contents($textFile, implode("\n", $ayat));

        $disk->makeDirectory(config('publishing.subtitles.dir'));
        $vttRelative = config('publishing.subtitles.dir').'/'.Str::uuid()->toString().'.vtt';
        $vttAbs = $disk->path($vttRelative);

        try {
            $result = Process::timeout((int) config('publishing.subtitles.timeout'))->run([
                config('publishing.subtitles.binary'),
                '-m', 'aeneas.tools.execute_task',
                $audioAbs,
                $textFile,
                'task_language=ara|is_text_type=plain|os_task_file_format='.config('publishing.subtitles.format'),
                $vttAbs,
            ]);

            if (! $result->successful() || ! is_file($vttAbs)) {
                throw new RuntimeException('aeneas alignment failed: '.trim($result->errorOutput() ?: $result->output()));
            }
        } finally {
            @unlink($textFile);
        }

        return $vttRelative;
    }
}
