<?php

namespace App\Services;

use App\Support\ArabicSkeleton;
use App\Support\QuranCorpus;

class QuranPhraseAligner
{
    public const ISTIADHA = 'أَعُوذُ بِٱللَّهِ مِنَ ٱلشَّيۡطَٰنِ ٱلرَّجِيمِ';

    public const BASMALA = 'بِسۡمِ ٱللَّهِ ٱلرَّحۡمَٰنِ ٱلرَّحِيمِ';

    public const SADAQA = 'صَدَقَ ٱللَّهُ ٱلۡعَظِيمُ';

    /**
     * A silence at least this long (seconds) between two transcribed words
     * closes a phrase — reciters breathe at a waqf.
     */
    private const PAUSE = 0.5;

    private const MAX_PHRASE_WORDS = 14;

    /**
     * How far (in corpus words) around the expected position a phrase is
     * searched before falling back to the whole text.
     */
    private const NEARBY = 60;

    /**
     * Ornaments that are part of the mushaf layout, not of the recited words.
     */
    private const DISPLAY_STRIP = ['۞', '۩'];

    public function __construct(
        private float $minScore = 0.6,
        private int $maxCueWords = 9,
    ) {}

    /**
     * Map timed transcript words of a recitation onto the exact Uthmani text
     * of its surahs. The transcript only anchors timing; every displayed word
     * comes from the corpus. Repeated passages (common in mujawwad) match
     * the same text again, and phrases that match nothing (crowd, noise) are
     * dropped.
     *
     * @param  list<int>  $surahs
     * @param  list<array{w: string, s: float, e: float}>  $words
     * @return list<array{start: float, end: float, score: float, surah: ?int, opens: bool, closes: bool, cues: list<array{start: float, end: float, text: string}>}>
     */
    public function align(array $surahs, array $words): array
    {
        $tokens = $this->corpusTokens($surahs);
        $formulas = [self::ISTIADHA, self::SADAQA];

        if (! in_array(1, $surahs, true)) {
            $formulas[] = self::BASMALA;
        }

        $matches = [];
        $expected = null;

        foreach ($this->phrases($words) as $phrase) {
            $key = implode('', array_column($phrase, 'key'));
            $span = $tokens === [] ? null : $this->bestSpan($tokens, $key, count($phrase), $expected);
            $formula = $this->bestFormula($formulas, $key);

            if ($formula !== null && ($span === null || $formula['score'] >= $span['score'])) {
                $matches[] = ['phrase' => $phrase, 'formula' => $formula['text'], 'score' => $formula['score']];

                continue;
            }

            if ($span === null || $span['score'] < $this->minScore) {
                continue;
            }

            $matches[] = ['phrase' => $phrase, 'span' => $span, 'score' => $span['score']];
            $expected = $span['to'] + 1;
        }

        $this->resolveTies($matches);

        return array_map(fn (array $match) => $this->segment($match, $tokens), $matches);
    }

    /**
     * The words of the given surahs in recitation order, each surah opened
     * by its basmala (the corpus holds it only as Al-Fatiha's first ayah).
     *
     * @param  list<int>  $surahs
     * @return list<array{text: string, key: string, surah: int, ayah: int, first: bool, last: bool}>
     */
    private function corpusTokens(array $surahs): array
    {
        $tokens = [];

        foreach ($surahs as $surah) {
            if ($surah !== 1 && $surah !== 9) {
                $this->pushAyah($tokens, self::BASMALA, $surah, 0);
            }

            foreach (QuranCorpus::ayat($surah) as $index => $text) {
                $this->pushAyah($tokens, $text, $surah, $index + 1);
            }
        }

        return $tokens;
    }

    /**
     * @param  list<array{text: string, key: string, surah: int, ayah: int, first: bool, last: bool}>  $tokens
     */
    private function pushAyah(array &$tokens, string $text, int $surah, int $ayah): void
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $last = count($words) - 1;

        foreach ($words as $index => $word) {
            $tokens[] = [
                'text' => $word,
                'key' => ArabicSkeleton::of($word),
                'surah' => $surah,
                'ayah' => $ayah,
                'first' => $index === 0,
                'last' => $index === $last,
            ];
        }
    }

    /**
     * Group transcript words into phrases at recitation pauses.
     *
     * @param  list<array{w: string, s: float, e: float}>  $words
     * @return list<list<array{w: string, s: float, e: float, key: string}>>
     */
    private function phrases(array $words): array
    {
        $phrases = [];
        $current = [];

        foreach ($words as $word) {
            $key = ArabicSkeleton::of($word['w']);

            if ($key === '') {
                continue;
            }

            $word['key'] = $key;
            $previous = end($current);

            if ($previous !== false && ($word['s'] - $previous['e'] >= self::PAUSE || count($current) >= self::MAX_PHRASE_WORDS)) {
                $phrases[] = $current;
                $current = [];
            }

            $current[] = $word;
        }

        if ($current !== []) {
            $phrases[] = $current;
        }

        return $phrases;
    }

    /**
     * The corpus span whose skeleton is closest to the phrase. Spans may hold
     * a few words more or fewer than the transcript, since speech-to-text
     * splits and joins words differently from the mushaf. Equal-scoring spans
     * (a refrain recited many times) are kept as alternatives.
     *
     * @param  list<array{text: string, key: string, surah: int, ayah: int, first: bool, last: bool}>  $tokens
     * @return array{from: int, to: int, score: float, ties: list<array{0: int, 1: int}>}|null
     */
    private function bestSpan(array $tokens, string $key, int $wordCount, ?int $expected): ?array
    {
        $count = count($tokens);
        $ranges = [[0, $count - 1]];

        if ($expected !== null) {
            array_unshift($ranges, [max(0, $expected - self::NEARBY), min($count - 1, $expected + self::NEARBY)]);
        }

        $best = null;

        foreach ($ranges as [$from, $to]) {
            $best = $this->searchRange($tokens, $key, $wordCount, $expected, $from, $to);

            if ($best !== null && $best['score'] >= $this->minScore) {
                return $best;
            }
        }

        return $best;
    }

    /**
     * @param  list<array{text: string, key: string, surah: int, ayah: int, first: bool, last: bool}>  $tokens
     * @return array{from: int, to: int, score: float, ties: list<array{0: int, 1: int}>}|null
     */
    private function searchRange(array $tokens, string $key, int $wordCount, ?int $expected, int $from, int $to): ?array
    {
        $count = count($tokens);
        $keyLength = strlen($key);
        $minWords = max(1, $wordCount - 3);
        $maxWords = $wordCount + 3;
        $best = null;

        for ($i = $from; $i <= $to; $i++) {
            $concat = '';

            for ($length = 1; $length <= $maxWords && $i + $length - 1 < $count; $length++) {
                $concat .= $tokens[$i + $length - 1]['key'];

                if ($length < $minWords) {
                    continue;
                }

                if (strlen($concat) > $keyLength * 1.6 + 2) {
                    break;
                }

                $score = ArabicSkeleton::similarity($concat, $key);
                $distance = $this->distance($i, $expected);
                $j = $i + $length - 1;

                if ($best === null || $score > $best['score'] + 1e-9) {
                    $best = ['from' => $i, 'to' => $j, 'score' => $score, 'distance' => $distance, 'ties' => [[$i, $j]]];
                } elseif (abs($score - $best['score']) <= 1e-9) {
                    $best['ties'][] = [$i, $j];

                    if ($distance < $best['distance']) {
                        $best = array_merge($best, ['from' => $i, 'to' => $j, 'distance' => $distance]);
                    }
                }
            }
        }

        if ($best === null) {
            return null;
        }

        unset($best['distance']);

        return $best;
    }

    /**
     * Distance from where the recitation is expected to continue; going back
     * (a repetition) costs more than going forward.
     */
    private function distance(int $index, ?int $expected): int
    {
        if ($expected === null) {
            return 0;
        }

        return $index >= $expected ? $index - $expected : ($expected - $index) * 2;
    }

    /**
     * @param  list<string>  $formulas
     * @return array{text: string, score: float}|null
     */
    private function bestFormula(array $formulas, string $key): ?array
    {
        $best = null;

        foreach ($formulas as $formula) {
            $score = ArabicSkeleton::similarity(ArabicSkeleton::of($formula), $key);

            if ($score >= $this->minScore && ($best === null || $score > $best['score'])) {
                $best = ['text' => $formula, 'score' => $score];
            }
        }

        return $best;
    }

    /**
     * A refrain that occurs many times matches all its occurrences equally;
     * pick the one that sits right before the next matched phrase (or right
     * after the previous one) so the ayah numbers stay in sequence.
     *
     * @param  list<array<string, mixed>>  $matches
     */
    private function resolveTies(array &$matches): void
    {
        foreach ($matches as $index => $match) {
            if (! isset($match['span']) || count($match['span']['ties']) < 2) {
                continue;
            }

            $next = $this->neighbourSpan($matches, $index, 1);
            $previous = $this->neighbourSpan($matches, $index, -1);
            $choice = null;

            foreach ($match['span']['ties'] as [$from, $to]) {
                $gap = match (true) {
                    $next !== null => $to < $next['from'] ? $next['from'] - $to : PHP_INT_MAX,
                    $previous !== null => $from > $previous['to'] ? $from - $previous['to'] : PHP_INT_MAX,
                    default => 0,
                };

                if ($choice === null || $gap < $choice['gap']) {
                    $choice = ['from' => $from, 'to' => $to, 'gap' => $gap];
                }
            }

            if ($choice !== null && $choice['gap'] !== PHP_INT_MAX) {
                $matches[$index]['span']['from'] = $choice['from'];
                $matches[$index]['span']['to'] = $choice['to'];
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $matches
     * @return array{from: int, to: int}|null
     */
    private function neighbourSpan(array $matches, int $index, int $step): ?array
    {
        for ($k = $index + $step; isset($matches[$k]); $k += $step) {
            if (isset($matches[$k]['span']) && count($matches[$k]['span']['ties']) === 1) {
                return $matches[$k]['span'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $match
     * @param  list<array{text: string, key: string, surah: int, ayah: int, first: bool, last: bool}>  $tokens
     * @return array{start: float, end: float, score: float, surah: ?int, opens: bool, closes: bool, cues: list<array{start: float, end: float, text: string}>}
     */
    private function segment(array $match, array $tokens): array
    {
        $phrase = $match['phrase'];
        $start = round((float) $phrase[0]['s'], 2);
        $end = round((float) end($phrase)['e'], 2);

        if (isset($match['formula'])) {
            return [
                'start' => $start,
                'end' => $end,
                'score' => round($match['score'], 3),
                'surah' => null,
                'opens' => true,
                'closes' => true,
                'cues' => [['start' => $start, 'end' => $end, 'text' => $match['formula']]],
            ];
        }

        $span = $match['span'];
        $words = array_slice($tokens, $span['from'], $span['to'] - $span['from'] + 1);

        return [
            'start' => $start,
            'end' => $end,
            'score' => round(min(1.0, $span['score']), 3),
            'surah' => $words[0]['surah'],
            'opens' => $words[0]['first'],
            'closes' => end($words)['last'],
            'cues' => $this->cues($words, $start, $end),
        ];
    }

    /**
     * Split a matched span into on-screen cues of at most maxCueWords words,
     * sharing the phrase's time by the length of each chunk.
     *
     * @param  list<array{text: string, key: string, surah: int, ayah: int, first: bool, last: bool}>  $words
     * @return list<array{start: float, end: float, text: string}>
     */
    private function cues(array $words, float $start, float $end): array
    {
        $chunkCount = (int) ceil(count($words) / $this->maxCueWords);
        $chunks = $chunkCount > 1
            ? array_chunk($words, (int) ceil(count($words) / $chunkCount))
            : [$words];

        $weights = array_map(fn (array $chunk) => max(1, array_sum(array_map(fn ($w) => strlen($w['key']), $chunk))), $chunks);
        $total = array_sum($weights);
        $cursor = $start;
        $cues = [];

        foreach ($chunks as $index => $chunk) {
            $length = ($end - $start) * $weights[$index] / $total;
            $cues[] = [
                'start' => round($cursor, 2),
                'end' => round($cursor + $length, 2),
                'text' => $this->display($chunk),
            ];
            $cursor += $length;
        }

        return $cues;
    }

    /**
     * Mushaf words joined for display, each completed ayah closed by its
     * numbered end-of-ayah sign.
     *
     * @param  list<array{text: string, key: string, surah: int, ayah: int, first: bool, last: bool}>  $words
     */
    private function display(array $words): string
    {
        $parts = [];

        foreach ($words as $word) {
            $parts[] = str_replace(self::DISPLAY_STRIP, '', $word['text']);

            if ($word['last'] && $word['ayah'] > 0) {
                $parts[] = '۝'.ArabicSkeleton::indicDigits($word['ayah']);
            }
        }

        return trim(implode(' ', array_filter($parts, fn (string $part) => $part !== '')));
    }
}
