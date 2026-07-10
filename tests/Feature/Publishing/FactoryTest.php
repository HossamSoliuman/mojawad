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
    config(['publishing.transcription.api_key' => 'test-key']);

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

it('shows a listen player for a source that has produced a recitation', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'uploaded_by' => $creator->id,
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
        ->assertSee(__('Listen'))
        ->assertSeeHtml($tilawa->audio_url);
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
