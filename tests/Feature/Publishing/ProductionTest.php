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
use App\Services\AudioMasteringService;
use App\Services\YoutubePublisher;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;
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
        'brand_card' => $brandStatus === 'ready'
            ? ['card_image' => 'published/cards/ready.png']
            : null,
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

it('switches production tabs through GET links', function () {
    $creator = User::factory()->create()->assignRole('creator');

    $this->actingAs($creator);

    Livewire::withQueryParams(['tab' => 'preparation'])
        ->test(ProductionQueue::class)
        ->assertSet('tab', 'preparation')
        ->assertSeeHtml('href="'.route('admin.publishing.production', ['tab' => 'selection']).'"')
        ->assertSeeHtml('href="'.route('admin.publishing.production', ['tab' => 'preparation']).'"')
        ->assertSeeHtml('href="'.route('admin.publishing.production', ['tab' => 'publishing']).'"')
        ->assertSeeHtml('href="'.route('admin.publishing.production', ['tab' => 'published']).'"')
        ->assertDontSeeHtml('wire:click="$set(\'tab\'');
});

it('downloads the rendered publishing video for its owner', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');
    $tilawa->update([
        'title_en' => 'Surah Al Baqarah',
        'brand_video_path' => 'published/videos/ready.mp4',
    ]);
    Storage::disk('public')->put('published/videos/ready.mp4', 'video-content');

    $this->actingAs($creator)
        ->get(route('admin.publishing.production.download', $tilawa))
        ->assertDownload('surah-al-baqarah-'.$tilawa->id.'.mp4');
});

it('does not let another creator download a rendered publishing video', function () {
    $owner = User::factory()->create()->assignRole('creator');
    $otherCreator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($owner, 'ready', 'publishing');
    $tilawa->update(['brand_video_path' => 'published/videos/private.mp4']);
    Storage::disk('public')->put('published/videos/private.mp4', 'video-content');

    $this->actingAs($otherCreator)
        ->get(route('admin.publishing.production.download', $tilawa))
        ->assertForbidden();
});

it('reveals the rendered publishing video in the host file explorer', function () {
    Process::fake([
        '*' => Process::describe()->exitCode(PHP_OS_FAMILY === 'Windows' ? 1 : 0),
    ]);
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');
    $tilawa->update(['brand_video_path' => 'published/videos/reveal-ready.mp4']);
    Storage::disk('public')->put($tilawa->brand_video_path, 'video-content');

    $absolutePath = realpath(Storage::disk('public')->path($tilawa->brand_video_path));
    $expectedCommand = match (PHP_OS_FAMILY) {
        'Windows' => 'explorer.exe /select,"'.$absolutePath.'"',
        'Darwin' => ['open', '-R', $absolutePath],
        default => ['xdg-open', dirname($absolutePath)],
    };

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->set('tab', 'publishing')
        ->assertSee(__('Show in folder'))
        ->assertSeeHtml('wire:click.renderless="revealVideoInExplorer('.$tilawa->id.')"')
        ->call('revealVideoInExplorer', $tilawa->id);

    Process::assertRan(fn ($process): bool => $process->command === $expectedCommand);
});

it('does not reveal another creators rendered publishing video', function () {
    Process::fake();
    $owner = User::factory()->create()->assignRole('creator');
    $otherCreator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($owner, 'ready', 'publishing');
    $tilawa->update(['brand_video_path' => 'published/videos/private-reveal.mp4']);
    Storage::disk('public')->put($tilawa->brand_video_path, 'video-content');

    $this->actingAs($otherCreator);

    expect(fn () => Livewire::test(ProductionQueue::class)
        ->call('revealVideoInExplorer', $tilawa->id))
        ->toThrow(ModelNotFoundException::class);

    Process::assertDidntRun(fn (): bool => true);
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

it('removes quick render and dispatches the full brand chain from the card editor', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'none', 'preparing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->set('tab', 'preparation')
        ->assertSeeHtml('wire:click="openEditor('.$tilawa->id.')"')
        ->assertDontSeeHtml('wire:click="prepare('.$tilawa->id.')"')
        ->call('openEditor', $tilawa->id)
        ->call('saveAndPrepare');

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
    $legacyReady = readyTilawa($creator, 'ready', 'preparing');
    $legacyReady->update(['brand_card' => null]);

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('moveToPublishing', $ready->id)
        ->call('moveToPublishing', $notReady->id)
        ->call('moveToPublishing', $legacyReady->id);

    expect($ready->fresh()->production_stage)->toBe('publishing')
        ->and($notReady->fresh()->production_stage)->toBe('preparing')
        ->and($legacyReady->fresh()->production_stage)->toBe('preparing');
});

it('deletes the rendered video when moving back to preparation', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');
    Storage::disk('public')->put('published/videos/rollback.mp4', 'video');
    $tilawa->update(['brand_video_path' => 'published/videos/rollback.mp4']);
    $videoPath = $tilawa->fresh()->brand_video_path;

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('moveToPreparation', $tilawa->id);

    $tilawa->refresh();

    expect($tilawa->production_stage)->toBe('preparing')
        ->and($tilawa->brand_status)->toBe('none')
        ->and($tilawa->brand_video_path)->toBeNull();

    Storage::disk('public')->assertMissing($videoPath);
});

it('marks a platform as posted with its link and keeps the recitation in publishing', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->set("postLinks.{$tilawa->id}.youtube", 'https://youtu.be/abc123')
        ->call('markPosted', $tilawa->id, 'youtube')
        ->assertHasNoErrors();

    $publication = Publication::where('tilawa_id', $tilawa->id)->where('platform', 'youtube')->first();
    expect($publication)->not->toBeNull()
        ->and($publication->status)->toBe('completed')
        ->and($publication->external_url)->toBe('https://youtu.be/abc123')
        ->and($publication->published_at)->not->toBeNull()
        ->and($tilawa->fresh()->production_stage)->toBe('publishing');
});

it('moves the recitation to published once youtube and facebook are both posted', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('markPosted', $tilawa->id, 'youtube')
        ->call('markPosted', $tilawa->id, 'facebook');

    expect(Publication::where('tilawa_id', $tilawa->id)->where('status', 'completed')->count())->toBe(2)
        ->and(Publication::where('tilawa_id', $tilawa->id)->first()->external_url)->toBeNull()
        ->and($tilawa->fresh()->production_stage)->toBe('published');
});

it('rejects an invalid post link', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->set("postLinks.{$tilawa->id}.facebook", 'not a link')
        ->call('markPosted', $tilawa->id, 'facebook')
        ->assertHasErrors("postLinks.{$tilawa->id}.facebook");

    expect(Publication::where('tilawa_id', $tilawa->id)->exists())->toBeFalse();
});

it('only marks youtube and facebook as posted', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('markPosted', $tilawa->id, 'podcast')
        ->assertNotFound();

    expect(Publication::where('tilawa_id', $tilawa->id)->exists())->toBeFalse();
});

it('undoes a posted platform while the recitation is still in publishing', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('markPosted', $tilawa->id, 'youtube')
        ->call('unmarkPosted', $tilawa->id, 'youtube');

    expect(Publication::where('tilawa_id', $tilawa->id)->exists())->toBeFalse();
});

it('mixes the publishing queue by qari instead of preparation order', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $first = Qari::factory()->create();
    $second = Qari::factory()->create();

    $this->actingAs($creator);

    foreach ([$first, $first, $first, $second, $second] as $qari) {
        readyTilawa($creator, 'ready', 'publishing')->update(['qari_id' => $qari->id]);
    }

    $qaris = Livewire::test(ProductionQueue::class)->instance()->publishingList->pluck('qari_id')->all();

    expect($qaris)->toBe([$first->id, $second->id, $first->id, $second->id, $first->id]);
});

it('keeps a half-posted recitation on top and continues the mix from the latest post', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $first = Qari::factory()->create();
    $second = Qari::factory()->create();

    $this->actingAs($creator);

    $lastPosted = readyTilawa($creator, 'ready', 'published');
    $lastPosted->update(['qari_id' => $first->id]);
    Publication::create(['tilawa_id' => $lastPosted->id, 'platform' => 'youtube', 'status' => 'completed', 'published_at' => now()->subDay()]);

    $waiting = collect([$first, $first, $second])->map(function (Qari $qari) use ($creator) {
        $tilawa = readyTilawa($creator, 'ready', 'publishing');
        $tilawa->update(['qari_id' => $qari->id]);

        return $tilawa;
    });

    $halfPosted = readyTilawa($creator, 'ready', 'publishing');
    $halfPosted->update(['qari_id' => $second->id]);
    Publication::create(['tilawa_id' => $halfPosted->id, 'platform' => 'youtube', 'status' => 'completed', 'published_at' => now()]);

    $list = Livewire::test(ProductionQueue::class)->instance()->publishingList;

    expect($list->first()->id)->toBe($halfPosted->id)
        ->and($list->pluck('qari_id')->all())->toBe([$second->id, $first->id, $second->id, $first->id])
        ->and($list->pluck('id')->all())->toContain(...$waiting->pluck('id')->all());
});

it('lists the publishing queue by posting date with a schedule brief', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-28 10:00', config('publishing.schedule.timezone')));

    $creator = User::factory()->create()->assignRole('creator');
    $kahf = readyTilawa($creator, 'ready', 'publishing');
    $kahf->update(['surah_number' => 18, 'title_ar' => 'تلاوة سورة الكهف']);
    $regular = readyTilawa($creator, 'ready', 'publishing');

    $this->actingAs($creator);

    $component = Livewire::test(ProductionQueue::class)->set('tab', 'publishing');
    $plan = $component->instance()->postingPlan;

    expect($component->instance()->publishingList->pluck('id')->all())->toBe([$regular->id, $kahf->id])
        ->and($plan->last()['at']->isFriday())->toBeTrue()
        ->and($plan->last()['kahf'])->toBeTrue();

    $component->assertSee(__('Posting schedule'))
        ->assertSee(__('Al-Kahf Friday'))
        ->assertSee(__('Al-Kahf waiting: :count', ['count' => 1]));
});

it('records a post marked ahead of its slot at that slot and keeps the slot once it leaves the queue', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-29 21:30', config('publishing.schedule.timezone'))->utc());

    $creator = User::factory()->create()->assignRole('creator');
    $kahf = readyTilawa($creator, 'ready', 'publishing');
    $kahf->update(['surah_number' => 18, 'title_ar' => 'تلاوة سورة الكهف']);
    readyTilawa($creator, 'ready', 'publishing');
    readyTilawa($creator, 'ready', 'publishing');

    $this->actingAs($creator);

    $component = Livewire::test(ProductionQueue::class)
        ->set('tab', 'publishing')
        ->call('markPosted', $kahf->id, 'youtube')
        ->call('markPosted', $kahf->id, 'facebook');

    $overdue = $component->instance()->publishingList->first();
    $component->call('markPosted', $overdue->id, 'youtube');

    $friday = CarbonImmutable::parse('2026-10-30 08:00', config('publishing.schedule.timezone'));
    $kahfPosts = Publication::where('tilawa_id', $kahf->id)->get();
    $plan = $component->instance()->postingPlan;

    expect($kahfPosts)->toHaveCount(2)
        ->and($kahfPosts->every(fn (Publication $publication) => $publication->published_at->equalTo($friday)))->toBeTrue()
        ->and($kahf->fresh()->production_stage)->toBe('published')
        ->and(Publication::where('tilawa_id', $overdue->id)->first()->published_at->equalTo(now()))->toBeTrue()
        ->and($plan->map(fn (array $entry) => $entry['at']->format('D H:i').($entry['posted'] ? ' posted' : ''))->all())
        ->toBe(['Thu 21:30', 'Fri 08:00 posted', 'Sat 20:00'])
        ->and($component->instance()->publishingList->pluck('id'))->not->toContain($kahf->id);

    $component->assertSee(__('Scheduled'));
});

it('shows copy-ready youtube and facebook texts without the referral link or podcast', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');
    $tilawa->update([
        'title_ar' => 'A ready recitation',
        'brand_video_path' => 'published/videos/share-ready.mp4',
    ]);
    Storage::disk('public')->put('published/videos/share-ready.mp4', 'video-content');
    $videoFilePath = str_replace('/', DIRECTORY_SEPARATOR, Storage::disk('public')->path($tilawa->brand_video_path));

    $this->actingAs($creator);

    $component = Livewire::test(ProductionQueue::class)
        ->set('tab', 'publishing')
        ->assertSee(__('Video title'))
        ->assertSee(__('Video description'))
        ->assertSee(__('Post text'))
        ->assertSee(__('Mark as posted'))
        ->assertSee(__('Upload to YouTube'))
        ->assertSee(__('Copy file path'))
        ->assertSee(Js::from($videoFilePath)->toHtml(), false)
        ->assertSee(__('Download video'))
        ->assertSee(route('admin.publishing.production.download', $tilawa), false)
        ->assertDontSee('utm_source')
        ->assertDontSee(__('Listen to more recitations on Mojawad:'))
        ->assertDontSee(__('Podcast (Spotify / Anghami)'))
        ->assertDontSee(__('Post title'))
        ->assertDontSee(__('Copy all'));

    $copy = $component->instance()->publishingCopy[$tilawa->id];

    expect($copy['youtube_title'])->toContain('A ready recitation')
        ->and($copy['youtube_description'])->toContain('A ready recitation')
        ->and($copy['facebook_text'])->toContain('#القرآن_الكريم')
        ->and($copy['facebook_text'])->not->toContain('mojawad.com');
});

it('moves a published recitation back to publishing to post it elsewhere', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'published');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('moveBackToPublishing', $tilawa->id);

    expect($tilawa->fresh()->production_stage)->toBe('publishing');
});

it('shows where a published recitation went live on youtube and facebook', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'published');
    Publication::factory()->youtube()->completed()->create([
        'tilawa_id' => $tilawa->id,
        'external_url' => 'https://youtu.be/live123',
    ]);

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->set('tab', 'published')
        ->assertSee('https://youtu.be/live123', false)
        ->assertSee(__('Open post'))
        ->assertSee(__('Not published yet'))
        ->assertSee(__('Back to publishing'))
        ->assertDontSee(__('Podcast (Spotify / Anghami)'));
});

it('adds the live link to a platform posted without one', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'published');
    $postedAt = now()->subDay()->startOfSecond();
    $publication = Publication::factory()->youtube()->completed()->create([
        'tilawa_id' => $tilawa->id,
        'external_url' => null,
        'published_at' => $postedAt,
    ]);

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->set('tab', 'published')
        ->assertSee(__('Paste the link'))
        ->set("postLinks.{$tilawa->id}.youtube", 'https://youtu.be/late456')
        ->call('savePostLink', $tilawa->id, 'youtube')
        ->assertHasNoErrors();

    expect($publication->fresh()->external_url)->toBe('https://youtu.be/late456')
        ->and($publication->fresh()->published_at->equalTo($postedAt))->toBeTrue();
});

it('requires a valid link when adding it later', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'published');
    $publication = Publication::factory()->youtube()->completed()->create([
        'tilawa_id' => $tilawa->id,
        'external_url' => null,
    ]);

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('savePostLink', $tilawa->id, 'youtube')
        ->assertHasErrors("postLinks.{$tilawa->id}.youtube");

    expect($publication->fresh()->external_url)->toBeNull();
});

it('moves recitations published only through the podcast back to publishing', function () {
    $owner = User::factory()->create();
    $podcastOnly = readyTilawa($owner, 'ready', 'published');
    Publication::factory()->podcast()->completed()->create(['tilawa_id' => $podcastOnly->id]);
    $failedUpload = readyTilawa($owner, 'ready', 'published');
    Publication::factory()->youtube()->failed()->create(['tilawa_id' => $failedUpload->id]);
    $postedOnFacebook = readyTilawa($owner, 'ready', 'published');
    Publication::factory()->facebook()->completed()->create(['tilawa_id' => $postedOnFacebook->id]);

    (require database_path('migrations/2026_10_29_135017_move_unposted_recitations_back_to_publishing.php'))->up();

    expect($podcastOnly->fresh()->production_stage)->toBe('publishing')
        ->and($failedUpload->fresh()->production_stage)->toBe('publishing')
        ->and($postedOnFacebook->fresh()->production_stage)->toBe('published');
});

it('does not mark as posted without a rendered customized card', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');
    $tilawa->update(['brand_card' => null]);

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('markPosted', $tilawa->id, 'youtube');

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
        ->assertSet('cardRareBadge', 'تلاوة نادرة')
        ->assertSee('ما تيسر من سورة '.$tilawa->fresh()->surah_label)
        ->assertSee('لأسطورة التلاوة')
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
        ->set('cardRareBadge', 'نسخة إذاعية نادرة')
        ->set('cardAnimateText', false)
        ->call('saveCard')
        ->assertSet('editingId', null);

    expect($tilawa->fresh()->brand_card)->toMatchArray([
        'qari_name' => 'قارئ مخصص',
        'surah_name' => 'الفاتحة',
        'extra_text' => 'تلاوة نادرة من الستينات',
        'rare_badge' => 'نسخة إذاعية نادرة',
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

    Livewire::test(ProductionQueue::class)
        ->call('openEditor', $tilawa->id)
        ->assertSet('editingId', null)
        ->call('saveAndPrepare');

    Bus::assertNotDispatched(AlignSubtitles::class);
});

it('retries a failed render and re-dispatches the chain', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'failed', 'preparing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)->call('retryRender', $tilawa->id);

    expect($tilawa->fresh()->brand_status)->toBe('processing');

    Bus::assertChained([
        AlignSubtitles::class,
        RenderBrandCover::class,
        MasterTilawaAudio::class,
        RenderBrandedVideo::class,
    ]);
});

it('does not retry a render that has not failed', function () {
    Bus::fake();
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'none', 'preparing');

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)->call('retryRender', $tilawa->id);

    Bus::assertNotDispatched(AlignSubtitles::class);
    expect($tilawa->fresh()->brand_status)->toBe('none');
});

it('reuses an existing master instead of re-mastering on retry', function () {
    $tilawa = readyTilawa(User::factory()->create(), 'failed', 'preparing');
    $tilawa->update(['master_audio_path' => 'published/audio/existing.mp3']);
    Storage::disk('public')->put('published/audio/existing.mp3', 'audio');

    $mastering = Mockery::mock(AudioMasteringService::class);
    $mastering->shouldNotReceive('master');

    (new MasterTilawaAudio($tilawa->id))->handle($mastering);

    expect($tilawa->fresh()->master_audio_path)->toBe('published/audio/existing.mp3');
});

it('drops a recitation from production without deleting it from the site', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $tilawa = readyTilawa($creator, 'ready', 'publishing');
    Storage::disk('public')->put('published/videos/remove.mp4', 'video');
    $tilawa->update(['brand_video_path' => 'published/videos/remove.mp4']);
    $videoPath = $tilawa->fresh()->brand_video_path;

    $this->actingAs($creator);

    Livewire::test(ProductionQueue::class)
        ->call('confirmRemove', $tilawa->id)
        ->assertSet('confirmingRemoveId', $tilawa->id)
        ->call('performRemove')
        ->assertSet('confirmingRemoveId', null);

    expect(Tilawa::find($tilawa->id))->not->toBeNull()
        ->and($tilawa->fresh()->production_stage)->toBeNull()
        ->and($tilawa->fresh()->brand_status)->toBe('none')
        ->and($tilawa->fresh()->brand_video_path)->toBeNull()
        ->and(TilawatSource::where('tilawa_id', $tilawa->id)->count())->toBe(1);

    Storage::disk('public')->assertMissing($videoPath);
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
