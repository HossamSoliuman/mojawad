<?php

use App\Jobs\DetectAyahRange;
use App\Models\Tilawa;
use App\Services\AyahDetectionService;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

$fatiha = [
    'بسم الله الرحمن الرحيم',
    'الحمد لله رب العالمين',
    'الرحمن الرحيم',
    'مالك يوم الدين',
    'اياك نعبد واياك نستعين',
    'اهدنا الصراط المستقيم',
    'صراط الذين انعمت عليهم غير المغضوب عليهم ولا الضالين',
];

it('pins the opening and closing ayat within the known surah', function () use ($fatiha) {
    $result = (new AyahDetectionService)->matchRange(
        $fatiha,
        'الحمد لله رب العالمين الرحمن الرحيم مالك',
        'غير المغضوب عليهم ولا الضالين',
    );

    expect($result['from'])->toBe(2)
        ->and($result['to'])->toBe(7)
        ->and($result['score'])->toBeGreaterThan(0.7);
});

it('matches despite diacritics and alef/hamza differences', function () use ($fatiha) {
    $result = (new AyahDetectionService)->matchRange(
        $fatiha,
        'إِيَّاكَ نَعْبُدُ وَإِيَّاكَ نَسْتَعِينُ',
        'إِيَّاكَ نَعْبُدُ وَإِيَّاكَ نَسْتَعِينُ',
    );

    expect($result['from'])->toBe(5)
        ->and($result['to'])->toBe(5);
});

it('never returns a to-ayah before the from-ayah', function () use ($fatiha) {
    $result = (new AyahDetectionService)->matchRange($fatiha, 'مالك يوم الدين', 'الحمد لله رب العالمين');

    expect($result['to'])->toBeGreaterThanOrEqual($result['from']);
});

it('writes the range, confidence and full title from the detector', function () {
    $tilawa = Tilawa::factory()->create(['surah_number' => 2, 'ayah_from' => null, 'ayah_to' => null]);

    $detector = Mockery::mock(AyahDetectionService::class);
    $detector->shouldReceive('detect')->once()->andReturn(['from' => 3, 'to' => 10, 'score' => 0.92]);

    (new DetectAyahRange($tilawa->id))->handle($detector);

    $tilawa->refresh();
    expect($tilawa->ayah_from)->toBe(3)
        ->and($tilawa->ayah_to)->toBe(10)
        ->and($tilawa->ayah_confidence)->toBe('high')
        ->and($tilawa->title_ar)->toBe('ما تيسر من سوره البقرة من الايه 3 الي الايه 10');
});

it('flags a low-confidence detection for human review', function () {
    $tilawa = Tilawa::factory()->create(['surah_number' => 2]);

    $detector = Mockery::mock(AyahDetectionService::class);
    $detector->shouldReceive('detect')->andReturn(['from' => 1, 'to' => 5, 'score' => 0.4]);

    (new DetectAyahRange($tilawa->id))->handle($detector);

    expect($tilawa->fresh()->ayah_confidence)->toBe('low');
});

it('falls back to manual entry when detection fails', function () {
    $tilawa = Tilawa::factory()->create(['surah_number' => 2]);

    (new DetectAyahRange($tilawa->id))->failed(new RuntimeException('asr down'));

    expect($tilawa->fresh()->ayah_confidence)->toBe('manual');
});
