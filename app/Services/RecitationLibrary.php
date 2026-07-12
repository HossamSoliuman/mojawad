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
     * Best-effort surah number parsed from a recitation file name — the first
     * surah the file covers, or null when nothing matches.
     */
    public static function guessSurahNumber(string $filename): ?int
    {
        return self::parse($filename)['surahs'][0] ?? null;
    }

    /**
     * Parse a recitation file name into the surah(s) it covers and, for a file
     * that is one cut of a longer surah, its part number. A file may name more
     * than one surah ("ما تيسر من سورة التوبة ويونس") or repeat a surah across
     * numbered files ("سورة البقرة 1", "سورة البقرة 2").
     *
     * @return array{surahs: list<int>, part: ?int}
     */
    public static function parse(string $filename): array
    {
        $haystack = self::normalize(pathinfo($filename, PATHINFO_FILENAME));

        // Peel the part number off first so a surah name glued to it ("مريم1",
        // "ق1") is still matched as a whole word once the digits are removed.
        [$haystack, $part] = self::extractPart($haystack);

        $matches = [];

        foreach (config('surahs', []) as $number => $name) {
            $needle = self::normalize((string) $name);

            if ($needle === '') {
                continue;
            }

            $found = self::wordMatch($haystack, $needle);

            if ($found !== null) {
                $matches[] = ['number' => (int) $number] + $found;
            }
        }

        return [
            'surahs' => self::resolveOverlaps($matches),
            'part' => $part,
        ];
    }

    /**
     * Locate a surah name as a whole word, tolerating a leading "و" conjunction
     * so the second surah in "التوبة ويونس" is still found. Byte offsets are
     * enough to order and de-overlap the matches.
     *
     * @return array{pos: int, len: int}|null
     */
    private static function wordMatch(string $haystack, string $needle): ?array
    {
        $pattern = '/(?:^|[\s\-_])و?('.preg_quote($needle, '/').')(?=$|[\s\-_])/u';

        if (preg_match($pattern, $haystack, $m, PREG_OFFSET_CAPTURE)) {
            return ['pos' => $m[1][1], 'len' => strlen($m[1][0])];
        }

        return null;
    }

    /**
     * Order matches by position and drop any whose span sits inside a longer
     * match, so a shorter surah name that overlaps a longer one is not counted.
     *
     * @param  list<array{number: int, pos: int, len: int}>  $matches
     * @return list<int>
     */
    private static function resolveOverlaps(array $matches): array
    {
        usort($matches, fn (array $a, array $b): int => $a['pos'] <=> $b['pos'] ?: $b['len'] <=> $a['len']);

        $kept = [];

        foreach ($matches as $match) {
            $end = $match['pos'] + $match['len'];

            foreach ($kept as $existing) {
                if ($match['pos'] < $existing['pos'] + $existing['len'] && $end > $existing['pos']) {
                    continue 2;
                }
            }

            $kept[] = $match;
        }

        return array_values(array_map(fn (array $match): int => $match['number'], $kept));
    }

    /**
     * Split a trailing part number ("البقرة 1", "مريم1") off the name, returning
     * the remaining text and the part. Ayah ranges like "59-73" are left intact
     * by refusing a value that follows another digit or a dash.
     *
     * @return array{0: string, 1: ?int}
     */
    private static function extractPart(string $haystack): array
    {
        $haystack = strtr($haystack, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);

        if (preg_match('/(?<![\d\-])(\d{1,2})\s*$/u', $haystack, $m, PREG_OFFSET_CAPTURE)) {
            $part = (int) $m[1][0];

            if ($part >= 1 && $part <= 20) {
                return [trim(substr($haystack, 0, $m[1][1])), $part];
            }
        }

        return [$haystack, null];
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
