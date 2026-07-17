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

it('shows recitations in production even when the ayah range is not confirmed', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $qari = Qari::factory()->create();
    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'uploaded_by' => $creator->id,
        'surah_number' => 2,
        'title_ar' => 'تلاوة بدون نطاق',
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

    Livewire::test(ProductionQueue::class)->assertSee('تلاوة بدون نطاق');
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

it('splits recitations between the create and publish tabs', function () {
    $admin = User::factory()->create()->assignRole('admin');
    readyTilawa($admin, 'none')->update(['title_ar' => 'تلاوة قيد الإنشاء']);
    readyTilawa($admin, 'ready')->update(['title_ar' => 'تلاوة جاهزة للنشر']);

    $this->actingAs($admin);

    Livewire::test(ProductionQueue::class)
        ->assertSet('tab', 'create')
        ->assertSee('تلاوة قيد الإنشاء')
        ->assertDontSee('تلاوة جاهزة للنشر')
        ->set('tab', 'publish')
        ->assertSee('تلاوة جاهزة للنشر')
        ->assertDontSee('تلاوة قيد الإنشاء');
});

it('filters the publish tab by published state', function () {
    $admin = User::factory()->create()->assignRole('admin');
    readyTilawa($admin, 'ready')->update(['title_ar' => 'تلاوة أولى']);
    $published = readyTilawa($admin, 'ready');
    $published->update(['title_ar' => 'تلاوة ثانية']);
    Publication::factory()->podcast()->completed()->create(['tilawa_id' => $published->id]);

    $this->actingAs($admin);

    Livewire::test(ProductionQueue::class)
        ->set('tab', 'publish')
        ->set('publishFilter', 'published')
        ->assertSee('تلاوة ثانية')
        ->assertDontSee('تلاوة أولى')
        ->set('publishFilter', 'unpublished')
        ->assertSee('تلاوة أولى')
        ->assertDontSee('تلاوة ثانية');
});

it('prefills the card editor from the recitation and its saved settings', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator);
    $tilawa->update(['description_ar' => 'وصف التلاوة']);

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('openEditor', $tilawa->id)
        ->assertSet('editingId', $tilawa->id)
        ->assertSet('cardQariName', $tilawa->fresh()->qari->name)
        ->assertSet('cardSurahName', $tilawa->fresh()->surah_label)
        ->assertSet('cardExtraText', 'وصف التلاوة')
        ->assertSet('cardAnimatePhoto', true)
        ->assertSet('cardAnimateText', true);
});

it('saves the card settings on the recitation', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator);

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('openEditor', $tilawa->id)
        ->set('cardQariName', 'قارئ مخصص')
        ->set('cardSurahName', 'الفاتحة')
        ->set('cardExtraText', 'تلاوة نادرة من الستينات')
        ->set('cardAnimateText', false)
        ->call('saveCard')
        ->assertSet('editingId', null);

    expect($tilawa->fresh()->brand_card)->toMatchArray([
        'qari_name' => 'قارئ مخصص',
        'surah_name' => 'الفاتحة',
        'extra_text' => 'تلاوة نادرة من الستينات',
        'animate_photo' => true,
        'animate_text' => false,
    ]);
});

it('saves and dispatches the render chain from the editor', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator);

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('openEditor', $tilawa->id)
        ->set('cardExtraText', 'نص متحرك')
        ->call('saveAndPrepare')
        ->assertSet('editingId', null);

    $fresh = $tilawa->fresh();
    expect($fresh->brand_status)->toBe('processing')
        ->and($fresh->brand_card['extra_text'])->toBe('نص متحرك');

    Bus::assertChained([
        AlignSubtitles::class,
        RenderBrandCover::class,
        MasterTilawaAudio::class,
        RenderBrandedVideo::class,
    ]);
});

it('does not re-dispatch the chain while a render is already processing', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'processing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)->call('prepare', $tilawa->id);

    Bus::assertNotDispatched(AlignSubtitles::class);
});

it('deletes a recitation through the confirmation modal', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator);

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('confirmDelete', $tilawa->id)
        ->assertSet('confirmingDeleteId', $tilawa->id)
        ->call('performDelete')
        ->assertSet('confirmingDeleteId', null);

    expect(Tilawa::find($tilawa->id))->toBeNull()
        ->and(TilawatSource::where('tilawa_id', $tilawa->id)->count())->toBe(0);
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
