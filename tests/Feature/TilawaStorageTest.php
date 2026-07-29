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

    $tilawa = Tilawa::withoutEvents(fn () => Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'title_ar' => 'ما تيسر من سوره النمل',
        'audio_path' => 'tilawat/legacy.mp3',
        'master_audio_path' => 'published/audio/legacy.mp3',
    ]));

    $this->artisan('tilawat:organize-storage')->assertSuccessful();

    $tilawa->refresh();

    expect($tilawa->audio_path)
        ->toBe('tilawat/محمد صديق المنشاوي/ما تيسر من سوره النمل للقاري الشيخ محمد صديق المنشاوي.mp3')
        ->and($tilawa->master_audio_path)
        ->toBe('published/audio/محمد صديق المنشاوي/ما تيسر من سوره النمل للقاري الشيخ محمد صديق المنشاوي.mp3');

    Storage::disk('public')->assertExists($tilawa->audio_path);
    Storage::disk('public')->assertExists($tilawa->master_audio_path);
});
