<?php

use App\Models\Qari;
use App\Models\Tilawa;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('names new audio after the tilawa and stores it in the qari folder', function () {
    $qari = Qari::factory()->create(['name_ar' => 'محمد رفعت']);
    Storage::disk('public')->put('tilawat/random-name.mp3', 'audio');

    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'title_ar' => 'ما تيسر من سوره مريم',
        'audio_path' => 'tilawat/random-name.mp3',
    ]);

    $expected = 'tilawat/محمد رفعت/ما تيسر من سوره مريم للقاري الشيخ محمد رفعت.mp3';

    expect($tilawa->audio_path)->toBe($expected);
    Storage::disk('public')->assertExists($expected);
    Storage::disk('public')->assertMissing('tilawat/random-name.mp3');
});

it('adds a number when the qari already has a tilawa with the same name', function () {
    $qari = Qari::factory()->create(['name_ar' => 'محمد رفعت']);

    Storage::disk('public')->put('tilawat/first.mp3', 'first');
    Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'title_ar' => 'ما تيسر من سوره الرحمن',
        'audio_path' => 'tilawat/first.mp3',
    ]);

    Storage::disk('public')->put('tilawat/second.mp3', 'second');
    $second = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'title_ar' => 'ما تيسر من سوره الرحمن',
        'audio_path' => 'tilawat/second.mp3',
    ]);

    expect($second->audio_path)
        ->toBe('tilawat/محمد رفعت/ما تيسر من سوره الرحمن للقاري الشيخ محمد رفعت (2).mp3');
});

it('numbers published videos inside each qari folder in sortable order', function () {
    $qari = Qari::factory()->create(['name_ar' => 'محمد رفعت']);
    $otherQari = Qari::factory()->create(['name_ar' => 'محمد صديق المنشاوي']);

    Storage::disk('public')->put('published/videos/first-uuid.mp4', 'first');
    $first = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'title_ar' => 'ما تيسر من سوره مريم',
        'brand_video_path' => 'published/videos/first-uuid.mp4',
    ]);

    Storage::disk('public')->put('published/videos/second-uuid.mp4', 'second');
    $second = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'title_ar' => 'ما تيسر من سوره يوسف',
        'brand_video_path' => 'published/videos/second-uuid.mp4',
    ]);

    Storage::disk('public')->put('published/videos/other-uuid.mp4', 'other');
    $other = Tilawa::factory()->create([
        'qari_id' => $otherQari->id,
        'title_ar' => 'ما تيسر من سوره الضحى',
        'brand_video_path' => 'published/videos/other-uuid.mp4',
    ]);

    expect($first->brand_video_path)
        ->toBe('published/videos/محمد رفعت/001 - ما تيسر من سوره مريم.mp4')
        ->and($second->brand_video_path)
        ->toBe('published/videos/محمد رفعت/002 - ما تيسر من سوره يوسف.mp4')
        ->and($other->brand_video_path)
        ->toBe('published/videos/محمد صديق المنشاوي/001 - ما تيسر من سوره الضحى.mp4');

    Storage::disk('public')->assertExists($first->brand_video_path);
    Storage::disk('public')->assertExists($second->brand_video_path);
    Storage::disk('public')->assertExists($other->brand_video_path);
});

it('keeps a published videos number when its tilawa title changes', function () {
    $qari = Qari::factory()->create(['name_ar' => 'محمد رفعت']);
    Storage::disk('public')->put('published/videos/video-uuid.mp4', 'video');

    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'title_ar' => 'ما تيسر من سوره مريم',
        'brand_video_path' => 'published/videos/video-uuid.mp4',
    ]);

    $oldPath = $tilawa->brand_video_path;
    $tilawa->update(['title_ar' => 'ما تيسر من سوره هود']);

    expect($tilawa->brand_video_path)
        ->toBe('published/videos/محمد رفعت/001 - ما تيسر من سوره هود.mp4');

    Storage::disk('public')->assertExists($tilawa->brand_video_path);
    Storage::disk('public')->assertMissing($oldPath);
});

it('repairs a stale database path when the organized video already exists', function () {
    $qari = Qari::factory()->create(['name_ar' => 'محمد صديق المنشاوي']);
    $organizedPath = 'published/videos/محمد صديق المنشاوي/001 - ما تيسر من سوره الضحى.mp4';
    Storage::disk('public')->put($organizedPath, 'video');

    $tilawa = Tilawa::withoutEvents(fn () => Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'title_ar' => 'ما تيسر من سوره الضحى',
        'brand_video_path' => 'published/videos/missing-uuid.mp4',
    ]));

    $this->artisan('tilawat:organize-storage')->assertSuccessful();

    expect($tilawa->fresh()->brand_video_path)->toBe($organizedPath);
    Storage::disk('public')->assertExists($organizedPath);
});

it('deletes published files when their tilawa is removed', function () {
    $paths = [
        'tilawat/audio.mp3',
        'tilawa-covers/cover.jpg',
        'published/subtitles/recitation.vtt',
        'published/audio/master.mp3',
        'published/covers/branded.png',
        'published/videos/محمد رفعت/001 - ما تيسر من سوره مريم.mp4',
        'published/cards/card.png',
    ];

    foreach ($paths as $path) {
        Storage::disk('public')->put($path, 'file');
    }

    $tilawa = Tilawa::withoutEvents(fn () => Tilawa::factory()->create([
        'audio_path' => $paths[0],
        'cover_image' => $paths[1],
        'subtitle_path' => $paths[2],
        'master_audio_path' => $paths[3],
        'brand_cover_path' => $paths[4],
        'brand_video_path' => $paths[5],
        'brand_card' => ['card_image' => $paths[6]],
    ]));

    $tilawa->delete();

    Storage::disk('public')->assertMissing($paths);
});

it('deletes a published video when it is removed from the tilawa', function () {
    $path = 'published/videos/محمد رفعت/001 - ما تيسر من سوره مريم.mp4';
    Storage::disk('public')->put($path, 'video');

    $tilawa = Tilawa::withoutEvents(fn () => Tilawa::factory()->create([
        'brand_video_path' => $path,
    ]));

    $tilawa->update(['brand_video_path' => null]);

    Storage::disk('public')->assertMissing($path);
});

it('keeps the stored filename synchronized with tilawa title changes', function () {
    $qari = Qari::factory()->create(['name_ar' => 'محمد رفعت']);
    Storage::disk('public')->put('tilawat/original.mp3', 'audio');

    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'title_ar' => 'ما تيسر من سوره مريم',
        'audio_path' => 'tilawat/original.mp3',
    ]);

    $oldPath = $tilawa->audio_path;
    $tilawa->update(['title_ar' => 'ما تيسر من سوره هود']);

    $expected = 'tilawat/محمد رفعت/ما تيسر من سوره هود للقاري الشيخ محمد رفعت.mp3';

    expect($tilawa->audio_path)->toBe($expected);
    Storage::disk('public')->assertExists($expected);
    Storage::disk('public')->assertMissing($oldPath);
});

it('organizes existing clean and mastered audio with the command', function () {
    $qari = Qari::factory()->create(['name_ar' => 'محمد صديق المنشاوي']);
    Storage::disk('public')->put('tilawat/legacy.mp3', 'clean');
    Storage::disk('public')->put('published/audio/legacy.mp3', 'master');
    Storage::disk('public')->put('published/videos/legacy.mp4', 'video');

    $tilawa = Tilawa::withoutEvents(fn () => Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'title_ar' => 'ما تيسر من سوره النمل',
        'audio_path' => 'tilawat/legacy.mp3',
        'master_audio_path' => 'published/audio/legacy.mp3',
        'brand_video_path' => 'published/videos/legacy.mp4',
    ]));

    $this->artisan('tilawat:organize-storage')->assertSuccessful();

    $tilawa->refresh();

    expect($tilawa->audio_path)
        ->toBe('tilawat/محمد صديق المنشاوي/ما تيسر من سوره النمل للقاري الشيخ محمد صديق المنشاوي.mp3')
        ->and($tilawa->master_audio_path)
        ->toBe('published/audio/محمد صديق المنشاوي/ما تيسر من سوره النمل للقاري الشيخ محمد صديق المنشاوي.mp3')
        ->and($tilawa->brand_video_path)
        ->toBe('published/videos/محمد صديق المنشاوي/001 - ما تيسر من سوره النمل.mp4');

    Storage::disk('public')->assertExists($tilawa->audio_path);
    Storage::disk('public')->assertExists($tilawa->master_audio_path);
    Storage::disk('public')->assertExists($tilawa->brand_video_path);
});
