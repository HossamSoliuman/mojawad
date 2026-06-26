<?php

namespace App\Services;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class TiktokImportService
{
    /**
     * Validate that a URL points at a TikTok video (full, short or share link).
     */
    public function isValidUrl(string $url): bool
    {
        $url = trim($url);

        $patterns = [
            '~^https?://(?:www\.|m\.)?tiktok\.com/@[\w.-]+/video/\d+~i',
            '~^https?://(?:www\.|m\.)?tiktok\.com/t/[\w-]+~i',
            '~^https?://(?:vm|vt)\.tiktok\.com/[\w-]+~i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse a textarea/file blob into a de-duplicated list of valid TikTok URLs.
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
                $urls[$line] = $line;
            }
        }

        return array_values($urls);
    }

    /**
     * Download the TikTok video as mp4 and store it on the public disk under
     * shorts/, mirroring a manual upload. Returns the stored relative path plus
     * the video's caption/title (when yt-dlp can resolve one) so the short can
     * be named automatically.
     *
     * @return array{path: string, title: ?string}
     */
    public function download(string $url): array
    {
        if (! $this->isValidUrl($url)) {
            throw new RuntimeException("Not a valid TikTok video URL: {$url}");
        }

        $workDir = storage_path('app/tiktok-import/'.Str::random(20));
        if (! is_dir($workDir) && ! mkdir($workDir, 0775, true) && ! is_dir($workDir)) {
            throw new RuntimeException("Unable to create temp directory: {$workDir}");
        }

        try {
            $args = [
                config('youtube.ytdlp_path'),
                '--no-playlist',
                '--no-warnings',
                '--no-part',
                '--write-info-json',
                '--retries', '5',
                '--fragment-retries', '5',
                '--remux-video', 'mp4',
                '--ffmpeg-location', config('youtube.ffmpeg_path'),
            ];

            $cookiesFile = config('youtube.tiktok_cookies_file');
            $cookiesBrowser = config('youtube.tiktok_cookies_browser');
            if ($cookiesFile && is_file($cookiesFile)) {
                $args[] = '--cookies';
                $args[] = $cookiesFile;
            } elseif ($cookiesBrowser) {
                $args[] = '--cookies-from-browser';
                $args[] = $cookiesBrowser;
            }

            $args[] = '-o';
            $args[] = $workDir.'/video.%(ext)s';
            $args[] = $url;

            $result = Process::timeout(config('youtube.download_timeout'))->run($args);

            if (! $result->successful()) {
                throw new RuntimeException('yt-dlp download failed: '.trim($result->errorOutput() ?: $result->output()));
            }

            $mp4Path = $workDir.'/video.mp4';
            if (! file_exists($mp4Path)) {
                throw new RuntimeException('TikTok download produced no mp4 file.');
            }

            $filename = Str::random(32).'.mp4';

            return [
                'path' => Storage::disk('public')->putFileAs('shorts', new File($mp4Path), $filename),
                'title' => $this->readTitle($workDir.'/video.info.json'),
            ];
        } finally {
            $this->cleanup($workDir);
        }
    }

    /**
     * Pull the caption/title out of the yt-dlp info JSON, if one was written.
     */
    private function readTitle(string $infoPath): ?string
    {
        if (! is_file($infoPath)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($infoPath), true);

        if (! is_array($data)) {
            return null;
        }

        $title = trim((string) ($data['title'] ?? ''));

        return $title !== '' ? Str::limit($title, 250, '') : null;
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
