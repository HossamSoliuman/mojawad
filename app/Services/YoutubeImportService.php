<?php

namespace App\Services;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class YoutubeImportService
{
    /**
     * Validate that a URL points at a single YouTube video and return its id.
     */
    public function extractVideoId(string $url): ?string
    {
        $url = trim($url);

        $patterns = [
            '~^https?://(?:www\.|m\.|music\.)?youtube\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})~i',
            '~^https?://(?:www\.)?youtube\.com/shorts/([A-Za-z0-9_-]{11})~i',
            '~^https?://(?:www\.)?youtube\.com/embed/([A-Za-z0-9_-]{11})~i',
            '~^https?://youtu\.be/([A-Za-z0-9_-]{11})~i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }

    public function isValidUrl(string $url): bool
    {
        return $this->extractVideoId($url) !== null;
    }

    /**
     * Parse a textarea/file blob into a de-duplicated list of valid YouTube URLs.
     *
     * @return list<string>
     */
    public function parseUrls(string $blob): array
    {
        $lines = preg_split('/[\r\n,]+/', $blob) ?: [];

        $urls = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '' && $this->isValidUrl($line)) {
                $videoId = $this->extractVideoId($line);
                $urls[$videoId] = $line;
            }
        }

        return array_values($urls);
    }

    /**
     * Fetch metadata for a video without downloading it.
     *
     * @return array{video_id: string, title: string, duration: int, thumbnail: ?string}
     */
    public function probe(string $url): array
    {
        if (! $this->isValidUrl($url)) {
            throw new RuntimeException("Not a valid YouTube video URL: {$url}");
        }

        $result = Process::timeout(config('youtube.probe_timeout'))
            ->run([
                config('youtube.ytdlp_path'),
                '--no-playlist',
                '--no-warnings',
                '--dump-json',
                $url,
            ]);

        if (! $result->successful()) {
            throw new RuntimeException('yt-dlp probe failed: '.trim($result->errorOutput() ?: $result->output()));
        }

        $data = json_decode($result->output(), true);

        if (! is_array($data) || empty($data['id'])) {
            throw new RuntimeException("Could not read video metadata for {$url}.");
        }

        return [
            'video_id' => (string) $data['id'],
            'title' => (string) ($data['title'] ?? $data['id']),
            'duration' => (int) round((float) ($data['duration'] ?? 0)),
            'thumbnail' => isset($data['thumbnail']) ? (string) $data['thumbnail'] : null,
        ];
    }

    /**
     * Download the audio, transcode to mp3 and store it on the public disk
     * under tilawat/, exactly like a manual upload. Returns the relative path.
     */
    public function download(string $url): string
    {
        if (! $this->isValidUrl($url)) {
            throw new RuntimeException("Not a valid YouTube video URL: {$url}");
        }

        $workDir = storage_path('app/youtube-import/'.Str::random(20));
        if (! is_dir($workDir) && ! mkdir($workDir, 0775, true) && ! is_dir($workDir)) {
            throw new RuntimeException("Unable to create temp directory: {$workDir}");
        }

        try {
            $result = Process::timeout(config('youtube.download_timeout'))
                ->run([
                    config('youtube.ytdlp_path'),
                    '--no-playlist',
                    '--no-warnings',
                    '--no-part',
                    '-x',
                    '--audio-format', 'mp3',
                    '--ffmpeg-location', config('youtube.ffmpeg_path'),
                    '-o', $workDir.'/audio.%(ext)s',
                    $url,
                ]);

            if (! $result->successful()) {
                throw new RuntimeException('yt-dlp download failed: '.trim($result->errorOutput() ?: $result->output()));
            }

            $mp3Path = $workDir.'/audio.mp3';
            if (! file_exists($mp3Path)) {
                throw new RuntimeException('Audio extraction produced no mp3 file.');
            }

            $filename = Str::random(32).'.mp3';

            return Storage::disk('public')->putFileAs('tilawat', new File($mp3Path), $filename);
        } finally {
            $this->cleanup($workDir);
        }
    }

    private function cleanup(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }
}
