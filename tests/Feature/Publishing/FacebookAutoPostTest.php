<?php

use App\Exceptions\FacebookPublishException;
use App\Jobs\PublishFacebookPost;
use App\Livewire\Admin\FacebookCampaign as FacebookCampaignComponent;
use App\Models\FacebookCampaign;
use App\Models\FacebookPost;
use App\Models\User;
use App\Services\FacebookPostPublisher;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    FacebookPost::query()->delete();
    FacebookCampaign::query()->delete();

    $this->imageDirectory = storage_path('framework/testing/facebook-auto-post-images');
    File::ensureDirectoryExists($this->imageDirectory);

    config([
        'publishing.campaign_images' => $this->imageDirectory,
        'publishing.facebook.page_id' => '1234567890',
        'publishing.facebook.access_token' => 'page-token',
        'publishing.facebook.graph_version' => 'v21.0',
    ]);

    $this->campaign = FacebookCampaign::factory()->create(['name' => 'Ramadan reflections']);

    $this->post = FacebookPost::factory()->for($this->campaign, 'campaign')->create([
        'title' => 'A short hadith reminder',
        'status' => 'scheduled',
        'scheduled_for' => now()->subMinutes(5),
        'hook' => 'A memorable first line',
        'body' => 'The body of the post.',
        'cta' => 'Share this reminder.',
        'hashtags' => '#hadith #mojawad',
        'image_file' => null,
    ]);
});

afterEach(function () {
    File::deleteDirectory($this->imageDirectory);
});

function fbAdmin(): User
{
    return User::factory()->create()->assignRole('admin');
}

function fbPostImage(FacebookPost $post, string $directory): void
{
    $post->update(['image_file' => 'poster.png']);
    File::put($directory.DIRECTORY_SEPARATOR.'poster.png', 'fake-png-bytes');
}

/** Answers the identity probe the scheduler makes before it claims anything. */
function fbHealthyToken(): void
{
    Http::fake([
        'graph.facebook.com/v21.0/me?*' => Http::response(['id' => '1234567890', 'name' => 'Mojawad.org']),
    ]);
}

it('queues scheduled posts whose time has come', function () {
    Queue::fake();
    fbHealthyToken();

    $this->artisan('facebook:publish-due')->assertSuccessful();

    Queue::assertPushed(PublishFacebookPost::class, fn (PublishFacebookPost $job): bool => $job->postId === $this->post->id);

    expect($this->post->fresh()->publish_attempted_at)->not->toBeNull();
});

it('leaves posts alone until their scheduled time arrives', function () {
    Queue::fake();
    $this->post->update(['scheduled_for' => now()->addHour()]);

    $this->artisan('facebook:publish-due')->assertSuccessful();

    Queue::assertNothingPushed();
    expect($this->post->fresh()->publish_attempted_at)->toBeNull();
});

it('never queues a post twice', function () {
    Queue::fake();
    fbHealthyToken();

    $this->artisan('facebook:publish-due')->assertSuccessful();
    $this->artisan('facebook:publish-due')->assertSuccessful();

    Queue::assertPushed(PublishFacebookPost::class, 1);
});

it('skips posts that are already live on the page', function () {
    Queue::fake();
    $this->post->update(['external_id' => '999_111']);

    $this->artisan('facebook:publish-due')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('publishes nothing when the page credentials are missing', function () {
    Queue::fake();
    config(['publishing.facebook.access_token' => null]);

    expect(FacebookPostPublisher::enabled())->toBeFalse();

    $this->artisan('facebook:publish-due')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('posts copy without an image to the page feed', function () {
    Http::fake([
        'graph.facebook.com/*/feed' => Http::response(['id' => '1234567890_555']),
    ]);

    (new PublishFacebookPost($this->post->id))->handle(new FacebookPostPublisher);

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), '/v21.0/1234567890/feed')
            && $request['message'] === $this->post->fullText()
            && $request['access_token'] === 'page-token';
    });

    $published = $this->post->fresh();

    expect($published->status)->toBe('published')
        ->and($published->external_id)->toBe('1234567890_555')
        ->and($published->external_url)->toBe('https://www.facebook.com/1234567890_555')
        ->and($published->published_at)->not->toBeNull()
        ->and($published->publish_error)->toBeNull();
});

it('uploads the poster and keeps the story id for a post with an image', function () {
    fbPostImage($this->post, $this->imageDirectory);

    Http::fake([
        'graph.facebook.com/*/photos' => Http::response(['id' => '777', 'post_id' => '1234567890_888']),
    ]);

    (new PublishFacebookPost($this->post->id))->handle(new FacebookPostPublisher);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), '/photos') && $request->isMultipart());

    expect($this->post->fresh()->external_id)->toBe('1234567890_888')
        ->and($this->post->fresh()->status)->toBe('published');
});

it('claims nothing and holds the queue when the page token is rejected', function () {
    Queue::fake();

    Http::fake([
        'graph.facebook.com/v21.0/me?*' => Http::response([
            'error' => ['message' => 'Invalid OAuth access token.', 'code' => 190],
        ], 400),
    ]);

    $this->artisan('facebook:publish-due')->assertFailed();

    Queue::assertNothingPushed();

    $held = $this->post->fresh();

    expect($held->publish_attempted_at)->toBeNull()
        ->and($held->status)->toBe('scheduled')
        ->and($held->publish_error)->toContain('Invalid OAuth access token.');
});

/** A working token means the probe must not cost the queue an extra call per post. */
it('probes the page token once for the whole batch', function () {
    Queue::fake();
    fbHealthyToken();

    FacebookPost::factory()->for($this->campaign, 'campaign')->create([
        'status' => 'scheduled',
        'scheduled_for' => now()->subMinutes(5),
    ]);

    $this->artisan('facebook:publish-due')->assertSuccessful();

    Queue::assertPushed(PublishFacebookPost::class, 2);
    Http::assertSentCount(1);
});

it('re-arms the post when facebook refuses the credentials', function () {
    $this->post->update(['publish_attempted_at' => now()]);

    Http::fake([
        'graph.facebook.com/*/feed' => Http::response([
            'error' => ['message' => 'Invalid OAuth access token.', 'code' => 190],
        ], 400),
    ]);

    $job = new PublishFacebookPost($this->post->id);

    try {
        $job->handle(new FacebookPostPublisher);
    } catch (FacebookPublishException $exception) {
        $job->failed($exception);
    }

    $rearmed = $this->post->fresh();

    expect($rearmed->publish_attempted_at)->toBeNull()
        ->and($rearmed->publish_error)->toContain('Invalid OAuth access token.');
});

/** The attempt may have left a live post behind, so a human decides what happens next. */
it('keeps the post claimed when facebook refuses the post itself', function () {
    Http::fake([
        'graph.facebook.com/*/feed' => Http::response([
            'error' => ['message' => 'Invalid parameter', 'code' => 100],
        ], 400),
    ]);

    $job = new PublishFacebookPost($this->post->id);

    try {
        $job->handle(new FacebookPostPublisher);
    } catch (FacebookPublishException $exception) {
        $job->failed($exception);
    }

    expect($this->post->fresh()->publish_attempted_at)->not->toBeNull();
});

it('records the graph error message when facebook rejects the post', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token.']], 400),
    ]);

    $job = new PublishFacebookPost($this->post->id);

    try {
        $job->handle(new FacebookPostPublisher);
    } catch (RuntimeException $exception) {
        $job->failed($exception);
    }

    $failed = $this->post->fresh();

    expect($failed->status)->toBe('scheduled')
        ->and($failed->external_id)->toBeNull()
        ->and($failed->publish_error)->toContain('Invalid OAuth access token.');
});

it('does not publish the same post a second time', function () {
    $this->post->update(['external_id' => '1234567890_555', 'status' => 'published']);
    Http::fake();

    (new PublishFacebookPost($this->post->id))->handle(new FacebookPostPublisher);

    Http::assertNothingSent();
});

it('lets an admin publish a post to the page on demand', function () {
    Queue::fake();

    Livewire::actingAs(fbAdmin())->test(FacebookCampaignComponent::class)
        ->call('confirmPublish', $this->post->id)
        ->assertSet('publishPostId', $this->post->id)
        ->call('publishPost')
        ->assertSet('publishPostId', null)
        ->assertSee(__('The post was queued for publishing to the Facebook page.'));

    Queue::assertPushed(PublishFacebookPost::class);
});

it('refuses to publish when the page is not connected', function () {
    Queue::fake();
    config(['publishing.facebook.page_id' => null]);

    Livewire::actingAs(fbAdmin())->test(FacebookCampaignComponent::class)
        ->call('confirmPublish', $this->post->id)
        ->call('publishPost')
        ->assertHasErrors('publish');

    Queue::assertNothingPushed();
});

it('re-arms a failed post when its slot is moved', function () {
    $this->post->update([
        'publish_attempted_at' => now()->subMinutes(10),
        'publish_error' => 'Invalid OAuth access token.',
    ]);

    Livewire::actingAs(fbAdmin())->test(FacebookCampaignComponent::class)
        ->call('editPost', $this->post->id)
        ->set('postScheduledFor', now()->addHour()->format('Y-m-d\TH:i'))
        ->call('savePost')
        ->assertHasNoErrors();

    $rearmed = $this->post->fresh();

    expect($rearmed->publish_attempted_at)->toBeNull()
        ->and($rearmed->publish_error)->toBeNull();
});

it('publishes a throwaway post and removes it again', function () {
    Http::fake([
        'graph.facebook.com/v21.0/1234567890/feed' => Http::response(['id' => '1234567890_777']),
        'graph.facebook.com/v21.0/1234567890_777' => Http::response(['success' => true]),
    ]);

    $this->artisan('facebook:test-post', ['message' => 'A smoke test', '--force' => true])
        ->expectsOutputToContain('1234567890_777')
        ->assertSuccessful();

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/feed')
        && $request['message'] === 'A smoke test');

    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && str_ends_with($request->url(), '/1234567890_777'));
});

it('keeps the throwaway post on the page when asked', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['id' => '1234567890_777'])]);

    $this->artisan('facebook:test-post', ['message' => 'Keep me', '--force' => true, '--keep' => true])
        ->assertSuccessful();

    Http::assertNotSent(fn ($request) => $request->method() === 'DELETE');
});

it('publishes nothing when the confirmation is declined', function () {
    Http::fake();

    $this->artisan('facebook:test-post')
        ->expectsConfirmation('Publish this to the live page now?', 'no')
        ->assertSuccessful();

    Http::assertNothingSent();
});

it('explains a permissions rejection from the test post', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['error' => ['message' => '(#200) Permissions error']], 403),
    ]);

    $this->artisan('facebook:test-post', ['--force' => true])
        ->expectsOutputToContain('pages_manage_posts')
        ->assertFailed();
});

it('refuses the test post when the page credentials are missing', function () {
    config(['publishing.facebook.page_id' => null]);

    Http::fake();

    $this->artisan('facebook:test-post', ['--force' => true])->assertFailed();

    Http::assertNothingSent();
});

/**
 * Shapes a `debug_token` reply, whose defaults describe the token the publisher
 * wants: permanent and allowed to post.
 *
 * @param  list<string>  $scopes
 */
function fbTokenInfo(array $scopes = ['pages_manage_posts', 'pages_read_engagement'], int $expiresAt = 0): array
{
    return ['data' => ['expires_at' => $expiresAt, 'scopes' => $scopes]];
}

it('confirms a page whose token is permanent and allowed to post', function () {
    Http::fake([
        'graph.facebook.com/v21.0/me?*' => Http::response(['id' => '1234567890', 'name' => 'Mojawad.org']),
        'graph.facebook.com/v21.0/debug_token?*' => Http::response(fbTokenInfo()),
    ]);

    $this->artisan('facebook:check')
        ->expectsOutputToContain('Mojawad.org')
        ->assertSuccessful();
});

it('fails the check when the page credentials are missing', function () {
    config(['publishing.facebook.access_token' => null]);

    Http::fake();

    $this->artisan('facebook:check')->assertFailed();

    Http::assertNothingSent();
});

it('fails the check when facebook rejects the token', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token.']], 400),
    ]);

    $this->artisan('facebook:check')
        ->expectsOutputToContain('Invalid OAuth access token.')
        ->assertFailed();
});

it('fails the check when the token belongs to a different page', function () {
    Http::fake([
        'graph.facebook.com/v21.0/me?*' => Http::response(['id' => '9999999999', 'name' => 'Someone else']),
    ]);

    $this->artisan('facebook:check')
        ->expectsOutputToContain('9999999999')
        ->assertFailed();
});

it('fails the check when the token cannot publish posts', function () {
    Http::fake([
        'graph.facebook.com/v21.0/me?*' => Http::response(['id' => '1234567890', 'name' => 'Mojawad.org']),
        'graph.facebook.com/v21.0/debug_token?*' => Http::response(fbTokenInfo(['pages_read_engagement'])),
    ]);

    $this->artisan('facebook:check')
        ->expectsOutputToContain('pages_manage_posts')
        ->assertFailed();
});

it('fails the check when the token still expires', function () {
    Http::fake([
        'graph.facebook.com/v21.0/me?*' => Http::response(['id' => '1234567890', 'name' => 'Mojawad.org']),
        'graph.facebook.com/v21.0/debug_token?*' => Http::response(fbTokenInfo(expiresAt: now()->addMonth()->timestamp)),
    ]);

    $this->artisan('facebook:check')->assertFailed();
});

/** The identity call already proved the credentials, so unreadable metadata is not fatal. */
it('passes the check when the token metadata cannot be read', function () {
    Http::fake([
        'graph.facebook.com/v21.0/me?*' => Http::response(['id' => '1234567890', 'name' => 'Mojawad.org']),
        'graph.facebook.com/v21.0/debug_token?*' => Http::response(['error' => ['message' => 'Unsupported get request.']], 400),
    ]);

    $this->artisan('facebook:check')->assertSuccessful();
});

/**
 * Publishing is manual now: the command stays available to run by hand, but
 * nothing on the schedule may reach for it on its own.
 */
it('never publishes on a schedule', function () {
    $scheduled = collect(app(Schedule::class)->events())
        ->map(fn (Event $event): string => $event->command ?? $event->description ?? '')
        ->filter(fn (string $command): bool => str_contains($command, 'facebook:publish-due'));

    expect($scheduled)->toBeEmpty();
});
