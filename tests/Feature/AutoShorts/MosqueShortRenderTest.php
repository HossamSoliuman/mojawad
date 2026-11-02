<?php

use App\Jobs\RenderVideoClip;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\VideoClip;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('renders a mosque short with the ayat burned in as subtitles', function () {
    $written = [];

    Process::fake(function (PendingProcess $process) use (&$written) {
        $command = (array) $process->command;

        if ($process->path !== null && is_file($process->path.'/subs.ass')) {
            $written['ass'] = File::get($process->path.'/subs.ass');
            $written['fonts'] = array_map(fn ($file) => $file->getFilename(), File::files($process->path.'/fonts'));
        }

        @file_put_contents((string) end($command), 'binary');

        return Process::result();
    });

    $qari = Qari::factory()->create(['name_ar' => 'محمد صديق المنشاوي']);
    $tilawa = Tilawa::factory()->approved()->create(['qari_id' => $qari->id, 'surah_number' => 55]);
    Storage::disk('public')->put($tilawa->audio_path, 'audio-bytes');

    $clip = VideoClip::factory()->create([
        'tilawa_id' => $tilawa->id,
        'template' => 'mosque',
        'clip_start' => 12.4,
        'clip_duration' => 40,
        'overlay_path' => null,
        'background_path' => 'cairo-sultan-hassan.jpg',
        'subtitles' => ['surah' => 55, 'cues' => [['start' => 0.4, 'end' => 6.2, 'text' => 'ٱلرَّحۡمَٰنُ ۝١']]],
    ]);

    (new RenderVideoClip($clip->id))->handle();

    $clip->refresh();

    expect($clip->status)->toBe('completed')
        ->and($clip->duration)->toBe(40)
        ->and($written['ass'])->toContain('الشيخ محمد صديق المنشاوي')
        ->and($written['ass'])->toContain('سورة الرحمن')
        ->and($written['ass'])->toContain('0:00:00.40,0:00:06.20,Ayah')
        ->and($written['fonts'])->toContain('AmiriQuran-Regular.ttf');

    Process::assertRan(function (PendingProcess $process) {
        $command = implode(' ', (array) $process->command);

        return str_contains($command, 'cairo-sultan-hassan.jpg')
            && str_contains($command, 'ass=subs.ass:fontsdir=fonts:shaping=complex')
            && str_contains($command, '-ss 12.4');
    });

    expect(File::directories(storage_path('app/clip-work')))->toBeEmpty();
});
