<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

class SourceCleanService
{
    /**
     * Strip any foreign branding from an uploaded source and re-encode to a
     * clean, standardized MP3 in one ffmpeg pass:
     *
     *   -map 0:a          keep only audio streams → drops any embedded cover/APIC
     *   -map_metadata -1  drop all ID3 tags left by the original publisher
     *   -vn               guarantee no video/thumbnail survives
     */
    public function clean(string $sourceAbsPath, string $outAbsPath): void
    {
        if (! is_file($sourceAbsPath)) {
            throw new RuntimeException("Source audio not found: {$sourceAbsPath}");
        }

        $this->runFfmpeg([
            config('youtube.ffmpeg_path'),
            '-y',
            '-i', $sourceAbsPath,
            '-map', '0:a',
            '-map_metadata', '-1',
            '-vn',
            '-c:a', 'libmp3lame',
            '-b:a', (string) config('publishing.clean_bitrate'),
            $outAbsPath,
        ]);

        if (! is_file($outAbsPath)) {
            throw new RuntimeException('ffmpeg produced no cleaned output file.');
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
