<?php

use App\Jobs\ImportTilawaFromYoutube;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Models\User;
use App\Services\YoutubeImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    Storage::fake('public');
});

it('lets an admin open the studio page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)->get(route('admin.studio.index'))->assertOk();
});

it('blocks regular users from the studio page', function () {
    $user = User::factory()->create()->assignRole('user');

    $this->actingAs($user)->get(route('admin.studio.index'))->assertForbidden();
});

it('queues a source and dispatches a job for a pasted link', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();

    $this->actingAs($creator)
        ->post(route('admin.studio.import'), [
            'qari_id' => $qari->id,
            'urls' => 'https://www.youtube.com/watch?v=abcdefghijk',
        ])
        ->assertRedirect(route('admin.studio.index'));

    $source = TilawatSource::first();
    expect($source)->not->toBeNull()
        ->and($source->status)->toBe('pending')
        ->and($source->source_video_id)->toBe('abcdefghijk')
        ->and($source->qari_id)->toBe($qari->id)
        ->and($source->created_by)->toBe($creator->id);

    Bus::assertDispatched(ImportTilawaFromYoutube::class);
});

it('rejects non-youtube links', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();

    $this->actingAs($creator)
        ->post(route('admin.studio.import'), [
            'qari_id' => $qari->id,
            'urls' => 'https://example.com/not-youtube',
        ])
        ->assertSessionHasErrors('urls');

    expect(TilawatSource::count())->toBe(0);
});

it('de-duplicates repeated links in one submission', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();

    $this->actingAs($creator)
        ->post(route('admin.studio.import'), [
            'qari_id' => $qari->id,
            'urls' => "https://www.youtube.com/watch?v=abcdefghijk\nhttps://youtu.be/abcdefghijk",
        ]);

    expect(TilawatSource::count())->toBe(1);
    Bus::assertDispatchedTimes(ImportTilawaFromYoutube::class, 1);
});

it('parses an uploaded link list into sources', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();

    $contents = "https://www.youtube.com/watch?v=aaaaaaaaaaa\nhttps://youtu.be/bbbbbbbbbbb\nhttps://www.youtube.com/watch?v=aaaaaaaaaaa\n";
    $file = UploadedFile::fake()->createWithContent('links.txt', $contents);

    $this->actingAs($creator)
        ->post(route('admin.studio.import-file'), [
            'qari_id' => $qari->id,
            'file' => $file,
        ])
        ->assertRedirect(route('admin.studio.index'));

    expect(TilawatSource::count())->toBe(2);
    Bus::assertDispatchedTimes(ImportTilawaFromYoutube::class, 2);
});

it('imports a tilawa when the job succeeds', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    $source = TilawatSource::factory()->create([
        'qari_id' => $qari->id,
        'created_by' => $creator->id,
        'status' => 'pending',
        'source_title' => null,
    ]);

    $fake = Mockery::mock(YoutubeImportService::class);
    $fake->shouldReceive('probe')->once()->andReturn([
        'video_id' => 'abcdefghijk',
        'title' => 'My Recitation Title',
        'duration' => 180,
        'thumbnail' => 'https://i.ytimg.com/vi/abcdefghijk/hqdefault.jpg',
    ]);
    $fake->shouldReceive('download')->once()->andReturn('tilawat/imported.mp3');
    $this->app->instance(YoutubeImportService::class, $fake);

    (new ImportTilawaFromYoutube($source->id))->handle($fake);

    $source->refresh();
    expect($source->status)->toBe('completed')
        ->and($source->tilawa_id)->not->toBeNull()
        ->and($source->source_title)->toBe('My Recitation Title')
        ->and($source->processed_at)->not->toBeNull();

    $tilawa = Tilawa::find($source->tilawa_id);
    expect($tilawa)->not->toBeNull()
        ->and($tilawa->title_ar)->toBe('My Recitation Title')
        ->and($tilawa->qari_id)->toBe($qari->id)
        ->and($tilawa->audio_path)->toBe('tilawat/imported.mp3')
        ->and($tilawa->uploaded_by)->toBe($creator->id)
        ->and($tilawa->status)->toBe('pending');
});

it('publishes immediately when an admin imports', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $source = TilawatSource::factory()->create(['created_by' => $admin->id, 'status' => 'pending']);

    $fake = Mockery::mock(YoutubeImportService::class);
    $fake->shouldReceive('probe')->andReturn([
        'video_id' => 'abcdefghijk', 'title' => 'Admin Import', 'duration' => 60, 'thumbnail' => null,
    ]);
    $fake->shouldReceive('download')->andReturn('tilawat/admin.mp3');

    (new ImportTilawaFromYoutube($source->id))->handle($fake);

    $tilawa = Tilawa::find($source->fresh()->tilawa_id);
    expect($tilawa->status)->toBe('active')
        ->and($tilawa->review_status)->toBe('approved');
});

it('marks the source failed when the job throws', function () {
    $source = TilawatSource::factory()->create(['status' => 'pending']);

    $fake = Mockery::mock(YoutubeImportService::class);
    $fake->shouldReceive('probe')->andThrow(new RuntimeException('yt-dlp unavailable'));

    $job = new ImportTilawaFromYoutube($source->id);

    try {
        $job->handle($fake);
    } catch (Throwable $e) {
        $job->failed($e);
    }

    $source->refresh();
    expect($source->status)->toBe('failed')
        ->and($source->error)->toContain('yt-dlp unavailable');
});

it('fails on a duplicate already-completed video', function () {
    $qari = Qari::factory()->create();
    TilawatSource::factory()->completed()->create(['source_video_id' => 'abcdefghijk']);
    $source = TilawatSource::factory()->create([
        'qari_id' => $qari->id,
        'status' => 'pending',
        'source_video_id' => null,
    ]);

    $fake = Mockery::mock(YoutubeImportService::class);
    $fake->shouldReceive('probe')->andReturn([
        'video_id' => 'abcdefghijk', 'title' => 'Dup', 'duration' => 10, 'thumbnail' => null,
    ]);
    $fake->shouldNotReceive('download');

    $job = new ImportTilawaFromYoutube($source->id);

    try {
        $job->handle($fake);
    } catch (Throwable $e) {
        $job->failed($e);
    }

    expect($source->fresh()->status)->toBe('failed');
});

it('lets a creator retry their own failed source', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $source = TilawatSource::factory()->failed()->create(['created_by' => $creator->id]);

    $this->actingAs($creator)
        ->post(route('admin.studio.retry', $source))
        ->assertRedirect(route('admin.studio.index'));

    expect($source->fresh()->status)->toBe('pending');
    Bus::assertDispatched(ImportTilawaFromYoutube::class);
});

it('prevents a creator from deleting another creators source', function () {
    $owner = User::factory()->create()->assignRole('creator');
    $other = User::factory()->create()->assignRole('creator');
    $source = TilawatSource::factory()->create(['created_by' => $owner->id]);

    $this->actingAs($other)
        ->delete(route('admin.studio.destroy', $source))
        ->assertForbidden();
});
