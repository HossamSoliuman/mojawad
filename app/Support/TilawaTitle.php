<?php

namespace App\Support;

class TilawaTitle
{
    public static function surahOnly(?int $surahNumber): string
    {
        return str_replace(
            ':surah',
            self::surahName($surahNumber),
            (string) config('publishing.title_pattern_surah_only')
        );
    }

    public static function withRange(?int $surahNumber, int $from, int $to): string
    {
        return str_replace(
            [':surah', ':from', ':to'],
            [self::surahName($surahNumber), (string) $from, (string) $to],
            (string) config('publishing.title_pattern')
        );
    }

    private static function surahName(?int $surahNumber): string
    {
        return $surahNumber ? (string) config('surahs.'.$surahNumber) : '';
    }
}
