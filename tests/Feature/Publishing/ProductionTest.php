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
 * A recitation owned by the given user, optionally already seeded into a
 * production stage with a matching brand status.
 */
function readyTilawa(User $owner, string $brandStatus = 'none', ?string $stage = null): Tilawa
{
    $qari = Qari::factory()->create();
    $tilawa = Tilawa::factory()->create([
        'qari_id' => $qari->id,
        'uploaded_by' => $owner->id,
        'surah_number' => 2,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'brand_status' => $brandStatus,
        'production_stage' => $stage,
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

it('lists qaris with pickable recitations in the selection tab', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator);
    $tilawa->update(['title_ar' => 'تلاوة قابلة للاختيار']);
    $tilawa->qari->update(['name_ar' => 'الشيخ المختار']);

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->assertSet('tab', 'selection')
        ->assertSee('الشيخ المختار')
        ->call('selectQari', $tilawa->qari_id)
        ->assertSee('تلاوة قابلة للاختيار');
});

it('moves a recitation into preparation when it is selected', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator);

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)->call('addToProduction', $tilawa->id);

    expect($tilawa->fresh()->production_stage)->toBe('preparing');
});

it('only shows a creator their own recitations in selection', function () {
    $owner = User::factory()->create()->assignRole('creator');
    $other = User::factory()->create()->assignRole('creator');
    readyTilawa($owner)->qari->update(['name_ar' => 'قارئ المالك']);
    readyTilawa($other)->qari->update(['name_ar' => 'قارئ الآخر']);

    $this->actingAs($owner);

    Livewire::test(ProductionQueue::class)
        ->assertSee('قارئ المالك')
        ->assertDontSee('قارئ الآخر');
});

it('dispatches the full brand chain when preparing', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'none', 'preparing');

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

it('moves a ready recitation from preparation to publishing', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $ready = readyTilawa($creator, 'ready', 'preparing');
    $notReady = readyTilawa($creator, 'none', 'preparing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('moveToPublishing', $ready->id)
        ->call('moveToPublishing', $notReady->id);

    expect($ready->fresh()->production_stage)->toBe('publishing')
        ->and($notReady->fresh()->production_stage)->toBe('preparing');
});

it('publishes to the podcast feed inline and moves the recitation to published', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('openPublish', $tilawa->id)
        ->set('platforms', ['podcast'])
        ->call('doPublish');

    $publication = Publication::where('tilawa_id', $tilawa->id)->where('platform', 'podcast')->first();
    expect($publication)->not->toBeNull()
        ->and($publication->status)->toBe('completed')
        ->and($tilawa->fresh()->status)->toBe('active')
        ->and($tilawa->fresh()->production_stage)->toBe('published');
});

it('stores the composed per-platform meta on the publication', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('openPublish', $tilawa->id)
        ->set('platforms', ['youtube'])
        ->set('ytTitle', 'عنوان مخصص لليوتيوب')
        ->set('ytDescription', 'وصف مخصص')
        ->call('doPublish');

    $publication = Publication::where('tilawa_id', $tilawa->id)->where('platform', 'youtube')->first();
    expect($publication)->not->toBeNull()
        ->and($publication->meta['title'])->toBe('عنوان مخصص لليوتيوب')
        ->and($publication->meta['description'])->toBe('وصف مخصص');

    Bus::assertDispatched(PublishToYoutube::class);
});

it('can push an already published recitation to another platform', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'published');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('openPublish', $tilawa->id)
        ->assertSet('publishFor', $tilawa->id)
        ->set('platforms', ['youtube'])
        ->call('doPublish');

    expect(Publication::where('tilawa_id', $tilawa->id)->where('platform', 'youtube')->exists())->toBeTrue();
});

it('does not publish when the brand assets are not ready', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'none', 'publishing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->set('publishFor', $tilawa->id)
        ->set('platforms', ['podcast'])
        ->call('doPublish');

    expect(Publication::where('tilawa_id', $tilawa->id)->count())->toBe(0);
});

it('marks a youtube publication failed when youtube is not configured', function () {
    config(['publishing.youtube.client_id' => null]);
    $tilawa = readyTilawa(User::factory()->create(), 'ready', 'publishing');
    $publication = Publication::factory()->youtube()->create(['tilawa_id' => $tilawa->id]);

    (new PublishToYoutube($publication->id))->handle(app(YoutubePublisher::class));

    expect($publication->fresh()->status)->toBe('failed')
        ->and($publication->fresh()->error)->toBe(__('YouTube is not configured.'));
});

it('separates recitations across the pipeline stages', function () {
    $admin = User::factory()->create()->assignRole('admin');
    readyTilawa($admin, 'none', 'preparing')->update(['title_ar' => 'تلاوة قيد التجهيز']);
    readyTilawa($admin, 'ready', 'publishing')->update(['title_ar' => 'تلاوة قيد النشر']);
    readyTilawa($admin, 'ready', 'published')->update(['title_ar' => 'تلاوة منشورة']);

    $this->actingAs($admin);

    Livewire::test(ProductionQueue::class)
        ->set('tab', 'preparation')
        ->assertSee('تلاوة قيد التجهيز')
        ->assertDontSee('تلاوة قيد النشر')
        ->set('tab', 'publishing')
        ->assertSee('تلاوة قيد النشر')
        ->assertDontSee('تلاوة منشورة')
        ->set('tab', 'published')
        ->assertSee('تلاوة منشورة')
        ->assertDontSee('تلاوة قيد التجهيز');
});

it('prefills the card editor from the recitation and its saved settings', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'none', 'preparing');
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
    $tilawa = readyTilawa($creator, 'none', 'preparing');

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
    $tilawa = readyTilawa($creator, 'none', 'preparing');

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
    $tilawa = readyTilawa($creator, 'processing', 'preparing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)->call('prepare', $tilawa->id);

    Bus::assertNotDispatched(AlignSubtitles::class);
});

it('drops a recitation from production without deleting it from the site', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'preparing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('confirmRemove', $tilawa->id)
        ->assertSet('confirmingRemoveId', $tilawa->id)
        ->call('performRemove')
        ->assertSet('confirmingRemoveId', null);

    expect(Tilawa::find($tilawa->id))->not->toBeNull()
        ->and($tilawa->fresh()->production_stage)->toBeNull()
        ->and(TilawatSource::where('tilawa_id', $tilawa->id)->count())->toBe(1);
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
