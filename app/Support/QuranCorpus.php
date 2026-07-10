<?php

namespace App\Support;

class QuranCorpus
{
    /** @var array<int|string, list<string>>|null */
    private static ?array $corpus = null;

    public static function available(): bool
    {
        return is_file((string) config('publishing.quran_text_path'));
    }

    /**
     * All ayat of a surah, in order (index 0 = ayah 1).
     *
     * @return list<string>
     */
    public static function ayat(int $surah): array
    {
        $data = self::load();

        return array_values($data[$surah] ?? $data[(string) $surah] ?? []);
    }

    /**
     * The ayat text for an inclusive 1-indexed range.
     *
     * @return list<string>
     */
    public static function range(int $surah, int $from, int $to): array
    {
        $ayat = self::ayat($surah);

        if ($ayat === []) {
            return [];
        }

        $from = max(1, $from);
        $to = min(count($ayat), max($from, $to));

        return array_slice($ayat, $from - 1, $to - $from + 1);
    }

    /**
     * @return array<int|string, list<string>>
     */
    private static function load(): array
    {
        if (self::$corpus === null) {
            $path = (string) config('publishing.quran_text_path');
            self::$corpus = is_file($path)
                ? (json_decode((string) file_get_contents($path), true) ?: [])
                : [];
        }

        return self::$corpus;
    }

    public static function flush(): void
    {
        self::$corpus = null;
    }
}
