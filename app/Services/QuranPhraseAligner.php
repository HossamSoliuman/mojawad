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
     * An unmatched phrase with at least this many letters is real speech that
     * is not in these surahs (another surah, a misheard line), so a short must
     * not run across it. Shorter ones (a takbir, a cheer) may sit inside one.
     */
    private const BARRIER_LETTERS = 10;

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
     * Map timed transcript phrases of a recitation onto the exact Uthmani
     * text of its surahs. The transcript only anchors timing; every displayed
     * word comes from the corpus. Repeated passages (common in mujawwad) match
     * the same text again. Phrases that match nothing come back unmatched,
     * without cues, and long ones are flagged as barriers.
     *
     * @param  list<int>  $surahs
     * @param  list<array{start: float, end: float, text: string, pauses?: list<float>}>  $phrases
     * @return list<array{start: float, end: float, score: float, matched: bool, barrier: bool, surah: ?int, opens: bool, closes: bool, cues: list<array{start: float, end: float, text: string}>}>
     */
    public function align(array $surahs, array $phrases): array
    {
        $tokens = $this->corpusTokens($surahs);
        $formulas = [self::ISTIADHA, self::SADAQA];

        if (! in_array(1, $surahs, true)) {
            $formulas[] = self::BASMALA;
        }

        $matches = [];
        $expected = null;

        foreach ($phrases as $phrase) {
            $words = array_values(array_filter(
                array_map(ArabicSkeleton::of(...), preg_split('/\s+/u', trim($phrase['text'])) ?: []),
                fn (string $key) => $key !== '',
            ));
            $key = implode('', $words);

            if ($key === '') {
                continue;
            }

            $span = $tokens === [] ? null : $this->bestSpan($tokens, $key, count($words), $expected);
            $formula = $this->bestFormula($formulas, $key);

            if ($formula !== null && ($span === null || $formula['score'] >= $span['score'])) {
                $matches[] = ['phrase' => $phrase, 'formula' => $formula['text'], 'score' => $formula['score']];

                continue;
            }

            if ($span === null || $span['score'] < $this->minScore) {
                $matches[] = ['phrase' => $phrase, 'unmatched' => strlen($key) >= self::BARRIER_LETTERS, 'score' => $span['score'] ?? 0.0];

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
     * @return array{start: float, end: float, score: float, matched: bool, barrier: bool, surah: ?int, opens: bool, closes: bool, cues: list<array{start: float, end: float, text: string}>}
     */
    private function segment(array $match, array $tokens): array
    {
        $start = round((float) $match['phrase']['start'], 2);
        $end = round((float) $match['phrase']['end'], 2);

        if (isset($match['unmatched'])) {
            return [
                'start' => $start,
                'end' => $end,
                'score' => round($match['score'], 3),
                'matched' => false,
                'barrier' => $match['unmatched'],
                'surah' => null,
                'opens' => false,
                'closes' => false,
                'cues' => [],
            ];
        }

        if (isset($match['formula'])) {
            return [
                'start' => $start,
                'end' => $end,
                'score' => round($match['score'], 3),
                'matched' => true,
                'barrier' => false,
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
            'matched' => true,
            'barrier' => false,
            'surah' => $words[0]['surah'],
            'opens' => $words[0]['first'],
            'closes' => end($words)['last'],
            'cues' => $this->cues($words, $start, $end, $match['phrase']['pauses'] ?? []),
        ];
    }

    /**
     * Split a matched span into on-screen cues: at every ayah end, then into
     * chunks of at most maxCueWords words, breaking at a waqf sign when one is
     * near. Time is shared by the letters in each chunk and every boundary is
     * moved onto a pause the reciter took nearby.
     *
     * @param  list<array{text: string, key: string, surah: int, ayah: int, first: bool, last: bool}>  $words
     * @param  list<float>  $pauses
     * @return list<array{start: float, end: float, text: string}>
     */
    private function cues(array $words, float $start, float $end, array $pauses = []): array
    {
        $chunks = [];
        $unit = [];

        foreach ($words as $index => $word) {
            $unit[] = $word;

            if ($word['last'] || $index === count($words) - 1) {
                array_push($chunks, ...$this->chunk($unit));
                $unit = [];
            }
        }

        $weights = array_map(fn (array $chunk) => max(1, array_sum(array_map(fn ($w) => strlen($w['key']), $chunk))), $chunks);
        $total = array_sum($weights);
        $boundaries = [$start];
        $cursor = $start;

        foreach (array_slice($weights, 0, -1) as $weight) {
            $cursor += ($end - $start) * $weight / $total;
            $boundaries[] = $this->nearestPause($pauses, $cursor, end($boundaries) + 0.5, $end - 0.5) ?? $cursor;
        }

        $boundaries[] = $end;
        $cues = [];

        foreach ($chunks as $index => $chunk) {
            $cues[] = [
                'start' => round($boundaries[$index], 2),
                'end' => round($boundaries[$index + 1], 2),
                'text' => $this->display($chunk),
            ];
        }

        return $cues;
    }

    /**
     * Break a run of words into chunks of at most maxCueWords, preferring to
     * break after a word that carries a waqf (pause) sign.
     *
     * @param  list<array{text: string, key: string, surah: int, ayah: int, first: bool, last: bool}>  $words
     * @return list<list<array{text: string, key: string, surah: int, ayah: int, first: bool, last: bool}>>
     */
    private function chunk(array $words): array
    {
        $count = count($words);
        $parts = (int) ceil($count / $this->maxCueWords);

        if ($parts <= 1) {
            return [$words];
        }

        $cuts = [0];

        for ($part = 1; $part < $parts; $part++) {
            $target = (int) round($part * $count / $parts);
            $cut = $target;

            foreach ([0, -1, 1, -2, 2] as $shift) {
                $after = $target + $shift - 1;

                if ($after >= end($cuts) && $after < $count - 1 && preg_match('/[\x{06D6}-\x{06DC}]/u', $words[$after]['text'])) {
                    $cut = $after + 1;
                    break;
                }
            }

            $cuts[] = $cut;
        }

        $cuts[] = $count;
        $chunks = [];

        for ($k = 0; $k < count($cuts) - 1; $k++) {
            if ($cuts[$k + 1] > $cuts[$k]) {
                $chunks[] = array_slice($words, $cuts[$k], $cuts[$k + 1] - $cuts[$k]);
            }
        }

        return $chunks;
    }

    /**
     * @param  list<float>  $pauses
     */
    private function nearestPause(array $pauses, float $target, float $after, float $before): ?float
    {
        $best = null;

        foreach ($pauses as $pause) {
            if ($pause <= $after || $pause >= $before || abs($pause - $target) > 3.0) {
                continue;
            }

            if ($best === null || abs($pause - $target) < abs($best - $target)) {
                $best = $pause;
            }
        }

        return $best;
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
