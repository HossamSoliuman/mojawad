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
        $hasProvider = match (config('publishing.transcription.driver')) {
            'gemini' => filled(config('publishing.transcription.gemini.api_key')),
            default => filled(config('publishing.transcription.api_key')),
        };

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

        $headFile = $this->extractWindow($audioAbs, 0, $window);
        $tailFile = $this->extractWindow($audioAbs, max(0, $duration - $window), $window);

        try {
            [$head, $tail] = $this->transcribeFiles([$headFile, $tailFile]);
        } finally {
            foreach ([$headFile, $tailFile] as $temp) {
                if (is_file($temp)) {
                    @unlink($temp);
                }
            }
        }

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
        $tailWords = $this->stripClosingFormula($this->tokenize($tail));

        $opening = array_slice($headWords, 0, 10);
        $closing = array_slice($tailWords, -10);

        $from = $this->bestMatch($ayat, $opening, true);
        $to = $this->bestMatch($ayat, $closing, false);

        $fromIndex = $from['index'];
        $toIndex = max($to['index'], $fromIndex);

        // Recitation is sequential, so the true end is the last ayah whose text
        // is (almost) wholly present in the tail window. This recovers the final
        // short ayah when the tail spans several ayat (the ending-alignment match
        // above can lock onto an earlier, fuller ayah instead).
        $toIndex = max($toIndex, $this->lastContainedAyah($ayat, $tailWords, $fromIndex));

        return [
            'from' => $fromIndex + 1,
            'to' => $toIndex + 1,
            'score' => round(($from['score'] + $to['score']) / 2, 3),
        ];
    }

    /**
     * Highest-indexed ayah (at or after the start ayah) whose words are at least
     * 80% present in the tail window — i.e. the last ayah the reciter clearly
     * reached. Returns -1 when no ayah is that fully contained.
     *
     * @param  list<string>  $ayat
     * @param  list<string>  $tailWords
     */
    private function lastContainedAyah(array $ayat, array $tailWords, int $fromIndex): int
    {
        $tailSet = array_unique($tailWords);
        $last = -1;

        foreach ($ayat as $i => $ayah) {
            if ($i < $fromIndex) {
                continue;
            }

            $ayahWords = array_values(array_unique($this->tokenize($ayah)));

            if ($ayahWords === []) {
                continue;
            }

            if (count(array_intersect($ayahWords, $tailSet)) / count($ayahWords) >= 0.8) {
                $last = $i;
            }
        }

        return $last;
    }

    /**
     * Drop a trailing reciter closing formula ("صدق الله العظيم") so it does not
     * pollute the closing-ayah match.
     *
     * @param  list<string>  $words
     * @return list<string>
     */
    private function stripClosingFormula(array $words): array
    {
        foreach ([['صدق', 'الله', 'العلي', 'العظيم'], ['صدق', 'الله', 'العظيم']] as $formula) {
            $length = count($formula);

            if (array_slice($words, -$length) === $formula) {
                return array_slice($words, 0, -$length);
            }
        }

        return $words;
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
        // Delete every combining mark (harakat, tanwin, and the Uthmani corpus'
        // Quranic annotation signs) plus tatweel. Using \p{Mn} rather than a few
        // fixed ranges is essential: signs like U+06E1 would otherwise fall to
        // the catch-all below and become spaces, splitting words (مَّحۡفُوظ → "مح فوظ").
        $text = preg_replace('/[\p{Mn}\x{0640}]/u', '', $text) ?? $text;

        $text = strtr($text, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ة' => 'ه', 'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي', 'ء' => '',
        ]);

        // Drop anything that is not an Arabic letter or whitespace.
        $text = preg_replace('/[^\x{0621}-\x{064A}\s]/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * Cut a 16kHz mono window out of the recitation for transcription and return
     * the temp file path. The caller owns cleanup.
     */
    private function extractWindow(string $audioAbs, int $start, int $window): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'asr');

        // Give the window a .mp3 name so ffmpeg's container is unambiguous.
        // Rename the file tempnam() already created rather than append, so the
        // path still exists for the success check below (and under Process::fake).
        if (@rename($temp, $temp.'.mp3')) {
            $temp .= '.mp3';
        }

        $extract = Process::timeout((int) config('publishing.render_timeout'))->run([
            config('youtube.ffmpeg_path'),
            '-y',
            '-ss', (string) $start,
            '-t', (string) $window,
            '-i', $audioAbs,
            '-ac', '1',
            '-ar', '16000',
            '-f', 'mp3',
            $temp,
        ]);

        if (! $extract->successful() || ! is_file($temp)) {
            @unlink($temp);

            throw new RuntimeException('ffmpeg failed to extract the transcription window.');
        }

        return $temp;
    }

    /**
     * Transcribe every window, preserving order. Gemini transcribes the whole
     * batch in one multimodal request; the OpenAI driver posts each file.
     *
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function transcribeFiles(array $paths): array
    {
        return match (config('publishing.transcription.driver')) {
            'gemini' => $this->transcribeGemini($paths),
            default => array_map(fn (string $path): string => $this->transcribeRemote($path), $paths),
        };
    }

    /**
     * Transcribe every window in a single Gemini request: one text instruction
     * plus one inline audio part per window, answered as a JSON array of the
     * verbatim Arabic transcriptions in the same order.
     *
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function transcribeGemini(array $paths): array
    {
        $config = config('publishing.transcription.gemini');
        $count = count($paths);

        $parts = [[
            'text' => "You are given {$count} short Quran recitation audio clips, in order. "
                .'Transcribe each clip to verbatim Arabic text (Uthmani words, transcription only, no translation or commentary). '
                ."Return ONLY a JSON array of exactly {$count} strings, one transcription per clip, in the same order.",
        ]];

        foreach ($paths as $path) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => 'audio/mp3',
                    'data' => base64_encode((string) file_get_contents($path)),
                ],
            ];
        }

        $response = Http::timeout((int) $config['timeout'])
            ->withHeaders(['x-goog-api-key' => (string) $config['api_key']])
            ->post(rtrim((string) $config['base_url'], '/').'/models/'.$config['model'].':generateContent', [
                'contents' => [['parts' => $parts]],
                'generationConfig' => ['temperature' => 0, 'responseMimeType' => 'application/json'],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini ASR failed: '.$response->body());
        }

        $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $transcripts = json_decode(trim($text), true);

        if (! is_array($transcripts) || count($transcripts) !== $count) {
            throw new RuntimeException('Gemini ASR returned an unexpected payload: '.$text);
        }

        return array_map(fn ($value): string => trim((string) $value), array_values($transcripts));
    }

    private function transcribeRemote(string $absPath): string
    {
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
