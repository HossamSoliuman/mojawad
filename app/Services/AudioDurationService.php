<?php

namespace App\Services;

use getID3;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AudioDurationService
{
    public static function getSeconds(string $path): int
    {
        try {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return self::getDurationFromUrl($path);
            }

            $absolutePath = Storage::disk('public')->path($path);

            if (! file_exists($absolutePath)) {
                Log::error("[AudioDurationService] File not found: {$absolutePath}");
                return 0;
            }

            $getID3   = new getID3();
            $fileInfo = $getID3->analyze($absolutePath);

            if (! empty($fileInfo['error'])) {
                Log::warning("[AudioDurationService] getID3 errors for {$path}: " . implode(' | ', $fileInfo['error']));
            }

            $seconds = (int) round((float) ($fileInfo['playtime_seconds'] ?? 0));

            if ($seconds > 0) {
                Log::info("[AudioDurationService] {$path} → {$seconds}s");
            } else {
                Log::warning("[AudioDurationService] Duration came back 0 for {$path}");
            }

            return $seconds;
        } catch (\Throwable $e) {
            Log::error("[AudioDurationService] Exception: " . $e->getMessage());
            return 0;
        }
    }

    private static function getDurationFromUrl(string $url): int
    {
        try {
            $client   = new \GuzzleHttp\Client();
            $response = $client->head($url, [
                'timeout'         => 5,
                'allow_redirects' => true,
            ]);

            $contentLength = $response->getHeader('Content-Length')[0] ?? null;
            if (!$contentLength) {
                Log::warning("[AudioDurationService] No Content-Length header for {$url}");
                return 0;
            }

            $duration = (int) round((int) $contentLength / 16000);
            Log::info("[AudioDurationService] URL {$url} → estimated {$duration}s");

            return $duration;
        } catch (\Throwable $e) {
            Log::warning("[AudioDurationService] Could not get duration from URL: " . $e->getMessage());
            return 0;
        }
    }
}
