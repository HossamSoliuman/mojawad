<?php

use App\Livewire\Admin\FacebookCampaign as FacebookCampaignComponent;
use App\Models\FacebookCampaign;
use App\Models\FacebookPost;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    FacebookPost::query()->delete();
    FacebookCampaign::query()->delete();

    $this->imageDirectory = storage_path('framework/testing/facebook-campaign-images');
    File::ensureDirectoryExists($this->imageDirectory);
    config(['publishing.campaign_images' => $this->imageDirectory]);

    $this->campaign = FacebookCampaign::factory()->create([
        'name' => 'Ramadan reflections',
        'slug' => 'ramadan-reflections',
        'goal' => 'Prepare a focused month of posts.',
    ]);

    $this->secondCampaign = FacebookCampaign::factory()->create([
        'name' => 'Qari stories',
        'slug' => 'qari-stories',
    ]);

    $this->draft = FacebookPost::factory()->for($this->campaign, 'campaign')->create([
        'title' => 'A short hadith reminder',
        'category' => 'hadith',
        'status' => 'draft',
        'hook' => 'A memorable first line',
        'body' => "First paragraph.\n\nSecond paragraph.",
        'cta' => 'Share this reminder.',
        'hashtags' => '#hadith #mojawad',
        'image_file' => 'hadith-reminder.png',
    ]);

    $this->ready = FacebookPost::factory()->for($this->secondCampaign, 'campaign')->ready()->create([
        'title' => 'The voice that crossed borders',
        'category' => 'qari_story',
        'hook' => 'A young reciter began in a small village.',
        'body' => 'His voice later reached listeners around the world.',
        'image_file' => 'qari-story.png',
    ]);
});

afterEach(function () {
    File::deleteDirectory($this->imageDirectory);
});

it('stores campaign posts in relational database records', function () {
    expect($this->campaign->posts)->toHaveCount(1)
        ->and($this->draft->campaign->is($this->campaign))->toBeTrue()
        ->and($this->draft->fullText())->toBe("A memorable first line\n\nFirst paragraph.\n\nSecond paragraph.\n\nShare this reminder.\n\n#hadith #mojawad")
        ->and($this->draft->hashtagList())->toBe(['#hadith', '#mojawad']);

    $this->assertDatabaseHas('facebook_posts', [
        'id' => $this->draft->id,
        'facebook_campaign_id' => $this->campaign->id,
        'title' => 'A short hadith reminder',
    ]);
});

it('renders a compact database-backed repository for editors', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.social.facebook'))
        ->assertOk()
        ->assertSeeLivewire('admin.facebook-campaign');

    Livewire::actingAs($admin)->test(FacebookCampaignComponent::class)
        ->assertSee(__('Facebook post repository'))
        ->assertSee('Ramadan reflections')
        ->assertSee('A short hadith reminder')
        ->assertSee('The voice that crossed borders')
        ->assertDontSee(__('Publishing copy'))
        ->call('togglePost', $this->draft->id)
        ->assertSee(__('Publishing copy'))
        ->assertSee('First paragraph.');
});

it('drops the visual type from posts entirely', function () {
    expect(Schema::hasColumn('facebook_posts', 'visual_type'))->toBeFalse()
        ->and(defined(FacebookPost::class.'::VISUAL_TYPES'))->toBeFalse();

    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)->test(FacebookCampaignComponent::class)
        ->call('editPost', $this->draft->id)
        ->assertDontSee(__('Visual type'))
        ->assertSee(__('Aspect ratio'));
});

it('splits the workspace into creation, scheduled, and posted tabs', function () {
    $admin = User::factory()->create()->assignRole('admin');

    FacebookPost::factory()->for($this->campaign, 'campaign')->scheduled()->create([
        'title' => 'Friday scheduled reminder',
        'category' => 'tafseer',
    ]);

    FacebookPost::factory()->for($this->campaign, 'campaign')->published()->create([
        'title' => 'Last week announcement',
        'category' => 'hadith',
    ]);

    $component = Livewire::actingAs($admin)->test(FacebookCampaignComponent::class)
        ->assertSet('stage', 'creation')
        ->assertSee('A short hadith reminder')
        ->assertSee('The voice that crossed borders')
        ->assertDontSee('Friday scheduled reminder')
        ->assertDontSee('Last week announcement');

    expect($component->instance()->stageCounts)->toBe(['creation' => 2, 'scheduled' => 1, 'posted' => 1]);

    $component
        ->call('selectStage', 'scheduled')
        ->assertSee('Friday scheduled reminder')
        ->assertDontSee('A short hadith reminder')
        ->call('selectStage', 'posted')
        ->assertSee('Last week announcement')
        ->assertDontSee('Friday scheduled reminder');
});

it('lists categories with stage counts in the side panel', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $component = Livewire::actingAs($admin)->test(FacebookCampaignComponent::class);
    $cards = collect($component->instance()->categoryCards)->keyBy('key');

    expect($cards->get('hadith')['count'])->toBe(1)
        ->and($cards->get('qari_story')['count'])->toBe(1)
        ->and($cards->get('tafseer')['count'])->toBe(0)
        ->and($cards)->not->toHaveKey(FacebookPost::UNCATEGORIZED);

    $component
        ->call('selectCategory', 'hadith')
        ->assertSet('category', 'hadith')
        ->assertSee('A short hadith reminder')
        ->assertDontSee('The voice that crossed borders');
});

it('groups posts saved without a category under an uncategorized entry', function () {
    $admin = User::factory()->create()->assignRole('admin');

    FacebookPost::factory()->for($this->campaign, 'campaign')->create([
        'title' => 'A post without a category',
        'category' => null,
    ]);

    $component = Livewire::actingAs($admin)->test(FacebookCampaignComponent::class);

    expect(collect($component->instance()->categoryCards)->firstWhere('key', FacebookPost::UNCATEGORIZED)['count'])->toBe(1);

    $component
        ->call('selectCategory', FacebookPost::UNCATEGORIZED)
        ->assertSee('A post without a category')
        ->assertDontSee('A short hadith reminder');
});

it('follows a saved post to the tab its new status belongs to', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)->test(FacebookCampaignComponent::class)
        ->assertSet('stage', 'creation')
        ->call('editPost', $this->draft->id)
        ->set('postStatus', 'published')
        ->call('savePost')
        ->assertHasNoErrors()
        ->assertSet('stage', 'posted')
        ->assertSee('A short hadith reminder');
});

it('filters posts by campaign status category and search text', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)->test(FacebookCampaignComponent::class)
        ->set('campaign', (string) $this->campaign->id)
        ->assertSee('A short hadith reminder')
        ->assertDontSee('The voice that crossed borders')
        ->set('campaign', 'all')
        ->set('status', 'ready')
        ->assertSee('The voice that crossed borders')
        ->assertDontSee('A short hadith reminder')
        ->call('resetFilters')
        ->set('category', 'hadith')
        ->assertSee('A short hadith reminder')
        ->assertDontSee('The voice that crossed borders')
        ->call('resetFilters')
        ->set('search', 'small village')
        ->assertSee('The voice that crossed borders')
        ->assertDontSee('A short hadith reminder');
});

it('creates a post from the repository form', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)->test(FacebookCampaignComponent::class)
        ->call('createPost')
        ->set('postCampaignId', (string) $this->campaign->id)
        ->set('postTitle', 'Nightly reflection')
        ->set('postCategory', 'tafseer')
        ->set('postStatus', 'ready')
        ->set('postHook', 'Pause with this verse tonight.')
        ->set('postBody', 'A concise reflection for the audience.')
        ->set('postHashtags', '#tafseer   #mojawad')
        ->set('postImageFile', '../nightly-reflection.png')
        ->call('savePost')
        ->assertHasNoErrors()
        ->assertSet('showPostForm', false)
        ->assertSee('Nightly reflection');

    $this->assertDatabaseHas('facebook_posts', [
        'facebook_campaign_id' => $this->campaign->id,
        'title' => 'Nightly reflection',
        'category' => 'tafseer',
        'status' => 'ready',
        'hashtags' => '#tafseer #mojawad',
        'image_file' => 'nightly-reflection.png',
        'created_by' => $admin->id,
    ]);
});

it('requires publishing time for scheduled posts', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)->test(FacebookCampaignComponent::class)
        ->call('createPost')
        ->set('postCampaignId', (string) $this->campaign->id)
        ->set('postTitle', 'Scheduled reflection')
        ->set('postStatus', 'scheduled')
        ->set('postHook', 'Opening line')
        ->set('postBody', 'Post body')
        ->call('savePost')
        ->assertHasErrors(['postScheduledFor' => 'required_if']);
});

it('updates and deletes repository posts', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)->test(FacebookCampaignComponent::class)
        ->call('editPost', $this->draft->id)
        ->assertSet('postTitle', 'A short hadith reminder')
        ->set('postTitle', 'Reviewed hadith reminder')
        ->set('postStatus', 'published')
        ->call('savePost')
        ->assertHasNoErrors();

    $this->draft->refresh();

    expect($this->draft->title)->toBe('Reviewed hadith reminder')
        ->and($this->draft->status)->toBe('published')
        ->and($this->draft->published_at)->not->toBeNull();

    Livewire::actingAs($admin)->test(FacebookCampaignComponent::class)
        ->call('confirmDeletePost', $this->draft->id)
        ->assertSet('deletePostId', $this->draft->id)
        ->call('deletePost');

    $this->assertDatabaseMissing('facebook_posts', ['id' => $this->draft->id]);
});

it('creates edits and safely removes empty campaigns', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $component = Livewire::actingAs($admin)->test(FacebookCampaignComponent::class)
        ->call('createCampaign')
        ->set('campaignName', 'Eid campaign')
        ->set('campaignGoal', 'Prepare Eid publishing copy.')
        ->call('saveCampaign')
        ->assertHasNoErrors();

    $eidCampaign = FacebookCampaign::query()->where('slug', 'eid-campaign')->firstOrFail();

    $component
        ->call('editCampaign', $eidCampaign->id)
        ->set('campaignName', 'Eid publishing campaign')
        ->call('saveCampaign')
        ->assertHasNoErrors()
        ->call('confirmDeleteCampaign', $eidCampaign->id)
        ->call('deleteCampaign');

    $this->assertDatabaseMissing('facebook_campaigns', ['id' => $eidCampaign->id]);

    Livewire::actingAs($admin)->test(FacebookCampaignComponent::class)
        ->call('confirmDeleteCampaign', $this->campaign->id)
        ->assertHasErrors('campaignDelete')
        ->assertSet('deleteCampaignId', null);
});

it('serves the image stored for a database post', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.social.poster', $this->draft))
        ->assertNotFound();

    file_put_contents($this->imageDirectory.'/hadith-reminder.png', 'png-bytes');

    expect($this->draft->hasImage())->toBeTrue()
        ->and($this->draft->imagePath())->toBe($this->imageDirectory.DIRECTORY_SEPARATOR.'hadith-reminder.png');

    $this->actingAs($admin)
        ->get(route('admin.social.poster', $this->draft))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.social.poster.download', $this->draft))
        ->assertOk()
        ->assertDownload('hadith-reminder.png');
});

it('allows creators to maintain campaign posts and rejects other roles', function () {
    $creator = User::factory()->create()->assignRole('creator');
    $reviewer = User::factory()->create()->assignRole('reviewer');

    Livewire::actingAs($creator)->test(FacebookCampaignComponent::class)
        ->call('editPost', $this->draft->id)
        ->assertSet('postTitle', 'A short hadith reminder');

    Livewire::actingAs($reviewer)->test(FacebookCampaignComponent::class)
        ->call('editPost', $this->draft->id)
        ->assertForbidden();
});
