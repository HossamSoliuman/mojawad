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

    /**
     * Title for a recitation described by one or more surahs. A single surah
     * yields the plain title (optionally tagged with its part number); two or
     * more surahs are listed with the correct Arabic dual/plural wording.
     *
     * @param  list<int>  $surahNumbers  ordered surah numbers the file covers
     */
    public static function forSurahs(array $surahNumbers, ?int $part = null): string
    {
        $numbers = array_values(array_filter(array_map('intval', $surahNumbers)));

        if ($numbers === []) {
            return self::surahOnly(null);
        }

        if (count($numbers) === 1) {
            return self::surahOnly($numbers[0]).self::partSuffix($part);
        }

        $names = self::joinNames(array_map(fn (int $number): string => self::surahName($number), $numbers));

        $pattern = count($numbers) === 2
            ? config('publishing.title_pattern_surahs_dual')
            : config('publishing.title_pattern_surahs');

        return str_replace(':surahs', $names, (string) $pattern).self::partSuffix($part);
    }

    /**
     * Bare surah name(s) for display — a single name, or several joined with the
     * Arabic conjunction. Accepts a surah number, a list of them, or null.
     *
     * @param  int|list<int>|null  $surahs
     */
    public static function surahList(int|array|null $surahs): ?string
    {
        $numbers = array_values(array_filter(array_map('intval', (array) ($surahs ?? []))));

        if ($numbers === []) {
            return null;
        }

        return self::joinNames(array_map(fn (int $number): string => self::surahName($number), $numbers));
    }

    /**
     * Join Arabic names with the "و" conjunction: "التوبة ويونس والنساء".
     *
     * @param  list<string>  $names
     */
    private static function joinNames(array $names): string
    {
        $names = array_values(array_filter($names, fn (string $name): bool => $name !== ''));

        if ($names === []) {
            return '';
        }

        $first = array_shift($names);

        return $first.implode('', array_map(fn (string $name): string => ' و'.$name, $names));
    }

    private static function partSuffix(?int $part): string
    {
        if ($part === null) {
            return '';
        }

        $ordinals = [
            1 => 'الأول', 2 => 'الثاني', 3 => 'الثالث', 4 => 'الرابع', 5 => 'الخامس',
            6 => 'السادس', 7 => 'السابع', 8 => 'الثامن', 9 => 'التاسع', 10 => 'العاشر',
        ];

        $label = $ordinals[$part] ?? 'رقم '.$part;

        return str_replace(':part', $label, (string) config('publishing.title_part_suffix'));
    }

    private static function surahName(?int $surahNumber): string
    {
        return $surahNumber ? (string) config('surahs.'.$surahNumber) : '';
    }
}
