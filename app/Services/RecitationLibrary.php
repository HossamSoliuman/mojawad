<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecitationLibrary
{
    private const AUDIO_EXTENSIONS = ['mp3', 'ogg', 'wav', 'm4a', 'flac'];

    public function disk(): Filesystem
    {
        return Storage::disk(config('publishing.library_disk'));
    }

    /**
     * Top-level reciter folders that contain at least one audio file.
     *
     * @return list<array{name: string, count: int}>
     */
    public function folders(): array
    {
        $disk = $this->disk();

        return collect($disk->directories())
            ->map(fn (string $dir): array => [
                'name' => $dir,
                'count' => count($this->audioFiles($dir)),
            ])
            ->filter(fn (array $folder): bool => $folder['count'] > 0)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Relative paths of the audio files inside a folder, sorted naturally.
     *
     * @return list<string>
     */
    public function audioFiles(string $folder): array
    {
        return collect($this->disk()->files($folder))
            ->filter(fn (string $path): bool => $this->isAudio($path))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function isAudio(string $path): bool
    {
        return in_array(Str::lower(pathinfo($path, PATHINFO_EXTENSION)), self::AUDIO_EXTENSIONS, true);
    }

    /**
     * Best-effort surah number parsed from a recitation file name by matching
     * the longest surah name that appears as a whole word. Returns null when
     * nothing matches, leaving the surah for ayah detection or manual review.
     */
    public static function guessSurahNumber(string $filename): ?int
    {
        $haystack = self::normalize(pathinfo($filename, PATHINFO_FILENAME));

        $bestNumber = null;
        $bestLength = 0;

        foreach (config('surahs', []) as $number => $name) {
            $needle = self::normalize((string) $name);

            if ($needle === '' || mb_strlen($needle) <= $bestLength) {
                continue;
            }

            if (self::containsWord($haystack, $needle)) {
                $bestNumber = (int) $number;
                $bestLength = mb_strlen($needle);
            }
        }

        return $bestNumber;
    }

    private static function containsWord(string $haystack, string $needle): bool
    {
        return (bool) preg_match(
            '/(?:^|[\s\-_])'.preg_quote($needle, '/').'(?:$|[\s\-_])/u',
            $haystack
        );
    }

    private static function normalize(string $value): string
    {
        $value = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $value) ?? $value;

        $value = strtr($value, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا',
            'ى' => 'ي', 'ة' => 'ه',
        ]);

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
