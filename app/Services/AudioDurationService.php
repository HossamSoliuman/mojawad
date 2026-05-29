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
            $absolutePath = Storage::disk('public')->path($path);

            if (! file_exists($absolutePath)) {
                Log::error("[AudioDurationService] File not found: {$absolutePath}");

                return 0;
            }

            $getID3 = new getID3;
            $fileInfo = $getID3->analyze($absolutePath);

            if (! empty($fileInfo['error'])) {
                Log::warning("[AudioDurationService] getID3 errors for {$path}: ".implode(' | ', $fileInfo['error']));
            }

            $seconds = (int) round((float) ($fileInfo['playtime_seconds'] ?? 0));

            if ($seconds > 0) {
                Log::info("[AudioDurationService] {$path} → {$seconds}s");
            } else {
                Log::warning("[AudioDurationService] Duration came back 0 for {$path}");
            }

            return $seconds;
        } catch (\Throwable $e) {
            Log::error('[AudioDurationService] Exception: '.$e->getMessage());

            return 0;
        }
    }
}
