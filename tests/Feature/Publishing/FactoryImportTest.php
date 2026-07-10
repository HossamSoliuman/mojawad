<?php

use App\Jobs\IngestSourceAudio;
use App\Livewire\Admin\FactoryImport;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Models\User;
use App\Services\RecitationLibrary;
use App\Services\SourceCleanService;
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

it('guesses the surah number from recitation file names', function (string $filename, ?int $expected) {
    expect(RecitationLibrary::guessSurahNumber($filename))->toBe($expected);
})->with([
    ['001-ماتيسر من سورة النحل.mp3', 16],
    ['ماتيسر من سورة آل عمران.mp3', 3],
    ['سورة الأحزاب 59-73.mp3', 33],
    ['ماتيسر من طه.mp3', 20],
    ['سورة الإخلاص.mp3', 112],
    ['recording without a surah.mp3', null],
]);

it('lists only library folders that contain audio', function () {
    Storage::disk('recitations')->put('al hosiry/سورة النحل.mp3', 'x');
    Storage::disk('recitations')->put('al hosiry/notes.txt', 'x');
    Storage::disk('recitations')->put('empty/readme.txt', 'x');

    $folders = app(RecitationLibrary::class)->folders();

    expect($folders)->toHaveCount(1)
        ->and($folders[0]['name'])->toBe('al hosiry')
        ->and($folders[0]['count'])->toBe(1);
});

it('queues every audio file in a folder as a library source', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();

    Storage::disk('recitations')->put('البنا/سورة التين.mp3', 'a');
    Storage::disk('recitations')->put('البنا/ماتيسر من سورة الأعراف.mp3', 'b');
    Storage::disk('recitations')->put('البنا/cover.jpg', 'not audio');

    Livewire::actingAs($creator)
        ->test(FactoryImport::class)
        ->call('selectQari', $qari->id)
        ->call('selectFolder', 'البنا')
        ->call('import')
        ->assertSet('imported', 2)
        ->assertDispatched('factory-updated');

    $sources = TilawatSource::where('source_type', 'library')->get();
    expect($sources)->toHaveCount(2)
        ->and($sources->pluck('qari_id')->unique()->all())->toBe([$qari->id])
        ->and($sources->firstWhere('source_url', 'البنا/سورة التين.mp3')->surah_number)->toBe(95)
        ->and($sources->firstWhere('source_url', 'البنا/سورة التين.mp3')->created_by)->toBe($creator->id);

    Bus::assertDispatchedTimes(IngestSourceAudio::class, 2);
});

it('does not re-queue files already imported for the reciter', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();

    Storage::disk('recitations')->put('محمد رفعت/سورة التين.mp3', 'a');
    TilawatSource::create([
        'source_type' => 'library',
        'source_url' => 'محمد رفعت/سورة التين.mp3',
        'qari_id' => $qari->id,
        'status' => 'completed',
        'created_by' => $creator->id,
    ]);

    Storage::disk('recitations')->put('محمد رفعت/سورة الضحى.mp3', 'b');

    Livewire::actingAs($creator)
        ->test(FactoryImport::class)
        ->call('selectQari', $qari->id)
        ->call('selectFolder', 'محمد رفعت')
        ->call('import')
        ->assertSet('imported', 1);

    expect(TilawatSource::where('source_type', 'library')->count())->toBe(2);
});

it('ingests a library source by reading straight from the recitations disk', function () {
    Bus::fake();
    Process::fake(function ($process) {
        $cmd = (array) $process->command;
        $output = end($cmd);
        if (is_string($output) && $output !== '') {
            @file_put_contents($output, 'clean-bytes');
        }

        return Process::result(output: '', errorOutput: '', exitCode: 0);
    });

    $qari = Qari::factory()->create();
    Storage::disk('recitations')->put('البنا/سورة التين.mp3', 'raw-bytes');

    $source = TilawatSource::create([
        'source_type' => 'library',
        'source_url' => 'البنا/سورة التين.mp3',
        'source_title' => 'سورة التين',
        'qari_id' => $qari->id,
        'surah_number' => 95,
        'status' => 'pending',
        'created_by' => $qari->id,
    ]);

    (new IngestSourceAudio($source->id))->handle(new SourceCleanService);

    $source->refresh();
    expect($source->status)->toBe('completed')
        ->and($source->tilawa_id)->not->toBeNull();

    $tilawa = Tilawa::find($source->tilawa_id);
    expect($tilawa->surah_number)->toBe(95)
        ->and($tilawa->title_ar)->toBe('ما تيسر من سوره التين');

    // The original library file is read in place, never deleted.
    Storage::disk('recitations')->assertExists('البنا/سورة التين.mp3');
});

it('falls back to the file name for the title when no surah is parsed', function () {
    Bus::fake();
    Process::fake(function ($process) {
        $cmd = (array) $process->command;
        $output = end($cmd);
        if (is_string($output) && $output !== '') {
            @file_put_contents($output, 'clean-bytes');
        }

        return Process::result(output: '', errorOutput: '', exitCode: 0);
    });

    $qari = Qari::factory()->create();
    Storage::disk('recitations')->put('البنا/تلاوة نادرة.mp3', 'raw-bytes');

    $source = TilawatSource::create([
        'source_type' => 'library',
        'source_url' => 'البنا/تلاوة نادرة.mp3',
        'source_title' => 'تلاوة نادرة',
        'qari_id' => $qari->id,
        'surah_number' => null,
        'status' => 'pending',
        'created_by' => $qari->id,
    ]);

    (new IngestSourceAudio($source->id))->handle(new SourceCleanService);

    $tilawa = Tilawa::find($source->fresh()->tilawa_id);
    expect($tilawa->title_ar)->toBe('تلاوة نادرة');
});
