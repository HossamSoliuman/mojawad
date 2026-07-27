<?php

use App\Jobs\RenderBrandedVideo;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Services\CardVideoRenderer;
use App\Services\VideoCardService;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\mock;

beforeEach(function () {
    Storage::fake('public');
});

// ── Card data defaults ─────────────────────────────────────────────

it('builds default card data from the recitation', function () {
    $qari = Qari::factory()->create(['name_ar' => 'الشيخ مصطفى إسماعيل']);
    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'description_ar' => 'تلاوة من حفل عام 1962',
        'brand_card' => null,
    ]);

    $data = app(VideoCardService::class)->dataFor($tilawa->load('qari'));

    expect($data['tilawaTitle'])->toBe($tilawa->title_ar)
        ->and($data['qariName'])->toBe('الشيخ مصطفى إسماعيل')
        ->and($data['surahName'])->toBe($tilawa->surah_label)
        ->and($data['extraText'])->toBe('تلاوة من حفل عام 1962')
        ->and($data['rareBadge'])->toBe('تلاوة نادرة')
        ->and($data['animate_photo'])->toBeTrue()
        ->and($data['animate_text'])->toBeTrue();
});

it('prefers saved card settings over the recitation defaults', function () {
    $tilawa = Tilawa::factory()->create([
        'description_ar' => 'الوصف الأصلي',
        'brand_card' => ['qari_name' => 'اسم مخصص', 'extra_text' => 'نص مخصص', 'rare_badge' => '', 'animate_text' => false],
    ]);

    $data = app(VideoCardService::class)->dataFor($tilawa->load('qari'));

    expect($data['qariName'])->toBe('اسم مخصص')
        ->and($data['extraText'])->toBe('نص مخصص')
        ->and($data['rareBadge'])->toBe('')
        ->and($data['animate_text'])->toBeFalse()
        ->and($data['animate_photo'])->toBeTrue();
});

// ── Motion filter graph ────────────────────────────────────────────

it('animates the photo on a sine drift and rises the text in', function () {
    $graph = app(CardVideoRenderer::class)->filterComplex(true, true, true, null);

    expect($graph)
        ->toContain('sin(2*PI*t/12)')
        ->toContain('fade=t=in')
        ->toContain('overlay=0:0')
        ->toContain('format=yuv420p[v]');
});

it('keeps the photo still when photo animation is off', function () {
    $graph = app(CardVideoRenderer::class)->filterComplex(true, false, false, null);

    expect($graph)->not->toContain('sin(')
        ->and($graph)->toContain('overlay=0:0');
});

it('skips the text layer when there is no animated text', function () {
    $graph = app(CardVideoRenderer::class)->filterComplex(true, true, false, null);

    expect($graph)->not->toContain('fade=t=in');
});

it('composites the card over black even without a qari photo', function () {
    $graph = app(CardVideoRenderer::class)->filterComplex(false, true, false, null);

    expect($graph)->toContain('[1:v][2:v]overlay=0:0')
        ->and($graph)->not->toContain('force_original_aspect_ratio');
});

it('burns subtitles when a subtitle file is given', function () {
    $graph = app(CardVideoRenderer::class)->filterComplex(true, true, true, 'C:\\subs\\aya.vtt');

    expect($graph)->toContain('subtitles=');
});

it('builds the ffmpeg command with looped image inputs and shortest output', function () {
    $command = app(CardVideoRenderer::class)->buildCommand(
        'audio.mp3', 'photo.jpg', 'overlay.png', 'text.png', null, 'out.mp4', true,
    );

    $joined = implode(' ', $command);

    expect($joined)
        ->toContain('-i audio.mp3')
        ->toContain('color=c=black:s=1920x1080')
        ->toContain('-loop 1 -framerate 25 -i photo.jpg')
        ->toContain('-loop 1 -framerate 25 -i overlay.png')
        ->toContain('-loop 1 -framerate 25 -i text.png')
        ->toContain('-shortest');
});

// ── The render job ─────────────────────────────────────────────────

it('stores the card video, keeps the card image, and drops stale artifacts', function () {
    $tilawa = Tilawa::factory()->create([
        'brand_status' => 'processing',
        'brand_video_path' => 'published/videos/old.mp4',
        'brand_card' => ['card_image' => 'published/cards/old.png', 'extra_text' => 'نص'],
    ]);
    Storage::disk('public')->put('published/videos/old.mp4', 'v');
    Storage::disk('public')->put('published/cards/old.png', 'c');

    mock(CardVideoRenderer::class)
        ->shouldReceive('render')->once()
        ->andReturn(['video' => 'published/videos/new.mp4', 'card_image' => 'published/cards/new.png']);

    (new RenderBrandedVideo($tilawa->id))->handle(app(CardVideoRenderer::class));

    $fresh = $tilawa->fresh();
    expect($fresh->brand_status)->toBe('ready')
        ->and($fresh->brand_video_path)->toBe('published/videos/new.mp4')
        ->and($fresh->brand_card['card_image'])->toBe('published/cards/new.png')
        ->and($fresh->brand_card['extra_text'])->toBe('نص');

    Storage::disk('public')->assertMissing('published/videos/old.mp4');
    Storage::disk('public')->assertMissing('published/cards/old.png');
});

it('falls back to the still-cover render when the card path fails', function () {
    $tilawa = Tilawa::factory()->create([
        'brand_status' => 'processing',
        'audio_path' => 'tilawat/a.mp3',
        'brand_cover_path' => 'published/covers/c.png',
    ]);
    Storage::disk('public')->put('tilawat/a.mp3', 'a');
    Storage::disk('public')->put('published/covers/c.png', 'img');

    Process::fake();
    mock(CardVideoRenderer::class)
        ->shouldReceive('render')->once()
        ->andThrow(new RuntimeException('Chrome not found'));

    try {
        (new RenderBrandedVideo($tilawa->id))->handle(app(CardVideoRenderer::class));
    } catch (RuntimeException) {
        // Process::fake() writes no file, so the fallback's output check throws.
    }

    Process::assertRan(function ($process) {
        $joined = implode(' ', (array) $process->command);

        return str_contains($joined, '-loop') && str_contains($joined, 'a.mp3');
    });
});
