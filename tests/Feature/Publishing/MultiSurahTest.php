<?php

use App\Jobs\DetectAyahRange;
use App\Jobs\IngestSourceAudio;
use App\Livewire\Admin\FactoryImport;
use App\Livewire\SurahTilawat;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Models\User;
use App\Services\RecitationLibrary;
use App\Services\SourceCleanService;
use App\Support\TilawaTitle;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    Storage::fake('recitations');
    Storage::fake('public');
});

/**
 * Write cleaned bytes to whatever output path the faked ffmpeg is handed so the
 * ingest job can carry on past the cleaning step.
 */
function fakeClean(): void
{
    Process::fake(function ($process) {
        $cmd = (array) $process->command;
        $output = end($cmd);
        if (is_string($output) && $output !== '') {
            @file_put_contents($output, 'clean-bytes');
        }

        return Process::result(output: '', errorOutput: '', exitCode: 0);
    });
}

it('parses a multi-surah file name into an ordered surah list', function () {
    $parsed = RecitationLibrary::parse('ماتيسر من سورة التوبة ويونس.mp3');

    expect($parsed['surahs'])->toBe([9, 10])
        ->and($parsed['part'])->toBeNull();
});

it('parses the part number from a multi-part surah file name', function () {
    expect(RecitationLibrary::parse('ماتيسر من سورة البقرة 1.mp3'))->toBe(['surahs' => [2], 'part' => 1])
        ->and(RecitationLibrary::parse('ماتيسر من سورة البقرة 2.mp3'))->toBe(['surahs' => [2], 'part' => 2]);
});

it('does not read an ayah range as a part number', function () {
    $parsed = RecitationLibrary::parse('سورة الأحزاب 59-73.mp3');

    expect($parsed['surahs'])->toBe([33])
        ->and($parsed['part'])->toBeNull();
});

it('parses a part number glued to the surah name', function () {
    expect(RecitationLibrary::parse('ماتيسر من سورة مريم1.mp3'))->toBe(['surahs' => [19], 'part' => 1])
        ->and(RecitationLibrary::parse('ماتيسر من سورة ق1.mp3'))->toBe(['surahs' => [50], 'part' => 1]);
});

it('parses surahs joined by a spaced or attached waw', function () {
    expect(RecitationLibrary::parse('ماتيسر من سورة مريم و التكوير.mp3')['surahs'])->toBe([19, 81])
        ->and(RecitationLibrary::parse('ماتيسر من سورة القمر والرحمن.mp3')['surahs'])->toBe([54, 55])
        ->and(RecitationLibrary::parse('ماتيسر من سورتي الأحزاب والبلد.mp3')['surahs'])->toBe([33, 90]);
});

it('builds Arabic titles for one, two, and several surahs', function () {
    expect(TilawaTitle::forSurahs([27]))->toBe('ما تيسر من سوره النمل')
        ->and(TilawaTitle::forSurahs([9, 10]))->toBe('ما تيسر من سورتي التوبة ويونس')
        ->and(TilawaTitle::forSurahs([9, 10, 4]))->toBe('ما تيسر من سور التوبة ويونس والنساء');
});

it('tags a single-surah title with its part number', function () {
    expect(TilawaTitle::forSurahs([2], 1))->toBe('ما تيسر من سوره البقرة (الجزء الأول)')
        ->and(TilawaTitle::forSurahs([2], 2))->toBe('ما تيسر من سوره البقرة (الجزء الثاني)');
});

it('imports multi-surah and multi-part library files with structured surah data', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();

    Storage::disk('recitations')->put('البنا/ماتيسر من سورة التوبة ويونس.mp3', 'a');
    Storage::disk('recitations')->put('البنا/ماتيسر من سورة البقرة 1.mp3', 'b');
    Storage::disk('recitations')->put('البنا/ماتيسر من سورة البقرة 2.mp3', 'c');

    Livewire::actingAs($creator)
        ->test(FactoryImport::class)
        ->call('selectQari', $qari->id)
        ->call('selectFolder', 'البنا')
        ->call('import')
        ->assertSet('imported', 3);

    $multi = TilawatSource::where('source_url', 'البنا/ماتيسر من سورة التوبة ويونس.mp3')->first();
    expect($multi->surah_number)->toBe(9)
        ->and($multi->surahs)->toBe([9, 10])
        ->and($multi->part)->toBeNull();

    $partOne = TilawatSource::where('source_url', 'البنا/ماتيسر من سورة البقرة 1.mp3')->first();
    expect($partOne->surah_number)->toBe(2)
        ->and($partOne->surahs)->toBeNull()
        ->and($partOne->part)->toBe(1);

    $partTwo = TilawatSource::where('source_url', 'البنا/ماتيسر من سورة البقرة 2.mp3')->first();
    expect($partTwo->part)->toBe(2);
});

it('ingests a multi-surah source with a multi-surah title and skips ayah detection', function () {
    Bus::fake();
    config(['publishing.transcription.api_key' => 'test-key']);
    fakeClean();

    $qari = Qari::factory()->create();
    Storage::disk('recitations')->put('البنا/multi.mp3', 'raw-bytes');

    $source = TilawatSource::create([
        'source_type' => 'library',
        'source_url' => 'البنا/multi.mp3',
        'source_title' => 'ماتيسر من سورة التوبة ويونس',
        'qari_id' => $qari->id,
        'surah_number' => 9,
        'surahs' => [9, 10],
        'status' => 'pending',
        'created_by' => $qari->id,
    ]);

    (new IngestSourceAudio($source->id))->handle(new SourceCleanService);

    $tilawa = Tilawa::find($source->fresh()->tilawa_id);
    expect($tilawa->title_ar)->toBe('ما تيسر من سورتي التوبة ويونس')
        ->and($tilawa->surah_number)->toBe(9)
        ->and($tilawa->surahs)->toBe([9, 10])
        ->and($tilawa->isMultiSurah())->toBeTrue();

    Bus::assertNotDispatched(DetectAyahRange::class);
});

it('ingests a multi-part file with its part number in the title', function () {
    Bus::fake();
    fakeClean();

    $qari = Qari::factory()->create();
    Storage::disk('recitations')->put('البنا/baqara-1.mp3', 'raw-bytes');

    $source = TilawatSource::create([
        'source_type' => 'library',
        'source_url' => 'البنا/baqara-1.mp3',
        'source_title' => 'ماتيسر من سورة البقرة 1',
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'part' => 1,
        'status' => 'pending',
        'created_by' => $qari->id,
    ]);

    (new IngestSourceAudio($source->id))->handle(new SourceCleanService);

    $tilawa = Tilawa::find($source->fresh()->tilawa_id);
    expect($tilawa->title_ar)->toBe('ما تيسر من سوره البقرة (الجزء الأول)')
        ->and($tilawa->surahs)->toBeNull()
        ->and($tilawa->isMultiSurah())->toBeFalse();
});

it('lists a multi-surah recitation on each of its surah pages', function () {
    $qari = Qari::factory()->create();
    Tilawa::factory()->approved()->create([
        'qari_id' => $qari->id,
        'surah_number' => 9,
        'surahs' => [9, 10],
        'title_ar' => 'ما تيسر من سورتي التوبة ويونس',
    ]);

    Livewire::test(SurahTilawat::class, ['surah' => 10])
        ->assertSee('ما تيسر من سورتي التوبة ويونس');

    Livewire::test(SurahTilawat::class, ['surah' => 9])
        ->assertSee('ما تيسر من سورتي التوبة ويونس');
});
