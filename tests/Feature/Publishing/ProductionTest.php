<?php

use App\Jobs\AlignSubtitles;
use App\Jobs\MasterTilawaAudio;
use App\Jobs\PublishToYoutube;
use App\Jobs\RenderBrandCover;
use App\Jobs\RenderBrandedVideo;
use App\Livewire\Admin\ProductionQueue;
use App\Models\Publication;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Models\User;
use App\Services\YoutubePublisher;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    Storage::fake('public');
});

/**
 * A recitation that has cleared the Factory (ayah range set) and is owned by
 * the given user, so it appears in that user's Production queue.
 */
function readyTilawa(User $owner, string $brandStatus = 'none'): Tilawa
{
    $qari = Qari::factory()->create();
    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'uploaded_by' => $owner->id,
        'surah_number' => 2,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'brand_status' => $brandStatus,
    ]);
    TilawatSource::create([
        'source_type' => 'upload',
        'source_url' => 'sources/1/x.mp3',
        'qari_id' => $qari->id,
        'surah_number' => 2,
        'status' => 'completed',
        'tilawa_id' => $tilawa->id,
        'created_by' => $owner->id,
    ]);

    return $tilawa;
}

it('lets an admin open the production page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)->get(route('admin.publishing.production'))->assertOk();
});

it('blocks regular users from the production page', function () {
    $user = User::factory()->create()->assignRole('user');

    $this->actingAs($user)->get(route('admin.publishing.production'))->assertForbidden();
});

it('dispatches the full brand chain when preparing', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator);

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)->call('prepare', $tilawa->id);

    expect($tilawa->fresh()->brand_status)->toBe('processing');

    Bus::assertChained([
        AlignSubtitles::class,
        RenderBrandCover::class,
        MasterTilawaAudio::class,
        RenderBrandedVideo::class,
    ]);
});

it('publishes to the podcast feed inline and activates the recitation', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('openPublish', $tilawa->id)
        ->set('platforms', ['podcast'])
        ->call('doPublish');

    $publication = Publication::where('tilawa_id', $tilawa->id)->where('platform', 'podcast')->first();
    expect($publication)->not->toBeNull()
        ->and($publication->status)->toBe('completed')
        ->and($tilawa->fresh()->status)->toBe('active');
});

it('does not publish when the brand assets are not ready', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'none');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->set('publishFor', $tilawa->id)
        ->set('platforms', ['podcast'])
        ->call('doPublish');

    expect(Publication::where('tilawa_id', $tilawa->id)->count())->toBe(0);
});

it('dispatches the youtube publisher when youtube is selected', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('openPublish', $tilawa->id)
        ->set('platforms', ['youtube'])
        ->call('doPublish');

    Bus::assertDispatched(PublishToYoutube::class);
});

it('marks a youtube publication failed when youtube is not configured', function () {
    config(['publishing.youtube.client_id' => null]);
    $tilawa = readyTilawa(User::factory()->create(), 'ready');
    $publication = Publication::factory()->youtube()->create(['tilawa_id' => $tilawa->id]);

    (new PublishToYoutube($publication->id))->handle(app(YoutubePublisher::class));

    expect($publication->fresh()->status)->toBe('failed')
        ->and($publication->fresh()->error)->toBe(__('YouTube is not configured.'));
});

it('only shows a creator their own recitations in production', function () {
    $owner = User::factory()->create()->assignRole('creator');
    $other = User::factory()->create()->assignRole('creator');
    readyTilawa($owner)->update(['title_ar' => 'تلاوة المالك']);
    readyTilawa($other)->update(['title_ar' => 'تلاوة الآخر']);

    $this->actingAs($owner);

    Livewire::test(ProductionQueue::class)
        ->assertSee('تلاوة المالك')
        ->assertDontSee('تلاوة الآخر');
});

it('serves completed podcast items in the RSS feed', function () {
    $qari = Qari::factory()->create(['name_ar' => 'الشيخ محمود']);
    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'title_ar' => 'ما تيسر من سوره البقرة',
        'master_audio_path' => 'published/audio/master.mp3',
        'brand_cover_path' => 'published/covers/cover.png',
        'duration' => 600,
    ]);
    Storage::disk('public')->put('published/audio/master.mp3', 'audio');
    Publication::factory()->podcast()->completed()->create(['tilawa_id' => $tilawa->id]);

    // A pending podcast item must not appear.
    $other = Tilawa::factory()->create(['master_audio_path' => 'published/audio/other.mp3', 'title_ar' => 'سورة يس']);
    Publication::factory()->podcast()->create(['tilawa_id' => $other->id, 'status' => 'pending']);

    $response = $this->get(route('podcast.feed'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/rss+xml');
    $response->assertSee('ما تيسر من سوره البقرة', false);
    $response->assertSee('published/audio/master.mp3', false);
    $response->assertDontSee('سورة يس', false);
});
