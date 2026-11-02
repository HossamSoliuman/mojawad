<?php

namespace App\Support;

class ArabicSkeleton
{
    /**
     * Each Arabic consonant mapped to one ASCII byte, so the byte-based
     * levenshtein() compares letters rather than UTF-8 bytes.
     *
     * @var array<string, string>
     */
    private const LETTERS = [
        'ب' => 'b', 'ت' => 't', 'ث' => 'v', 'ج' => 'j', 'ح' => 'H', 'خ' => 'x',
        'د' => 'd', 'ذ' => 'V', 'ر' => 'r', 'ز' => 'z', 'س' => 's', 'ش' => 'c',
        'ص' => 'S', 'ض' => 'D', 'ط' => 'T', 'ظ' => 'Z', 'ع' => 'E', 'غ' => 'g',
        'ف' => 'f', 'ق' => 'q', 'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n',
        'ه' => 'h', 'ة' => 'h', 'و' => 'w', 'ؤ' => 'w', 'ي' => 'y', 'ى' => 'y',
        'ئ' => 'y',
    ];

    /**
     * Reduce Arabic text (Uthmani or plain spelling) to a consonant skeleton:
     * diacritics, Quranic marks, alef forms and hamza are dropped, so the
     * mushaf spelling and a speech transcript of the same words compare equal
     * or nearly equal.
     */
    public static function of(string $text): string
    {
        $skeleton = '';

        foreach (mb_str_split($text) as $char) {
            $skeleton .= self::LETTERS[$char] ?? '';
        }

        return $skeleton;
    }

    /**
     * Similarity of two skeletons in [0, 1], from their edit distance.
     */
    public static function similarity(string $a, string $b): float
    {
        $longest = max(strlen($a), strlen($b));

        if ($longest === 0) {
            return 0.0;
        }

        return 1 - levenshtein($a, $b) / $longest;
    }

    /**
     * Western digits written as Arabic-Indic digits.
     */
    public static function indicDigits(int $number): string
    {
        return strtr((string) $number, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
    }
}
