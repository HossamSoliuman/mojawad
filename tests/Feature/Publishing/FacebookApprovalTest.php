<?php

use App\Livewire\Admin\FacebookCampaign as FacebookCampaignComponent;
use App\Models\FacebookCampaign;
use App\Models\FacebookPost;
use App\Models\User;
use App\Services\FacebookPostScheduler;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    FacebookPost::query()->delete();
    FacebookCampaign::query()->delete();

    config([
        'publishing.facebook.cadence.timezone' => 'Africa/Cairo',
        'publishing.facebook.cadence.daily' => ['08:00', '21:00'],
        'publishing.facebook.cadence.friday' => ['08:00', '11:00', '21:00'],
        'publishing.facebook.cadence.lead_minutes' => 15,
        'publishing.facebook.cadence.horizon_days' => 180,
    ]);

    $this->campaign = FacebookCampaign::factory()->create(['name' => 'Ramadan reflections']);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * Freezes the given Cairo wall-clock time. The frozen instant is stored as UTC
 * because Carbon hands its test-now timezone to every later naive parse — including
 * Eloquent's date hydration — and the application itself runs on UTC.
 */
function fbFreeze(string $cairoTime): void
{
    CarbonImmutable::setTestNow(CarbonImmutable::parse($cairoTime, 'Africa/Cairo')->utc());
}

/**
 * The given Cairo wall-clock time as a UTC instant. Eloquent writes whatever
 * timezone the Carbon carries without converting it, so seeding has to hand it
 * the same UTC value the application would.
 */
function fbCairo(string $cairoTime): CarbonImmutable
{
    return CarbonImmutable::parse($cairoTime, 'Africa/Cairo')->utc();
}

function fbDraft(array $attributes = []): FacebookPost
{
    return FacebookPost::factory()->create($attributes + [
        'facebook_campaign_id' => test()->campaign->id,
        'status' => 'draft',
        'scheduled_for' => null,
    ]);
}

function fbApprover(): User
{
    return User::factory()->create()->assignRole('admin');
}

it('books the first daily slot still ahead of now', function () {
    fbFreeze('2026-03-10 06:00');

    expect(app(FacebookPostScheduler::class)->nextSlot()->equalTo(fbCairo('2026-03-10 08:00')))->toBeTrue();
});

it('moves to the evening slot once the morning one has passed', function () {
    fbFreeze('2026-03-10 09:30');

    expect(app(FacebookPostScheduler::class)->nextSlot()->equalTo(fbCairo('2026-03-10 21:00')))->toBeTrue();
});

it('rolls over to the next day when the evening slot has passed', function () {
    fbFreeze('2026-03-10 22:00');

    expect(app(FacebookPostScheduler::class)->nextSlot()->equalTo(fbCairo('2026-03-11 08:00')))->toBeTrue();
});

/** A slot moments away cannot reach the queue in time, so it is skipped. */
it('skips a slot that is inside the lead time', function () {
    fbFreeze('2026-03-10 07:55');

    expect(app(FacebookPostScheduler::class)->nextSlot()->equalTo(fbCairo('2026-03-10 21:00')))->toBeTrue();
});

it('gives friday a third slot before jumuah', function () {
    fbFreeze('2026-03-13 09:00');

    expect(app(FacebookPostScheduler::class)->nextSlot()->equalTo(fbCairo('2026-03-13 11:00')))->toBeTrue();
});

it('never books a slot another scheduled post already holds', function () {
    fbFreeze('2026-03-10 06:00');

    fbDraft(['status' => 'scheduled', 'scheduled_for' => fbCairo('2026-03-10 08:00')]);

    expect(app(FacebookPostScheduler::class)->nextSlot()->equalTo(fbCairo('2026-03-10 21:00')))->toBeTrue();
});

/** A published post's slot is spent; the same clock time is free on later days. */
it('ignores slots held by posts that already went out', function () {
    fbFreeze('2026-03-10 06:00');

    fbDraft(['status' => 'published', 'scheduled_for' => fbCairo('2026-03-10 08:00')]);

    expect(app(FacebookPostScheduler::class)->nextSlot()->equalTo(fbCairo('2026-03-10 08:00')))->toBeTrue();
});

it('approves a draft and books it into the next free slot', function () {
    fbFreeze('2026-03-10 06:00');

    $post = fbDraft();

    Livewire::actingAs(fbApprover())->test(FacebookCampaignComponent::class)
        ->call('approvePost', $post->id)
        ->assertHasNoErrors();

    $approved = $post->fresh();

    expect($approved->status)->toBe('scheduled')
        ->and($approved->scheduled_for->equalTo(fbCairo('2026-03-10 08:00')))->toBeTrue();
});

it('spreads consecutive approvals across separate slots', function () {
    fbFreeze('2026-03-10 06:00');

    $first = fbDraft();
    $second = fbDraft();
    $third = fbDraft();

    $component = Livewire::actingAs(fbApprover())->test(FacebookCampaignComponent::class);

    foreach ([$first, $second, $third] as $post) {
        $component->call('approvePost', $post->id);
    }

    expect($first->fresh()->scheduled_for->equalTo(fbCairo('2026-03-10 08:00')))->toBeTrue()
        ->and($second->fresh()->scheduled_for->equalTo(fbCairo('2026-03-10 21:00')))->toBeTrue()
        ->and($third->fresh()->scheduled_for->equalTo(fbCairo('2026-03-11 08:00')))->toBeTrue();
});

it('keeps a future slot an editor chose by hand', function () {
    fbFreeze('2026-03-10 06:00');

    $post = fbDraft(['status' => 'ready', 'scheduled_for' => fbCairo('2026-03-20 17:30')]);

    Livewire::actingAs(fbApprover())->test(FacebookCampaignComponent::class)
        ->call('approvePost', $post->id);

    expect($post->fresh()->scheduled_for->equalTo(fbCairo('2026-03-20 17:30')))->toBeTrue();
});

it('rebooks a post whose chosen slot is already in the past', function () {
    fbFreeze('2026-03-10 06:00');

    $post = fbDraft(['scheduled_for' => fbCairo('2026-03-01 09:00')]);

    Livewire::actingAs(fbApprover())->test(FacebookCampaignComponent::class)
        ->call('approvePost', $post->id);

    expect($post->fresh()->scheduled_for->equalTo(fbCairo('2026-03-10 08:00')))->toBeTrue();
});

it('clears a stale failure when a post is approved again', function () {
    fbFreeze('2026-03-10 06:00');

    $post = fbDraft([
        'publish_attempted_at' => fbCairo('2026-03-01 09:00'),
        'publish_error' => 'Invalid OAuth access token.',
    ]);

    Livewire::actingAs(fbApprover())->test(FacebookCampaignComponent::class)
        ->call('approvePost', $post->id);

    $approved = $post->fresh();

    expect($approved->publish_attempted_at)->toBeNull()
        ->and($approved->publish_error)->toBeNull();
});

it('refuses to approve a post that already left the creation stage', function () {
    fbFreeze('2026-03-10 06:00');

    $post = fbDraft(['status' => 'published']);

    Livewire::actingAs(fbApprover())->test(FacebookCampaignComponent::class)
        ->call('approvePost', $post->id)
        ->assertStatus(422);
});

it('frees the slot again when a post is sent back for review', function () {
    fbFreeze('2026-03-10 06:00');

    $post = fbDraft(['status' => 'scheduled', 'scheduled_for' => fbCairo('2026-03-10 08:00')]);

    Livewire::actingAs(fbApprover())->test(FacebookCampaignComponent::class)
        ->call('returnToCreation', $post->id);

    $returned = $post->fresh();

    expect($returned->status)->toBe('ready')
        ->and($returned->scheduled_for)->toBeNull();

    expect(app(FacebookPostScheduler::class)->nextSlot()->equalTo(fbCairo('2026-03-10 08:00')))->toBeTrue();
});

it('will not send a post that is already live back for review', function () {
    $post = fbDraft(['status' => 'published', 'external_id' => '123_456']);

    Livewire::actingAs(fbApprover())->test(FacebookCampaignComponent::class)
        ->call('returnToCreation', $post->id)
        ->assertStatus(422);
});

it('hands an approved post to the publisher once its slot arrives', function () {
    fbFreeze('2026-03-10 06:00');

    $post = fbDraft();

    Livewire::actingAs(fbApprover())->test(FacebookCampaignComponent::class)
        ->call('approvePost', $post->id);

    expect(FacebookPost::query()->dueForPublishing()->count())->toBe(0);

    fbFreeze('2026-03-10 08:01');

    expect(FacebookPost::query()->dueForPublishing()->pluck('id')->all())->toBe([$post->id]);
});

/** What an editor types is the audience's clock, not the server's. */
it('reads and writes the manual slot in the cadence timezone', function () {
    fbFreeze('2026-03-10 06:00');

    $post = fbDraft();

    Livewire::actingAs(fbApprover())->test(FacebookCampaignComponent::class)
        ->call('editPost', $post->id)
        ->set('postStatus', 'scheduled')
        ->set('postScheduledFor', '2026-03-18T21:00')
        ->call('savePost')
        ->assertHasNoErrors();

    expect($post->fresh()->scheduled_for->equalTo(fbCairo('2026-03-18 21:00')))->toBeTrue();

    Livewire::actingAs(fbApprover())->test(FacebookCampaignComponent::class)
        ->call('editPost', $post->id)
        ->assertSet('postScheduledFor', '2026-03-18T21:00');
});

it('states the cadence it books into', function () {
    Livewire::actingAs(fbApprover())->test(FacebookCampaignComponent::class)
        ->assertSet('stage', 'creation')
        ->assertSeeText('08:00')
        ->assertSeeText('21:00');

    expect(app(FacebookPostScheduler::class)->summary())
        ->toMatchArray(['timezone' => 'Africa/Cairo', 'weekly' => 15]);
});
