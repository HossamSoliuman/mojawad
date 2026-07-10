<?php

namespace App\Services;

use App\Models\Tilawa;
use App\Support\QuranCorpus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AyahDetectionService
{
    /**
     * The AI step only runs when an ASR key is configured and the fixed Quran
     * corpus is bundled. Otherwise ingest leaves the ayah range for manual entry.
     */
    public static function enabled(): bool
    {
        $driver = config('publishing.transcription.driver');

        $hasProvider = $driver === 'local' || filled(config('publishing.transcription.api_key'));

        return $hasProvider && QuranCorpus::available();
    }

    /**
     * Detect the ayah range of a recitation whose surah is already known.
     *
     * @return array{from: int, to: int, score: float}
     */
    public function detect(Tilawa $tilawa): array
    {
        $ayat = QuranCorpus::ayat((int) $tilawa->surah_number);

        if ($ayat === []) {
            throw new RuntimeException("No corpus text for surah {$tilawa->surah_number}.");
        }

        $audioAbs = Storage::disk(config('publishing.disk'))->path($tilawa->audio_path);

        if (! is_file($audioAbs)) {
            throw new RuntimeException("Audio not found for tilawa {$tilawa->id}.");
        }

        $window = (int) config('publishing.transcription.window');
        $duration = (int) $tilawa->duration;

        $head = $this->transcribeWindow($audioAbs, 0, $window);
        $tail = $this->transcribeWindow($audioAbs, max(0, $duration - $window), $window);

        return $this->matchRange($ayat, $head, $tail);
    }

    /**
     * Pure corpus matcher — pin the opening phrase to a start ayah and the
     * closing phrase to an end ayah within the one known surah. Isolated from
     * ffmpeg/ASR so it can be unit tested with fixture transcriptions.
     *
     * @param  list<string>  $ayat  the surah's ayat text, in order (index 0 = ayah 1)
     * @return array{from: int, to: int, score: float}
     */
    public function matchRange(array $ayat, string $head, string $tail): array
    {
        $headWords = $this->tokenize($head);
        $tailWords = $this->tokenize($tail);

        $opening = array_slice($headWords, 0, 10);
        $closing = array_slice($tailWords, -10);

        $from = $this->bestMatch($ayat, $opening, true);
        $to = $this->bestMatch($ayat, $closing, false);

        $fromIndex = $from['index'];
        $toIndex = max($to['index'], $fromIndex);

        return [
            'from' => $fromIndex + 1,
            'to' => $toIndex + 1,
            'score' => round(($from['score'] + $to['score']) / 2, 3),
        ];
    }

    /**
     * @param  list<string>  $ayat
     * @param  list<string>  $phrase
     * @return array{index: int, score: float}
     */
    private function bestMatch(array $ayat, array $phrase, bool $fromStart): array
    {
        $best = ['index' => $fromStart ? 0 : count($ayat) - 1, 'score' => 0.0];

        foreach ($ayat as $i => $ayah) {
            $ayahWords = $this->tokenize($ayah);
            $window = $fromStart
                ? array_slice($ayahWords, 0, max(count($phrase), 1))
                : array_slice($ayahWords, -max(count($phrase), 1));

            $score = $this->overlap($phrase, $window);

            if ($score > $best['score']) {
                $best = ['index' => $i, 'score' => $score];
            }
        }

        return $best;
    }

    /**
     * Word recall of $needle against $haystack (both normalized word lists).
     *
     * @param  list<string>  $needle
     * @param  list<string>  $haystack
     */
    private function overlap(array $needle, array $haystack): float
    {
        $needle = array_values(array_unique($needle));

        if ($needle === []) {
            return 0.0;
        }

        $hit = count(array_intersect($needle, array_unique($haystack)));

        return $hit / count($needle);
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        return array_values(array_filter(explode(' ', $this->normalize($text)), fn ($w) => $w !== ''));
    }

    /**
     * Strip diacritics/tatweel and unify alef/hamza/ta-marbuta/ya so a rough
     * transcription and the Uthmani corpus compare on the same footing.
     */
    private function normalize(string $text): string
    {
        // Remove Arabic diacritics (harakat, tanwin, superscript alef) and tatweel.
        $text = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text) ?? $text;

        $text = strtr($text, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ة' => 'ه', 'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي', 'ء' => '',
        ]);

        // Drop anything that is not an Arabic letter or whitespace.
        $text = preg_replace('/[^\x{0621}-\x{064A}\s]/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function transcribeWindow(string $audioAbs, int $start, int $window): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'asr').'.mp3';

        try {
            $extract = Process::timeout((int) config('publishing.render_timeout'))->run([
                config('youtube.ffmpeg_path'),
                '-y',
                '-ss', (string) $start,
                '-t', (string) $window,
                '-i', $audioAbs,
                '-ac', '1',
                '-ar', '16000',
                $temp,
            ]);

            if (! $extract->successful() || ! is_file($temp)) {
                throw new RuntimeException('ffmpeg failed to extract the transcription window.');
            }

            return $this->transcribe($temp);
        } finally {
            if (is_file($temp)) {
                @unlink($temp);
            }
        }
    }

    private function transcribe(string $absPath): string
    {
        if (config('publishing.transcription.driver') === 'local') {
            return '';
        }

        $response = Http::withToken((string) config('publishing.transcription.api_key'))
            ->timeout((int) config('publishing.transcription.timeout'))
            ->attach('file', file_get_contents($absPath) ?: '', basename($absPath))
            ->post(rtrim((string) config('publishing.transcription.base_url'), '/').'/audio/transcriptions', [
                'model' => config('publishing.transcription.model'),
                'language' => 'ar',
                'response_format' => 'text',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('ASR request failed: '.$response->body());
        }

        return trim($response->body());
    }
}
