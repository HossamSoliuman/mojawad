<?php

use App\Livewire\Admin\FacebookCampaign as FacebookCampaignComponent;
use App\Models\FacebookCampaign;
use App\Models\FacebookPost;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'creator', 'reviewer', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    FacebookPost::query()->delete();
    FacebookCampaign::query()->delete();

    $this->campaign = FacebookCampaign::factory()->create([
        'name' => 'Mojawad Facebook',
        'post_instructions' => null,
        'edit_lessons' => null,
    ]);
});

function fbiEditor(): User
{
    return User::factory()->create()->assignRole('admin');
}

function fbiPost(array $attributes = []): FacebookPost
{
    return FacebookPost::factory()->create($attributes + [
        'facebook_campaign_id' => test()->campaign->id,
        'status' => 'draft',
        'scheduled_for' => null,
    ]);
}

it('opens the single campaign instructions with the default rules', function () {
    $component = Livewire::actingAs(fbiEditor())->test(FacebookCampaignComponent::class)
        ->call('openInstructions')
        ->assertSet('showInstructions', true)
        ->assertSet('instructionsCampaignId', $this->campaign->id);

    expect($component->get('instructionsText'))->toContain('شاركنا في التعليقات');
});

it('saves edited instructions onto the campaign', function () {
    Livewire::actingAs(fbiEditor())->test(FacebookCampaignComponent::class)
        ->call('openInstructions')
        ->set('instructionsText', 'لا تختم المنشور بسؤال للتفاعل.')
        ->call('saveInstructions')
        ->assertHasNoErrors()
        ->assertSet('showInstructions', false);

    expect($this->campaign->fresh()->post_instructions)->toBe('لا تختم المنشور بسؤال للتفاعل.');
});

it('logs the rewritten fields as a lesson when a post is edited', function () {
    $post = fbiPost(['title' => 'Abdul Basit story', 'body' => 'The old body.']);

    Livewire::actingAs(fbiEditor())->test(FacebookCampaignComponent::class)
        ->call('editPost', $post->id)
        ->set('postBody', 'The corrected body.')
        ->call('savePost')
        ->assertHasNoErrors();

    $lessons = $this->campaign->fresh()->editLessonList();

    expect($lessons)->toHaveCount(1)
        ->and($lessons[0])->toContain('Abdul Basit story')
        ->toContain('The old body.')
        ->toContain('The corrected body.');
});

it('keeps the editor note beside the fields that changed', function () {
    $post = fbiPost(['cta' => 'شاركنا في التعليقات: أيّ تلاوة أثّرت فيك؟']);

    Livewire::actingAs(fbiEditor())->test(FacebookCampaignComponent::class)
        ->call('editPost', $post->id)
        ->set('postCta', 'استمع إلى التلاوة كاملة على مجوّد.')
        ->set('postEditLesson', 'لا تطلب تفاعلًا في نهاية المنشور.')
        ->call('savePost')
        ->assertHasNoErrors();

    expect($this->campaign->fresh()->editLessonList()[0])
        ->toContain('لا تطلب تفاعلًا في نهاية المنشور.')
        ->toContain('استمع إلى التلاوة كاملة على مجوّد.');
});

/** Rescheduling is workflow, not writing; it teaches the generator nothing. */
it('records no lesson when only the publishing slot moves', function () {
    $post = fbiPost();

    Livewire::actingAs(fbiEditor())->test(FacebookCampaignComponent::class)
        ->call('editPost', $post->id)
        ->set('postStatus', 'scheduled')
        ->set('postScheduledFor', '2026-09-18T21:00')
        ->call('savePost')
        ->assertHasNoErrors();

    expect($this->campaign->fresh()->edit_lessons)->toBeNull();
});

it('records no lesson when a post is created', function () {
    Livewire::actingAs(fbiEditor())->test(FacebookCampaignComponent::class)
        ->call('createPost')
        ->set('postTitle', 'A brand new post')
        ->set('postHook', 'An opening line.')
        ->set('postBody', 'The body of the post.')
        ->call('savePost')
        ->assertHasNoErrors();

    expect($this->campaign->fresh()->edit_lessons)->toBeNull();
});

it('drops the oldest lesson once the log is full', function () {
    $this->campaign->update([
        'edit_lessons' => collect(range(1, FacebookCampaign::EDIT_LESSON_LIMIT))
            ->map(fn (int $index): string => "lesson {$index}")
            ->implode("\n"),
    ]);

    $this->campaign->recordEditLesson('the newest lesson');

    $lessons = $this->campaign->fresh()->editLessonList();

    expect($lessons)->toHaveCount(FacebookCampaign::EDIT_LESSON_LIMIT)
        ->and($lessons[0])->toBe('lesson 2')
        ->and(end($lessons))->toBe('the newest lesson');
});

it('hands the generator the instructions and the lessons in one brief', function () {
    $this->campaign->update(['post_instructions' => 'اكتب بلغة عربية سهلة.']);
    $this->campaign->recordEditLesson('لا تطلب تفاعلًا في نهاية المنشور.');

    $document = $this->campaign->fresh()->instructionsDocument();

    expect($document)->toContain('Mojawad Facebook')
        ->toContain('اكتب بلغة عربية سهلة.')
        ->toContain('لا تطلب تفاعلًا في نهاية المنشور.');
});

it('offers the instructions button from the workspace header', function () {
    Livewire::actingAs(fbiEditor())->test(FacebookCampaignComponent::class)
        ->assertSeeText(__('Post instructions'))
        ->assertSet('showInstructions', false);
});
