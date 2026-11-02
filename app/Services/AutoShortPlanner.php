<?php

namespace App\Services;

use App\Models\Tilawa;
use Illuminate\Support\Facades\Storage;

class AutoShortPlanner
{
    /**
     * Audio kept before the first word and after the last one, so the clip
     * neither clips a breath nor cuts a held note.
     */
    private const LEAD_IN = 0.4;

    private const TAIL = 1.2;

    /**
     * How long a cue stays on screen after its words end while the reciter
     * pauses (capped by the next cue).
     */
    private const HOLD = 6.0;

    /**
     * Silences longer than this inside a clip leave the screen without
     * subtitles too long, so passages with them score lower.
     */
    private const LONG_GAP = 8.0;

    public function __construct(private RecitationTranscriber $transcriber) {}

    /**
     * Transcribe a window of the recitation, align it to the Quran text and
     * pick the best passage in it for a short.
     *
     * @return array{start: float, duration: int, surah: ?int, cues: list<array{start: float, end: float, text: string}>}|null
     */
    public function plan(Tilawa $tilawa, float $windowStart): ?array
    {
        $total = (float) $tilawa->duration;
        $window = min((float) config('clips.auto.scan_window'), max(0.0, $total - $windowStart));

        if ($window <= 0) {
            return null;
        }

        $phrases = $this->transcriber->transcribe(
            Storage::disk(config('clips.disk'))->path($tilawa->audio_path),
            $windowStart,
            $window,
        );

        $surahs = array_values(array_map('intval', $tilawa->surahs ?: array_filter([$tilawa->surah_number])));
        $aligner = new QuranPhraseAligner((float) config('clips.auto.min_match_score'), (int) config('clips.auto.max_cue_words'));

        return $this->choose(
            $aligner->align($surahs, $phrases),
            $windowStart,
            $windowStart + $window,
            $windowStart + $window >= $total - 0.5,
        );
    }

    /**
     * Choose the passage that best makes a short: it should start where an
     * ayah starts and end where one ends, run close to the target length and
     * keep subtitles on screen. It starts and ends on matched phrases and
     * never runs across a barrier (speech that is not in the surah).
     * Segments touching the window edges are only used when they cannot have
     * been cut off.
     *
     * @param  list<array{start: float, end: float, score: float, matched?: bool, barrier?: bool, surah: ?int, opens: bool, closes: bool, cues: list<array{start: float, end: float, text: string}>}>  $segments
     * @return array{start: float, duration: int, surah: ?int, cues: list<array{start: float, end: float, text: string}>}|null
     */
    public function choose(array $segments, float $windowStart, float $windowEnd, bool $windowReachesEnd = false): ?array
    {
        $count = count($segments);
        $maxLength = (float) config('clips.max_duration') - 0.5;
        $minLength = (float) config('clips.auto.min_duration');
        $target = (float) config('clips.auto.target_duration');
        $best = null;

        for ($i = 0; $i < $count; $i++) {
            if (! ($segments[$i]['matched'] ?? true)) {
                continue;
            }

            if ($i === 0 && $windowStart > 0 && $segments[0]['start'] - $windowStart < 0.8) {
                continue;
            }

            $clipStart = max($windowStart, $segments[$i]['start'] - self::LEAD_IN, $i > 0 ? $segments[$i - 1]['end'] + 0.15 : $windowStart);
            $gapPenalty = 0.0;

            for ($j = $i; $j < $count; $j++) {
                if ($segments[$j]['barrier'] ?? false) {
                    break;
                }

                if ($j > $i && $segments[$j]['start'] - $segments[$j - 1]['end'] > self::LONG_GAP) {
                    $gapPenalty += 1.5;
                }

                $isLast = $j === $count - 1;

                if (! ($segments[$j]['matched'] ?? true)) {
                    continue;
                }

                if ($isLast && ! $windowReachesEnd && $windowEnd - $segments[$j]['end'] < 1.2) {
                    continue;
                }

                $clipEnd = min($segments[$j]['end'] + self::TAIL, $isLast ? $windowEnd : $segments[$j + 1]['start'] - 0.15);
                $length = $clipEnd - $clipStart;

                if ($length > $maxLength) {
                    break;
                }

                if ($length < $minLength) {
                    continue;
                }

                $score = ($segments[$i]['opens'] ? 3 : 0)
                    + ($segments[$j]['closes'] ? 3 : 0)
                    + 4 * min(1.0, $length / $target)
                    - $gapPenalty;

                if ($best === null || $score > $best['score']) {
                    $best = ['score' => $score, 'from' => $i, 'to' => $j, 'start' => $clipStart, 'length' => $length];
                }
            }
        }

        if ($best === null) {
            return null;
        }

        $chosen = array_slice($segments, $best['from'], $best['to'] - $best['from'] + 1);
        $duration = (int) floor($best['length']);
        $surahs = array_values(array_filter(array_column($chosen, 'surah')));

        return [
            'start' => round($best['start'], 2),
            'duration' => $duration,
            'surah' => $surahs[0] ?? null,
            'cues' => $this->cues($chosen, $best['start'], $duration),
        ];
    }

    /**
     * Flatten the passage's cues onto the clip timeline, holding each one on
     * screen through the pause after it.
     *
     * @param  list<array{cues: list<array{start: float, end: float, text: string}>}>  $segments
     * @return list<array{start: float, end: float, text: string}>
     */
    private function cues(array $segments, float $offset, int $duration): array
    {
        $cues = [];

        foreach ($segments as $segment) {
            foreach ($segment['cues'] as $cue) {
                $cues[] = [
                    'start' => max(0.0, $cue['start'] - $offset),
                    'end' => $cue['end'] - $offset,
                    'text' => $cue['text'],
                ];
            }
        }

        foreach ($cues as $index => $cue) {
            $limit = isset($cues[$index + 1]) ? $cues[$index + 1]['start'] - 0.05 : (float) $duration;
            $cues[$index]['start'] = round($cue['start'], 2);
            $cues[$index]['end'] = round(max($cue['start'] + 0.3, min($cue['end'] + self::HOLD, $limit)), 2);
        }

        return array_values(array_filter($cues, fn (array $cue) => $cue['start'] < $duration));
    }
}
