<?php

use App\Jobs\DetectAyahRange;
use App\Jobs\IngestSourceAudio;
use App\Livewire\Admin\FactoryQueue;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Models\TmpUpload;
use App\Models\User;
use App\Services\SourceCleanService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    Storage::fake('public');
});

it('lets an admin open the factory page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)->get(route('admin.publishing.factory'))->assertOk();
});

it('blocks regular users from the factory page', function () {
    $user = User::factory()->create()->assignRole('user');

    $this->actingAs($user)->get(route('admin.publishing.factory'))->assertForbidden();
});

it('queues an uploaded source for ingest', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();

    $token = (string) Str::uuid();
    Storage::disk('public')->put("tmp/uploads/{$creator->id}/{$token}.mp3", 'source-bytes');
    TmpUpload::create([
        'id' => $token,
        'disk' => 'public',
        'path' => "tmp/uploads/{$creator->id}/{$token}.mp3",
        'original_name' => 'recitation.mp3',
        'mime' => 'audio/mpeg',
        'size' => 12,
        'uploaded_by' => $creator->id,
        'expires_at' => now()->addHours(2),
    ]);

    $this->actingAs($creator)
        ->post(route('admin.publishing.ingest'), [
            'qari_id' => $qari->id,
            'surah_number' => 27,
            'audio_tmp' => $token,
        ])
        ->assertRedirect(route('admin.publishing.factory'));

    $source = TilawatSource::first();
    expect($source)->not->toBeNull()
        ->and($source->source_type)->toBe('upload')
        ->and($source->qari_id)->toBe($qari->id)
        ->and($source->surah_number)->toBe(27)
        ->and($source->source_url)->toStartWith("sources/{$qari->id}/")
        ->and($source->created_by)->toBe($creator->id);

    Storage::disk('public')->assertExists($source->source_url);
    Bus::assertDispatched(IngestSourceAudio::class);
});

it('cleans a source and creates a draft recitation with the surah-only title', function () {
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
    Storage::disk('public')->put("sources/{$qari->id}/raw.mp3", 'raw-bytes');

    $source = TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => "sources/{$qari->id}/raw.mp3",
        'qari_id' => $qari->id,
        'surah_number' => 27,
        'status' => 'pending',
        'created_by' => $qari->id, // any user id
    ]);

    (new IngestSourceAudio($source->id))->handle(new SourceCleanService);

    $source->refresh();
    expect($source->status)->toBe('completed')
        ->and($source->tilawa_id)->not->toBeNull();

    $tilawa = Tilawa::find($source->tilawa_id);
    expect($tilawa->status)->toBe('inactive')
        ->and($tilawa->surah_number)->toBe(27)
        ->and($tilawa->title_ar)->toBe('ما تيسر من سوره النمل')
        ->and($tilawa->ayah_from)->toBeNull();

    // ASR is not configured in the test env → no detection is dispatched.
    Bus::assertNotDispatched(DetectAyahRange::class);
});

it('dispatches ayah detection when ASR is configured', function () {
    Bus::fake();
    config(['publishing.transcription.gemini.api_key' => 'test-key']);

    Process::fake(function ($process) {
        $cmd = (array) $process->command;
        $output = end($cmd);
        if (is_string($output) && $output !== '') {
            @file_put_contents($output, 'clean-bytes');
        }

        return Process::result(output: '', errorOutput: '', exitCode: 0);
    });

    $qari = Qari::factory()->create();
    Storage::disk('public')->put("sources/{$qari->id}/raw.mp3", 'raw-bytes');
    $source = TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => "sources/{$qari->id}/raw.mp3",
        'qari_id' => $qari->id,
        'surah_number' => 27,
        'status' => 'pending',
        'created_by' => $qari->id,
    ]);

    (new IngestSourceAudio($source->id))->handle(new SourceCleanService);

    Bus::assertDispatched(DetectAyahRange::class);
});

it('marks the source failed when cleaning throws', function () {
    $source = TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => 'sources/1/missing.mp3',
        'qari_id' => Qari::factory()->create()->id,
        'surah_number' => 1,
        'status' => 'pending',
        'created_by' => 1,
    ]);

    $job = new IngestSourceAudio($source->id);

    try {
        $job->handle(new SourceCleanService);
    } catch (Throwable $e) {
        $job->failed($e);
    }

    expect($source->fresh()->status)->toBe('failed');
});

it('shows a review player for a source that has produced a recitation', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'uploaded_by' => $creator->id,
        'status' => 'inactive',
        'audio_path' => 'tilawat/ready.mp3',
    ]);
    TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => 'sources/1/x.mp3',
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'status' => 'completed',
        'tilawa_id' => $tilawa->id,
        'created_by' => $creator->id,
    ]);

    $this->actingAs($creator);

    Livewire::test(FactoryQueue::class)
        ->call('switchTab', 'review')
        ->assertSeeHtml($tilawa->audio_url);
});

it('keeps items being cleaned in Processing and cleaned ones in Review', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $procQari = Qari::factory()->create(['name_ar' => 'قارئ المعالجة']);
    $reviewQari = Qari::factory()->create(['name_ar' => 'قارئ المراجعة']);

    TilawatSource::create([
        'source_type' => 'library',
        'source_url' => 'lib/a.mp3',
        'qari_id' => $procQari->id,
        'surah_number' => 2,
        'status' => 'processing',
        'created_by' => $creator->id,
    ]);

    $tilawa = Tilawa::factory()->create([
        'qari_id' => $reviewQari->id,
        'uploaded_by' => $creator->id,
        'title_ar' => 'تلاوة للمراجعة',
        'audio_path' => 'tilawat/r.mp3',
        'status' => 'inactive',
    ]);
    TilawatSource::create([
        'source_type' => 'library',
        'source_url' => 'lib/b.mp3',
        'qari_id' => $reviewQari->id,
        'surah_number' => 2,
        'status' => 'completed',
        'tilawa_id' => $tilawa->id,
        'created_by' => $creator->id,
    ]);

    $this->actingAs($creator);

    Livewire::test(FactoryQueue::class)
        ->assertSee('قارئ المعالجة')
        ->assertDontSee('تلاوة للمراجعة')
        ->call('switchTab', 'review')
        ->assertSee('تلاوة للمراجعة')
        ->assertDontSee('قارئ المعالجة');
});

it('approves a reviewed recitation and publishes it live', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'uploaded_by' => $creator->id,
        'title_ar' => 'تلاوة للاعتماد',
        'status' => 'inactive',
        'review_status' => 'pending',
    ]);
    TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => 'sources/1/x.mp3',
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'status' => 'completed',
        'tilawa_id' => $tilawa->id,
        'created_by' => $creator->id,
    ]);

    $this->actingAs($creator);

    Livewire::test(FactoryQueue::class)
        ->call('switchTab', 'review')
        ->assertSee('تلاوة للاعتماد')
        ->call('approve', $tilawa->id)
        ->assertDontSee('تلاوة للاعتماد');

    $tilawa->refresh();
    expect($tilawa->status)->toBe('active')
        ->and($tilawa->review_status)->toBe('approved')
        ->and($tilawa->reviewed_by)->toBe($creator->id)
        ->and($tilawa->reviews()->where('action', 'approved')->exists())->toBeTrue();
});

it('locks in an edited ayah range when approving', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'uploaded_by' => $creator->id,
        'surah_number' => 2,
        'ayah_from' => null,
        'ayah_to' => null,
        'status' => 'inactive',
    ]);
    TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => 'sources/1/x.mp3',
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'status' => 'completed',
        'tilawa_id' => $tilawa->id,
        'created_by' => $creator->id,
    ]);

    $this->actingAs($creator);

    Livewire::test(FactoryQueue::class)
        ->call('switchTab', 'review')
        ->set("edits.{$tilawa->id}.from", 3)
        ->set("edits.{$tilawa->id}.to", 20)
        ->call('approve', $tilawa->id);

    $tilawa->refresh();
    expect($tilawa->status)->toBe('active')
        ->and($tilawa->ayah_from)->toBe(3)
        ->and($tilawa->ayah_to)->toBe(20)
        ->and($tilawa->title_ar)->toBe('ما تيسر من سوره البقرة من الايه 3 الي الايه 20');
});

it('hides the listen player while a source is still being cleaned', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => 'sources/1/x.mp3',
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'status' => 'processing',
        'created_by' => $creator->id,
    ]);

    $this->actingAs($creator);

    Livewire::test(FactoryQueue::class)
        ->assertDontSeeHtml('<audio');
});

it('selects every source in the active tab with one toggle', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    $ids = collect(range(1, 3))->map(fn () => TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => 'sources/1/'.Str::uuid().'.mp3',
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'status' => 'processing',
        'created_by' => $creator->id,
    ])->id);

    $this->actingAs($creator);

    $expected = $ids->map(fn ($id) => (string) $id)->sort()->values()->all();

    Livewire::test(FactoryQueue::class)
        ->set('selectAll', true)
        ->assertSet('selected', fn ($selected) => collect($selected)->sort()->values()->all() === $expected)
        ->set('selectAll', false)
        ->assertSet('selected', []);
});

it('bulk deletes the selected sources through the confirmation modal', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    $keep = TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => 'sources/1/keep.mp3',
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'status' => 'processing',
        'created_by' => $creator->id,
    ]);
    $drop = collect(range(1, 2))->map(fn () => TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => 'sources/1/'.Str::uuid().'.mp3',
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'status' => 'processing',
        'created_by' => $creator->id,
    ]));

    $this->actingAs($creator);

    Livewire::test(FactoryQueue::class)
        ->set('selected', $drop->map(fn ($s) => (string) $s->id)->all())
        ->call('confirmBulkDelete')
        ->assertSet('confirmingBulkDelete', true)
        ->call('performDelete')
        ->assertSet('confirmingBulkDelete', false)
        ->assertSet('selected', []);

    expect(TilawatSource::whereIn('id', $drop->pluck('id'))->count())->toBe(0)
        ->and(TilawatSource::whereKey($keep->id)->exists())->toBeTrue();
});

it('deletes a single source through the confirmation modal', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    $source = TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => 'sources/1/x.mp3',
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'status' => 'processing',
        'created_by' => $creator->id,
    ]);

    $this->actingAs($creator);

    Livewire::test(FactoryQueue::class)
        ->call('confirmDelete', $source->id)
        ->assertSet('confirmingSourceId', $source->id)
        ->call('performDelete')
        ->assertSet('confirmingSourceId', null);

    expect(TilawatSource::whereKey($source->id)->exists())->toBeFalse();
});

it('deletes a source even when its stored path is corrupted', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'uploaded_by' => $creator->id,
        'status' => 'inactive',
        'audio_path' => "tilawat/bad\x07audio.mp3",
    ]);
    $source = TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => "sources/{$qari->id}/bad\x07path.mp3",
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'status' => 'completed',
        'tilawa_id' => $tilawa->id,
        'created_by' => $creator->id,
    ]);

    $this->actingAs($creator);

    Livewire::test(FactoryQueue::class)
        ->call('confirmDelete', $source->id)
        ->call('performDelete')
        ->assertSet('confirmingSourceId', null);

    expect(TilawatSource::whereKey($source->id)->exists())->toBeFalse()
        ->and(Tilawa::whereKey($tilawa->id)->exists())->toBeFalse();
});

it('will not bulk delete sources owned by another creator', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $other = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    $foreign = TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => 'sources/1/foreign.mp3',
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'status' => 'processing',
        'created_by' => $other->id,
    ]);

    $this->actingAs($creator);

    Livewire::test(FactoryQueue::class)
        ->set('selected', [(string) $foreign->id])
        ->call('performDelete');

    expect(TilawatSource::whereKey($foreign->id)->exists())->toBeTrue();
});

it('lets an editor confirm the ayah range and finalize the title', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'uploaded_by' => $creator->id,
        'surah_number' => 2,
        'ayah_from' => null,
        'ayah_to' => null,
    ]);
    TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => 'sources/1/x.mp3',
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'status' => 'completed',
        'tilawa_id' => $tilawa->id,
        'created_by' => $creator->id,
    ]);

    $this->actingAs($creator);

    Livewire::test(FactoryQueue::class)
        ->set("edits.{$tilawa->id}.from", 3)
        ->set("edits.{$tilawa->id}.to", 20)
        ->call('confirm', $tilawa->id);

    $tilawa->refresh();
    expect($tilawa->ayah_from)->toBe(3)
        ->and($tilawa->ayah_to)->toBe(20)
        ->and($tilawa->ayah_confidence)->toBe('manual')
        ->and($tilawa->title_ar)->toBe('ما تيسر من سوره البقرة من الايه 3 الي الايه 20');
});
