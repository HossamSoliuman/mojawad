<?php

namespace App\Services;

use getID3;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AudioDurationService
{
    /**
     * Returns duration in seconds for a file stored on the public disk.
     * Returns 0 on any failure (never throws).
     */
    public static function getSeconds(string $storagePath): int
    {
        try {
            $absolutePath = Storage::disk('public')->path($storagePath);

            if (! file_exists($absolutePath)) {
                Log::error("[AudioDurationService] File not found: {$absolutePath}");
                return 0;
            }

            $getID3   = new getID3();
            $fileInfo = $getID3->analyze($absolutePath);

            if (! empty($fileInfo['error'])) {
                Log::warning("[AudioDurationService] getID3 errors for {$storagePath}: " . implode(' | ', $fileInfo['error']));
            }

            $seconds = (int) round((float) ($fileInfo['playtime_seconds'] ?? 0));

            if ($seconds > 0) {
                Log::info("[AudioDurationService] {$storagePath} → {$seconds}s");
            } else {
                Log::warning("[AudioDurationService] Duration came back 0 for {$storagePath}");
            }

            return $seconds;
        } catch (\Throwable $e) {
            Log::error("[AudioDurationService] Exception: " . $e->getMessage());
            return 0;
        }
    }
}
