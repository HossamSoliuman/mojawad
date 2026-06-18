<?php

use App\Jobs\RenderVideoClip;
use App\Models\Tilawa;
use App\Models\User;
use App\Models\VideoClip;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    Storage::fake('public');
});

$pngDataUrl = 'data:image/png;base64,'.base64_encode('fake-png-bytes');

it('lets an admin open the clips page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)->get(route('admin.clips.index'))->assertOk();
});

it('blocks regular users from the clips page', function () {
    $user = User::factory()->create()->assignRole('user');

    $this->actingAs($user)->get(route('admin.clips.index'))->assertForbidden();
});

it('creates a clip, stores the overlay and dispatches the render job', function () use ($pngDataUrl) {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = Tilawa::factory()->approved()->create();

    $this->actingAs($creator)
        ->post(route('admin.clips.store'), [
            'tilawa_id' => $tilawa->id,
            'template' => 'dark-waveform',
            'clip_start' => 30,
            'clip_duration' => 30,
            'caption_ar' => 'وصف المقطع',
            'overlay' => $pngDataUrl,
        ])
        ->assertRedirect(route('admin.clips.index'));

    $clip = VideoClip::first();
    expect($clip)->not->toBeNull()
        ->and($clip->status)->toBe('pending')
        ->and($clip->tilawa_id)->toBe($tilawa->id)
        ->and($clip->created_by)->toBe($creator->id)
        ->and($clip->clip_duration)->toBe(30)
        ->and($clip->overlay_path)->not->toBeNull();

    Storage::disk('public')->assertExists($clip->overlay_path);
    Bus::assertDispatched(RenderVideoClip::class);
});

it('rejects a clip longer than the cap', function () use ($pngDataUrl) {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = Tilawa::factory()->approved()->create();

    $this->actingAs($creator)
        ->post(route('admin.clips.store'), [
            'tilawa_id' => $tilawa->id,
            'template' => 'dark-waveform',
            'clip_start' => 0,
            'clip_duration' => 999,
            'overlay' => $pngDataUrl,
        ])
        ->assertSessionHasErrors('clip_duration');

    expect(VideoClip::count())->toBe(0);
});

it('rejects an unknown template', function () use ($pngDataUrl) {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = Tilawa::factory()->approved()->create();

    $this->actingAs($creator)
        ->post(route('admin.clips.store'), [
            'tilawa_id' => $tilawa->id,
            'template' => 'rainbow',
            'clip_start' => 0,
            'clip_duration' => 30,
            'overlay' => $pngDataUrl,
        ])
        ->assertSessionHasErrors('template');
});

it('renders a clip end to end when ffmpeg succeeds', function () {
    Process::fake(function ($process) {
        $cmd = (array) $process->command;
        $output = end($cmd);
        if (is_string($output) && $output !== '') {
            @file_put_contents($output, 'binary');
        }

        return Process::result(output: '', errorOutput: '', exitCode: 0);
    });

    $tilawa = Tilawa::factory()->approved()->create();
    Storage::disk('public')->put($tilawa->audio_path, 'audio-bytes');

    $clip = VideoClip::factory()->create([
        'tilawa_id' => $tilawa->id,
        'template' => 'dark-waveform',
        'clip_duration' => 30,
        'status' => 'pending',
    ]);
    Storage::disk('public')->put($clip->overlay_path, 'png-bytes');

    (new RenderVideoClip($clip->id))->handle();

    $clip->refresh();
    expect($clip->status)->toBe('completed')
        ->and($clip->output_path)->not->toBeNull()
        ->and($clip->poster_path)->not->toBeNull()
        ->and($clip->duration)->toBe(30);

    Storage::disk('public')->assertExists($clip->output_path);
    Process::assertRanTimes(fn () => true, 2);
});

it('marks the clip failed when the source audio is missing', function () {
    $tilawa = Tilawa::factory()->approved()->create();
    $clip = VideoClip::factory()->create(['tilawa_id' => $tilawa->id, 'status' => 'pending']);

    $job = new RenderVideoClip($clip->id);

    try {
        $job->handle();
    } catch (Throwable $e) {
        $job->failed($e);
    }

    $clip->refresh();
    expect($clip->status)->toBe('failed')
        ->and($clip->error)->toContain('Source audio not found');
});

it('saves a posted tiktok url', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $clip = VideoClip::factory()->completed()->create(['created_by' => $creator->id]);

    $this->actingAs($creator)
        ->patch(route('admin.clips.tiktok', $clip), ['tiktok_url' => 'https://www.tiktok.com/@x/video/123'])
        ->assertRedirect(route('admin.clips.index'));

    expect($clip->fresh()->tiktok_url)->toBe('https://www.tiktok.com/@x/video/123');
});

it('prevents a creator from deleting another creators clip', function () {
    $owner = User::factory()->create()->assignRole('creator');
    $other = User::factory()->create()->assignRole('creator');
    $clip = VideoClip::factory()->create(['created_by' => $owner->id]);

    $this->actingAs($other)
        ->delete(route('admin.clips.destroy', $clip))
        ->assertForbidden();
});
