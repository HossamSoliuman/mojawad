<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use RuntimeException;

class RecitationTranscriber
{
    private const PROMPT = 'This is an audio clip of a Quran recitation (mujawwad style). '
        .'Split it into breath groups: a new entry starts every time the reciter pauses or takes a breath, so most entries hold only a few words. '
        .'Keep them in order and include repeated phrases. '
        .'For each entry give its start and end time from the beginning of the clip as "MM:SS.s" and the Arabic words recited, verbatim as heard; '
        .'do not complete, correct or continue the text from memory. Skip audience reactions and silence. '
        .'Return ONLY a JSON array of objects {"start": "MM:SS.s", "end": "MM:SS.s", "text": string}.';

    /**
     * How far (seconds) a phrase edge may move to land on a detected pause.
     */
    private const SNAP = 2.0;

    /**
     * Transcribe a window of a recitation into timed phrases. Gemini hears
     * the phrases and their words; ffmpeg's silence detection then moves each
     * phrase edge onto the nearby pause so held notes are not cut. Times come
     * back relative to the whole file, with the pauses inside each phrase.
     *
     * @return list<array{start: float, end: float, text: string, pauses: list<float>}>
     */
    public function transcribe(string $audioPath, float $start, float $duration): array
    {
        $clip = tempnam(sys_get_temp_dir(), 'asr');

        if (@rename($clip, $clip.'.mp3')) {
            $clip .= '.mp3';
        }

        try {
            $cut = Process::timeout(300)->run([
                config('youtube.ffmpeg_path'),
                '-y',
                '-ss', (string) $start,
                '-t', (string) $duration,
                '-i', $audioPath,
                '-ac', '1',
                '-ar', '16000',
                '-b:a', '64k',
                '-f', 'mp3',
                $clip,
            ]);

            if (! $cut->successful()) {
                throw new RuntimeException('ffmpeg could not cut the transcription window: '.trim($cut->errorOutput()));
            }

            $phrases = $this->ask((string) file_get_contents($clip));
            $silences = $this->silences($clip);
        } finally {
            if (is_file($clip)) {
                @unlink($clip);
            }
        }

        $inWindow = array_values(array_filter($phrases, fn (array $phrase) => $phrase['end'] <= $duration + 1));

        return array_map(fn (array $phrase) => [
            'start' => round($phrase['start'] + $start, 2),
            'end' => round(min($phrase['end'], $duration) + $start, 2),
            'text' => $phrase['text'],
            'pauses' => array_values(array_map(
                fn (array $silence) => round(($silence[0] + $silence[1]) / 2 + $start, 2),
                array_filter($silences, fn (array $silence) => $silence[0] > $phrase['start'] + 0.3 && $silence[1] < $phrase['end'] - 0.3),
            )),
        ], $this->snap($inWindow, $silences));
    }

    /**
     * Ask Gemini for the timed phrases, retrying busy models and falling
     * back through the configured list.
     *
     * @return list<array{start: float, end: float, text: string}>
     */
    private function ask(string $audio): array
    {
        $gemini = config('publishing.transcription.gemini');
        $lastError = 'no model configured';

        foreach ((array) config('clips.transcription.models') as $model) {
            for ($attempt = 1; $attempt <= (int) config('clips.transcription.attempts'); $attempt++) {
                try {
                    $response = Http::timeout((int) config('clips.transcription.timeout'))
                        ->withHeaders(['x-goog-api-key' => (string) $gemini['api_key']])
                        ->post(rtrim((string) $gemini['base_url'], '/').'/models/'.trim($model).':generateContent', [
                            'contents' => [['parts' => [
                                ['text' => self::PROMPT],
                                ['inline_data' => ['mime_type' => 'audio/mp3', 'data' => base64_encode($audio)]],
                            ]]],
                            'generationConfig' => ['temperature' => 0, 'responseMimeType' => 'application/json'],
                        ]);
                } catch (ConnectionException $exception) {
                    $lastError = $exception->getMessage();

                    continue;
                }

                if ($response->successful()) {
                    $phrases = $this->parse((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));

                    if ($phrases !== null) {
                        return $phrases;
                    }

                    $lastError = "{$model} returned an unreadable phrase list";

                    continue;
                }

                $lastError = "{$model}: HTTP {$response->status()}";

                if (! in_array($response->status(), [429, 500, 503], true)) {
                    break;
                }

                Sleep::for(5 * $attempt)->seconds();
            }
        }

        throw new RuntimeException('Transcription failed: '.$lastError);
    }

    /**
     * @return list<array{start: float, end: float, text: string}>|null
     */
    private function parse(string $text): ?array
    {
        $payload = json_decode(trim($text), true);

        if (! is_array($payload)) {
            return null;
        }

        $phrases = [];

        foreach ($payload as $phrase) {
            if (! is_array($phrase) || ! isset($phrase['start'], $phrase['end'], $phrase['text'])) {
                continue;
            }

            $start = $this->seconds($phrase['start']);
            $end = $this->seconds($phrase['end']);
            $text = trim((string) $phrase['text']);

            if ($text === '' || $start === null || $end === null || $end <= $start) {
                continue;
            }

            $phrases[] = ['start' => $start, 'end' => $end, 'text' => $text];
        }

        usort($phrases, fn (array $a, array $b) => $a['start'] <=> $b['start']);

        return $phrases;
    }

    /**
     * A "MM:SS.s" (or "HH:MM:SS.s") timestamp, or plain seconds, in seconds.
     */
    private function seconds(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value) || ! preg_match('/^\s*(?:(\d+):)?(?:(\d+):)?(\d+(?:\.\d+)?)\s*$/', $value, $parts)) {
            return null;
        }

        $units = array_values(array_filter([$parts[1], $parts[2]], fn (string $part) => $part !== ''));
        $seconds = (float) $parts[3];

        foreach (array_reverse($units) as $power => $unit) {
            $seconds += (int) $unit * 60 ** ($power + 1);
        }

        return $seconds;
    }

    /**
     * Pauses in the clip as [start, end] pairs, read from a 0.1s loudness
     * envelope. Old and live recordings never fall silent (hiss, audience),
     * so a pause is any stretch well below the reciter's own voice level
     * rather than below a fixed threshold.
     *
     * @return list<array{0: float, 1: float}>
     */
    private function silences(string $clip): array
    {
        $result = Process::timeout(300)->run([
            config('youtube.ffmpeg_path'),
            '-hide_banner',
            '-i', $clip,
            '-af', 'asetnsamples=n=1600,astats=metadata=1:reset=1,ametadata=print:key=lavfi.astats.Overall.RMS_level:file=-',
            '-f', 'null',
            '-',
        ]);

        preg_match_all('/pts_time:([\d.]+)\s+lavfi\.astats\.Overall\.RMS_level=(-?[\d.]+|-inf)/', $result->output(), $matches, PREG_SET_ORDER);

        return $this->quietStretches(array_map(
            fn (array $match) => [(float) $match[1], $match[2] === '-inf' ? -120.0 : (float) $match[2]],
            $matches,
        ));
    }

    /**
     * Stretches of at least 0.4s whose loudness stays more than
     * clips.transcription.pause_drop dB under the voice level (the 80th
     * percentile of the envelope).
     *
     * @param  list<array{0: float, 1: float}>  $envelope  [time, RMS dB] every 0.1s
     * @return list<array{0: float, 1: float}>
     */
    public function quietStretches(array $envelope): array
    {
        if (count($envelope) < 10) {
            return [];
        }

        $levels = array_column($envelope, 1);
        sort($levels);
        $voice = $levels[(int) floor(count($levels) * 0.8)];
        $threshold = $voice - (float) config('clips.transcription.pause_drop');

        $stretches = [];
        $open = null;

        foreach ($envelope as [$time, $level]) {
            if ($level < $threshold) {
                $open ??= $time;

                continue;
            }

            if ($open !== null) {
                $stretches[] = [$open, $time];
            }

            $open = null;
        }

        if ($open !== null) {
            $stretches[] = [$open, end($envelope)[0] + 0.1];
        }

        $merged = [];

        foreach ($stretches as $stretch) {
            $last = array_key_last($merged);

            if ($last !== null && $stretch[0] - $merged[$last][1] <= 0.3) {
                $merged[$last][1] = $stretch[1];
            } else {
                $merged[] = $stretch;
            }
        }

        return array_values(array_map(
            fn (array $stretch) => [round($stretch[0], 2), round($stretch[1], 2)],
            array_filter($merged, fn (array $stretch) => $stretch[1] - $stretch[0] >= 0.4),
        ));
    }

    /**
     * Move each phrase start to the end of the pause just before it and each
     * phrase end to the start of the pause just after it, when one is close.
     *
     * @param  list<array{start: float, end: float, text: string}>  $phrases
     * @param  list<array{0: float, 1: float}>  $silences
     * @return list<array{start: float, end: float, text: string}>
     */
    public function snap(array $phrases, array $silences): array
    {
        foreach ($phrases as $index => $phrase) {
            $start = $phrase['start'];
            $end = $phrase['end'];

            $starts = array_filter(array_column($silences, 1), fn (float $time) => abs($time - $phrase['start']) <= self::SNAP);
            $ends = array_filter(array_column($silences, 0), fn (float $time) => abs($time - $phrase['end']) <= self::SNAP);

            if ($starts !== []) {
                $start = $this->nearest($starts, $phrase['start']);
            }

            if ($ends !== []) {
                $end = $this->nearest($ends, $phrase['end']);
            }

            if ($end > $start + 0.3) {
                $phrases[$index]['start'] = $start;
                $phrases[$index]['end'] = $end;
            }
        }

        return $phrases;
    }

    /**
     * @param  array<int, float>  $candidates
     */
    private function nearest(array $candidates, float $target): float
    {
        usort($candidates, fn (float $a, float $b) => abs($a - $target) <=> abs($b - $target));

        return $candidates[0];
    }
}
