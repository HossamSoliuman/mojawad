<?php

use App\Services\RecitationTranscriber;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Sleep::fake();
    config([
        'publishing.transcription.gemini.api_key' => 'test-key',
        'clips.transcription.models' => ['busy-model', 'good-model'],
    ]);

    Process::fake(function (PendingProcess $process) {
        $command = (array) $process->command;

        if (in_array('null', $command, true)) {
            $envelope = '';

            for ($frame = 0; $frame < 200; $frame++) {
                $time = $frame / 10;
                $level = $time >= 9.4 && $time < 12.1 ? -38.5 : -12.0 + ($frame % 3);
                $envelope .= "frame:{$frame} pts:{$frame} pts_time:{$time}\nlavfi.astats.Overall.RMS_level={$level}\n";
            }

            return Process::result(output: $envelope);
        }

        @file_put_contents((string) end($command), 'mp3-bytes');

        return Process::result();
    });
});

it('transcribes timed phrases, falling back from a busy model and snapping to pauses', function () {
    Http::fake([
        '*/models/busy-model:generateContent' => Http::response(['error' => ['code' => 503]], 503),
        '*/models/good-model:generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode([
            ['start' => '00:12.5', 'end' => '00:20.0', 'text' => 'الله الصمد'],
            ['start' => '00:00.6', 'end' => '00:08.2', 'text' => 'قل هو الله أحد'],
            ['start' => '01:05.0', 'end' => '1:15', 'text' => 'لم يلد ولم يولد'],
            ['start' => '00:21', 'end' => '00:20', 'text' => 'broken'],
        ])]]]]]]),
    ]);

    $phrases = (new RecitationTranscriber)->transcribe('/audio.mp3', 100, 150);

    expect($phrases)->toBe([
        ['start' => 100.6, 'end' => 109.4, 'text' => 'قل هو الله أحد', 'pauses' => []],
        ['start' => 112.1, 'end' => 120.0, 'text' => 'الله الصمد', 'pauses' => []],
        ['start' => 165.0, 'end' => 175.0, 'text' => 'لم يلد ولم يولد', 'pauses' => []],
    ]);

    Http::assertSentCount(4);
    Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-key')
        && str_contains(json_encode($request->data()), 'inline_data'));
});

it('fails when no model returns a phrase list', function () {
    Http::fake(['*' => Http::response(['error' => ['code' => 400]], 400)]);

    expect(fn () => (new RecitationTranscriber)->transcribe('/audio.mp3', 0, 60))
        ->toThrow(RuntimeException::class, 'good-model: HTTP 400');
});

it('finds pauses relative to the reciter\'s voice level and joins brief bursts', function () {
    $envelope = [];

    foreach (range(0, 99) as $frame) {
        $time = $frame / 10;
        $level = match (true) {
            $time >= 2.0 && $time < 3.5 => -30.0,
            $time >= 3.5 && $time < 3.6 => -15.0,
            $time >= 3.6 && $time < 4.2 => -29.0,
            $time >= 7.0 && $time < 7.2 => -31.0,
            default => -11.0,
        };
        $envelope[] = [$time, $level];
    }

    expect((new RecitationTranscriber)->quietStretches($envelope))->toBe([[2.0, 4.2]]);
});

it('skips a model whose quota is used up for the following requests', function () {
    config(['clips.transcription.models' => ['spent-model', 'good-model']]);

    Http::fake([
        '*/models/spent-model:generateContent' => Http::response(['error' => ['code' => 429]], 429),
        '*/models/good-model:generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode([
            ['start' => '00:00.6', 'end' => '00:08.2', 'text' => 'قل هو الله أحد'],
        ])]]]]]]),
    ]);

    $transcriber = new RecitationTranscriber;
    $transcriber->transcribe('/audio.mp3', 0, 60);
    $transcriber->transcribe('/audio.mp3', 60, 60);

    Http::assertSentCount(3);
});
