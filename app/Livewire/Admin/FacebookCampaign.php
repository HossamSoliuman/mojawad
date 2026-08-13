<?php

namespace App\Livewire\Admin;

use App\Jobs\PublishFacebookPost;
use App\Models\FacebookCampaign as Campaign;
use App\Models\FacebookPost;
use App\Services\FacebookPostPublisher;
use App\Services\FacebookPostScheduler;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class FacebookCampaign extends Component
{
    use WithPagination;

    /**
     * The writing fields whose hand edits are worth teaching the generator.
     * Workflow columns (status, slot, image file) are left out: changing them
     * says nothing about how the next post should be written.
     *
     * @var array<string, string>
     */
    private const LESSON_FIELDS = [
        'title' => 'Internal title',
        'category' => 'Category',
        'length' => 'Post length',
        'hook' => 'Hook',
        'body' => 'Post body',
        'cta' => 'Call to action',
        'hashtags' => 'Hashtags',
        'visual_brief' => 'Visual brief',
        'image_prompt' => 'Image prompt',
        'visual_text' => 'Text on poster',
        'alt_text' => 'Alt text',
    ];

    #[Url]
    public string $stage = 'creation';

    #[Url]
    public string $campaign = 'all';

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $category = 'all';

    #[Url]
    public string $search = '';

    public ?int $expandedPostId = null;

    public ?int $deletePostId = null;

    public ?int $deleteCampaignId = null;

    public ?int $publishPostId = null;

    public bool $showPostForm = false;

    public ?int $editingPostId = null;

    public string $postCampaignId = '';

    public string $postTitle = '';

    public string $postCategory = '';

    public string $postStatus = 'draft';

    public string $postLength = 'short';

    public string $postHook = '';

    public string $postBody = '';

    public string $postCta = '';

    public string $postHashtags = '';

    public string $postVisualBrief = '';

    public string $postImagePrompt = '';

    public string $postAspectRatio = '1:1';

    public string $postImageFile = '';

    public string $postVisualText = '';

    public string $postAltText = '';

    public string $postReviewNotes = '';

    public string $postPublishSlot = '';

    public string $postScheduledFor = '';

    public string $postEditLesson = '';

    public bool $showInstructions = false;

    public ?int $instructionsCampaignId = null;

    public string $instructionsText = '';

    public string $instructionsLessons = '';

    public bool $showCampaignForm = false;

    public ?int $editingCampaignId = null;

    public string $campaignName = '';

    public string $campaignGoal = '';

    public string $campaignAudience = '';

    public string $campaignCadence = '';

    public string $campaignTone = '';

    public string $campaignHashtags = '';

    public string $campaignImageWorkflow = '';

    public bool $campaignIsActive = true;

    public ?string $notice = null;

    /** @return EloquentCollection<int, Campaign> */
    #[Computed]
    public function campaigns(): EloquentCollection
    {
        return Campaign::query()
            ->withCount('posts')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    /**
     * The campaign the instructions button opens. The workspace runs on a
     * single campaign, so a filtered one wins and otherwise the active one
     * stands in without asking the editor to pick.
     */
    #[Computed]
    public function instructionsCampaign(): ?Campaign
    {
        if ($this->campaign !== 'all') {
            return $this->campaigns->firstWhere('id', (int) $this->campaign);
        }

        return $this->campaigns->firstWhere('is_active', true) ?? $this->campaigns->first();
    }

    /**
     * Post counts per workspace tab, ignoring the in-tab filters so the tabs
     * always report the whole stage.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function stageCounts(): array
    {
        $counts = FacebookPost::query()
            ->forCampaign($this->campaign)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(FacebookPost::STAGES)
            ->map(fn (array $statuses): int => (int) $counts->only($statuses)->sum())
            ->all();
    }

    /** @return list<array{key: string, label: string, icon: string, color: string, count: int}> */
    #[Computed]
    public function categoryCards(): array
    {
        $counts = FacebookPost::query()
            ->forCampaign($this->campaign)
            ->inStage($this->stage)
            ->selectRaw('category, count(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category');

        $keys = collect(array_keys(FacebookPost::CATEGORIES))
            ->merge($counts->keys()->map(fn (?string $key): string => (string) $key))
            ->map(fn (string $key): string => $key !== '' ? $key : FacebookPost::UNCATEGORIZED)
            ->unique()
            ->values();

        return $keys
            ->map(fn (string $key): array => FacebookPost::categoryMeta($key) + [
                'key' => $key,
                'count' => (int) $counts->get($key === FacebookPost::UNCATEGORIZED ? '' : $key, 0),
            ])
            ->reject(fn (array $card): bool => $card['key'] === FacebookPost::UNCATEGORIZED && $card['count'] === 0)
            ->values()
            ->all();
    }

    /** @return list<string> */
    #[Computed]
    public function statusOptions(): array
    {
        return FacebookPost::stageStatuses($this->stage);
    }

    /** Whether page credentials are present, so the UI can explain why publishing is unavailable. */
    #[Computed]
    public function facebookConnected(): bool
    {
        return FacebookPostPublisher::enabled();
    }

    #[Computed]
    public function publishTarget(): ?FacebookPost
    {
        return $this->publishPostId !== null
            ? FacebookPost::query()->find($this->publishPostId)
            : null;
    }

    /**
     * The rhythm approval books into, so the workspace can state it plainly.
     *
     * @return array{timezone: string, weekly: int, daily: list<string>, friday: list<string>}
     */
    #[Computed]
    public function cadence(): array
    {
        return app(FacebookPostScheduler::class)->summary();
    }

    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        $needle = trim($this->search);

        return FacebookPost::query()
            ->with('campaign')
            ->forCampaign($this->campaign)
            ->inStage($this->stage)
            ->forCategory($this->category)
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($needle !== '', function ($query) use ($needle): void {
                $query->where(function ($query) use ($needle): void {
                    $query
                        ->where('title', 'like', "%{$needle}%")
                        ->orWhere('hook', 'like', "%{$needle}%")
                        ->orWhere('body', 'like', "%{$needle}%")
                        ->orWhere('hashtags', 'like', "%{$needle}%");
                });
            })
            ->orderByRaw("case status when 'scheduled' then 1 when 'ready' then 2 when 'draft' then 3 when 'published' then 4 else 5 end")
            ->orderByRaw('scheduled_for is null')
            ->orderBy('scheduled_for')
            ->orderByDesc('updated_at')
            ->paginate(12, pageName: 'postsPage');
    }

    public function mount(): void
    {
        if (! array_key_exists($this->stage, FacebookPost::STAGES)) {
            $this->stage = array_key_first(FacebookPost::STAGES);
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['campaign', 'status', 'category', 'search'], true)) {
            $this->resetPage('postsPage');
        }

        if ($property === 'campaign') {
            $this->category = 'all';
        }
    }

    public function selectStage(string $stage): void
    {
        abort_unless(array_key_exists($stage, FacebookPost::STAGES), 404);

        $this->stage = $stage;
        $this->status = 'all';
        $this->expandedPostId = null;
        $this->resetPage('postsPage');
    }

    public function selectCategory(string $category): void
    {
        $this->category = $category;
        $this->resetPage('postsPage');
    }

    public function resetFilters(): void
    {
        $this->reset(['status', 'category', 'search']);
        $this->resetPage('postsPage');
    }

    public function togglePost(int $postId): void
    {
        $this->expandedPostId = $this->expandedPostId === $postId ? null : $postId;
    }

    public function createPost(): void
    {
        $this->authorizeAdmin();
        $this->resetPostForm();
        $selectedCampaign = $this->campaign !== 'all'
            ? Campaign::query()->find($this->campaign)
            : Campaign::query()->active()->orderBy('name')->first();
        $selectedCampaign ??= Campaign::query()->orderBy('name')->first();

        if ($selectedCampaign === null) {
            $this->createCampaign();

            return;
        }

        $this->postCampaignId = (string) $selectedCampaign->id;
        $this->postStatus = FacebookPost::stageStatuses($this->stage)[0];

        if ($this->category !== 'all' && $this->category !== FacebookPost::UNCATEGORIZED) {
            $this->postCategory = $this->category;
        }

        $this->showPostForm = true;
    }

    public function editPost(int $postId): void
    {
        $this->authorizeAdmin();
        $post = FacebookPost::query()->findOrFail($postId);

        $this->editingPostId = $post->id;
        $this->postCampaignId = (string) $post->facebook_campaign_id;
        $this->postTitle = $post->title;
        $this->postCategory = (string) $post->category;
        $this->postStatus = $post->status;
        $this->postLength = $post->length;
        $this->postHook = $post->hook;
        $this->postBody = $post->body;
        $this->postCta = (string) $post->cta;
        $this->postHashtags = (string) $post->hashtags;
        $this->postVisualBrief = (string) $post->visual_brief;
        $this->postImagePrompt = (string) $post->image_prompt;
        $this->postAspectRatio = $post->aspect_ratio;
        $this->postImageFile = (string) $post->image_file;
        $this->postVisualText = (string) $post->visual_text;
        $this->postAltText = (string) $post->alt_text;
        $this->postReviewNotes = (string) $post->review_notes;
        $this->postPublishSlot = (string) $post->publish_slot;
        $this->postScheduledFor = $post->localScheduledFor()?->format('Y-m-d\TH:i') ?? '';
        $this->postEditLesson = '';
        $this->showPostForm = true;
    }

    public function savePost(): void
    {
        $this->authorizeAdmin();
        $validated = $this->validate($this->postRules(), $this->postMessages());
        $post = $this->editingPostId !== null
            ? FacebookPost::query()->findOrFail($this->editingPostId)
            : new FacebookPost(['created_by' => auth()->id()]);

        $post->fill([
            'facebook_campaign_id' => (int) $validated['postCampaignId'],
            'title' => trim($validated['postTitle']),
            'category' => filled($validated['postCategory']) ? Str::snake(trim($validated['postCategory'])) : null,
            'status' => $validated['postStatus'],
            'length' => $validated['postLength'],
            'hook' => trim($validated['postHook']),
            'body' => trim($validated['postBody']),
            'cta' => $this->nullableText($validated['postCta']),
            'hashtags' => $this->normaliseHashtags($validated['postHashtags']),
            'visual_brief' => $this->nullableText($validated['postVisualBrief']),
            'image_prompt' => $this->nullableText($validated['postImagePrompt']),
            'aspect_ratio' => $validated['postAspectRatio'],
            'image_file' => filled($validated['postImageFile']) ? basename(trim($validated['postImageFile'])) : null,
            'visual_text' => $this->nullableText($validated['postVisualText']),
            'alt_text' => $this->nullableText($validated['postAltText']),
            'review_notes' => $this->nullableText($validated['postReviewNotes']),
            'publish_slot' => $this->nullableText($validated['postPublishSlot']),
            'scheduled_for' => filled($validated['postScheduledFor'])
                ? CarbonImmutable::parse($validated['postScheduledFor'], FacebookPost::slotTimezone())->setTimezone('UTC')
                : null,
        ]);

        if ($post->status === 'published' && $post->published_at === null) {
            $post->published_at = now();
        }

        /**
         * Re-arm a post that never made it to the page: moving its slot is how
         * an editor asks the scheduler to try again after a failed attempt.
         */
        if (! $post->isLive() && $post->isDirty('scheduled_for')) {
            $post->publish_attempted_at = null;
            $post->publish_error = null;
        }

        /** Read the corrections while the record still knows what it replaced. */
        $lesson = $this->editingPostId !== null
            ? $this->editLesson($post, $validated['postEditLesson'] ?? '')
            : null;

        $post->save();

        if ($lesson !== null) {
            Campaign::query()->find($post->facebook_campaign_id)?->recordEditLesson($lesson);
            unset($this->campaigns, $this->instructionsCampaign);
        }

        $this->notice = $this->editingPostId === null ? __('Post added to the repository.') : __('Post updated.');
        $this->closePostForm();
        $this->followPost($post);
    }

    public function confirmDeletePost(int $postId): void
    {
        $this->authorizeAdmin();
        FacebookPost::query()->findOrFail($postId);
        $this->deletePostId = $postId;
    }

    public function deletePost(): void
    {
        $this->authorizeAdmin();
        FacebookPost::query()->findOrFail($this->deletePostId)->delete();
        $this->deletePostId = null;
        $this->expandedPostId = null;
        $this->notice = __('Post deleted from the repository.');
    }

    /**
     * The one click that moves a post out of review. Booking the slot here is
     * what makes the next tab self-driving: the editor judges the copy, the
     * cadence decides the timing, and the scheduler publishes it unattended.
     * A slot an editor already chose for the future is respected as-is.
     */
    public function approvePost(int $postId): void
    {
        $this->authorizeAdmin();
        $post = FacebookPost::query()->findOrFail($postId);
        abort_unless(in_array($post->status, FacebookPost::stageStatuses('creation'), true), 422);

        $slot = $post->scheduled_for?->isFuture()
            ? $post->scheduled_for
            : app(FacebookPostScheduler::class)->nextSlot();

        $post->update([
            'status' => 'scheduled',
            'scheduled_for' => $slot,
            'publish_attempted_at' => null,
            'publish_error' => null,
        ]);

        $this->notice = __('Approved — it posts on :slot.', ['slot' => $this->slotLabel($slot)]);
        $this->expandedPostId = null;
    }

    /** Undo an approval that came too fast; the slot frees up for the next post. */
    public function returnToCreation(int $postId): void
    {
        $this->authorizeAdmin();
        $post = FacebookPost::query()->findOrFail($postId);
        abort_if($post->isLive(), 422);

        $post->update([
            'status' => 'ready',
            'scheduled_for' => null,
            'publish_attempted_at' => null,
            'publish_error' => null,
        ]);

        $this->notice = __('Sent back for review. Its slot is free again.');
        $this->followPost($post);
    }

    public function confirmPublish(int $postId): void
    {
        $this->authorizeAdmin();
        $post = FacebookPost::query()->findOrFail($postId);
        abort_if($post->isLive(), 422);

        $this->publishPostId = $postId;
    }

    public function cancelPublish(): void
    {
        $this->publishPostId = null;
    }

    /**
     * Hand the post to the queue. The scheduler claims due posts the same way,
     * so stamping the attempt here keeps a manual publish and an automatic one
     * from racing over the same record.
     */
    public function publishPost(): void
    {
        $this->authorizeAdmin();
        $post = FacebookPost::query()->findOrFail($this->publishPostId);
        $this->publishPostId = null;

        if ($post->isLive()) {
            $this->notice = __('This post is already live on the page.');

            return;
        }

        if (! FacebookPostPublisher::enabled()) {
            $this->addError('publish', __('Add the Facebook page id and access token before publishing.'));

            return;
        }

        $post->update(['publish_attempted_at' => now(), 'publish_error' => null]);
        PublishFacebookPost::dispatch($post->id);

        $this->notice = __('The post was queued for publishing to the Facebook page.');
        $this->followPost($post);
    }

    public function closePostForm(): void
    {
        $this->showPostForm = false;
        $this->resetPostForm();
    }

    /**
     * Open the brief the post generator writes from: the standing instructions
     * plus every correction an editor has made since. Both are editable, so an
     * instruction that stopped being true can be rewritten instead of accruing.
     */
    public function openInstructions(): void
    {
        $this->authorizeAdmin();
        $campaign = $this->instructionsCampaign;

        if ($campaign === null) {
            $this->createCampaign();

            return;
        }

        $this->instructionsCampaignId = $campaign->id;
        $this->instructionsText = $campaign->postInstructions();
        $this->instructionsLessons = (string) $campaign->edit_lessons;
        $this->showInstructions = true;
        $this->resetValidation();
    }

    public function saveInstructions(): void
    {
        $this->authorizeAdmin();
        $validated = $this->validate([
            'instructionsText' => ['nullable', 'string', 'max:50000'],
            'instructionsLessons' => ['nullable', 'string', 'max:50000'],
        ]);

        Campaign::query()->findOrFail($this->instructionsCampaignId)->update([
            'post_instructions' => $this->nullableText($validated['instructionsText']),
            'edit_lessons' => $this->nullableText($validated['instructionsLessons']),
        ]);

        unset($this->campaigns, $this->instructionsCampaign);
        $this->notice = __('Post instructions updated.');
        $this->closeInstructions();
    }

    public function closeInstructions(): void
    {
        $this->showInstructions = false;
        $this->reset(['instructionsCampaignId', 'instructionsText', 'instructionsLessons']);
        $this->resetValidation();
    }

    public function createCampaign(): void
    {
        $this->authorizeAdmin();
        $this->resetCampaignForm();
        $this->showInstructions = false;
        $this->showCampaignForm = true;
    }

    public function editCampaign(int $campaignId): void
    {
        $this->authorizeAdmin();
        $campaign = Campaign::query()->findOrFail($campaignId);
        $this->showInstructions = false;

        $this->editingCampaignId = $campaign->id;
        $this->campaignName = $campaign->name;
        $this->campaignGoal = (string) $campaign->goal;
        $this->campaignAudience = (string) $campaign->audience;
        $this->campaignCadence = (string) $campaign->cadence;
        $this->campaignTone = (string) $campaign->tone;
        $this->campaignHashtags = (string) $campaign->core_hashtags;
        $this->campaignImageWorkflow = (string) $campaign->image_workflow;
        $this->campaignIsActive = $campaign->is_active;
        $this->showCampaignForm = true;
    }

    public function saveCampaign(): void
    {
        $this->authorizeAdmin();
        $validated = $this->validate($this->campaignRules());
        $campaign = $this->editingCampaignId !== null
            ? Campaign::query()->findOrFail($this->editingCampaignId)
            : new Campaign(['created_by' => auth()->id()]);

        $campaign->fill([
            'name' => trim($validated['campaignName']),
            'slug' => $this->uniqueCampaignSlug($validated['campaignName']),
            'goal' => $this->nullableText($validated['campaignGoal']),
            'audience' => $this->nullableText($validated['campaignAudience']),
            'cadence' => $this->nullableText($validated['campaignCadence']),
            'tone' => $this->nullableText($validated['campaignTone']),
            'core_hashtags' => $this->normaliseHashtags($validated['campaignHashtags']),
            'image_workflow' => $this->nullableText($validated['campaignImageWorkflow']),
            'is_active' => $validated['campaignIsActive'],
        ])->save();

        $this->campaign = (string) $campaign->id;
        $this->notice = $this->editingCampaignId === null ? __('Campaign created.') : __('Campaign updated.');
        $this->closeCampaignForm();
    }

    public function confirmDeleteCampaign(int $campaignId): void
    {
        $this->authorizeAdmin();
        $campaign = Campaign::query()->withCount('posts')->findOrFail($campaignId);

        if ($campaign->posts_count > 0) {
            $this->addError('campaignDelete', __('Move or delete the campaign posts first.'));

            return;
        }

        $this->deleteCampaignId = $campaignId;
    }

    public function deleteCampaign(): void
    {
        $this->authorizeAdmin();
        $campaign = Campaign::query()->withCount('posts')->findOrFail($this->deleteCampaignId);
        abort_if($campaign->posts_count > 0, 422);
        $campaign->delete();
        $this->deleteCampaignId = null;
        $this->campaign = 'all';
        $this->notice = __('Campaign deleted.');
    }

    public function closeCampaignForm(): void
    {
        $this->showCampaignForm = false;
        $this->resetCampaignForm();
    }

    public function revealImageInExplorer(int $postId): void
    {
        $this->authorizeAdmin();
        $post = FacebookPost::query()->findOrFail($postId);
        abort_unless($post->hasImage(), 404);
        $absolutePath = realpath($post->imagePath());
        abort_unless($absolutePath !== false, 404);

        if (PHP_OS_FAMILY === 'Windows') {
            Process::run(['cmd.exe', '/c', 'start', '', 'explorer.exe', '/select,'.$absolutePath]);

            return;
        }

        $command = PHP_OS_FAMILY === 'Darwin'
            ? ['open', '-R', $absolutePath]
            : ['xdg-open', dirname($absolutePath)];

        Process::run($command)->throw();
    }

    public function render(): View
    {
        return view('livewire.admin.facebook-campaign');
    }

    /** @return array<string, mixed> */
    private function postRules(): array
    {
        return [
            'postCampaignId' => ['required', 'integer', Rule::exists('facebook_campaigns', 'id')],
            'postTitle' => ['required', 'string', 'max:255'],
            'postCategory' => ['nullable', 'string', 'max:100'],
            'postStatus' => ['required', Rule::in(FacebookPost::STATUSES)],
            'postLength' => ['required', Rule::in(FacebookPost::LENGTHS)],
            'postHook' => ['required', 'string', 'max:2000'],
            'postBody' => ['required', 'string', 'max:20000'],
            'postCta' => ['nullable', 'string', 'max:2000'],
            'postHashtags' => ['nullable', 'string', 'max:1000'],
            'postVisualBrief' => ['nullable', 'string', 'max:5000'],
            'postImagePrompt' => ['nullable', 'string', 'max:10000'],
            'postAspectRatio' => ['required', Rule::in(FacebookPost::ASPECT_RATIOS)],
            'postImageFile' => ['nullable', 'string', 'max:255'],
            'postVisualText' => ['nullable', 'string', 'max:2000'],
            'postAltText' => ['nullable', 'string', 'max:2000'],
            'postReviewNotes' => ['nullable', 'string', 'max:5000'],
            'postPublishSlot' => ['nullable', 'string', 'max:255'],
            'postScheduledFor' => ['nullable', 'date', 'required_if:postStatus,scheduled'],
            'postEditLesson' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * One line describing what an editor rewrote, kept on the campaign so the
     * next generated post is written against the corrections rather than
     * repeating them. Returns null when the edit taught nothing.
     */
    private function editLesson(FacebookPost $post, string $note): ?string
    {
        $changes = collect(self::LESSON_FIELDS)
            ->filter(fn (string $label, string $field): bool => $post->isDirty($field))
            ->map(fn (string $label, string $field): string => __(':field: «:before» → «:after»', [
                'field' => __($label),
                'before' => $this->lessonSnippet($post->getOriginal($field)),
                'after' => $this->lessonSnippet($post->getAttribute($field)),
            ]))
            ->values();

        $summary = collect([trim($note), $changes->implode(' · ')])
            ->filter(fn (string $part): bool => $part !== '')
            ->implode(' — ');

        if ($summary === '') {
            return null;
        }

        return __(':date · «:title» — :summary', [
            'date' => now()->setTimezone(FacebookPost::slotTimezone())->format('Y-m-d'),
            'title' => $post->title,
            'summary' => $summary,
        ]);
    }

    /** Edited copy runs long; the lesson only needs enough to recognise it. */
    private function lessonSnippet(?string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

        return $value !== '' ? Str::limit($value, 140) : __('empty');
    }

    /** @return array<string, string> */
    private function postMessages(): array
    {
        return [
            'postScheduledFor.required_if' => __('Choose a publishing date for a scheduled post.'),
        ];
    }

    /** @return array<string, mixed> */
    private function campaignRules(): array
    {
        return [
            'campaignName' => ['required', 'string', 'max:120'],
            'campaignGoal' => ['nullable', 'string', 'max:5000'],
            'campaignAudience' => ['nullable', 'string', 'max:3000'],
            'campaignCadence' => ['nullable', 'string', 'max:255'],
            'campaignTone' => ['nullable', 'string', 'max:5000'],
            'campaignHashtags' => ['nullable', 'string', 'max:1000'],
            'campaignImageWorkflow' => ['nullable', 'string', 'max:5000'],
            'campaignIsActive' => ['boolean'],
        ];
    }

    private function resetPostForm(): void
    {
        $this->reset([
            'editingPostId', 'postCampaignId', 'postTitle', 'postCategory', 'postHook', 'postBody',
            'postCta', 'postHashtags', 'postVisualBrief', 'postImagePrompt', 'postImageFile',
            'postVisualText', 'postAltText', 'postReviewNotes', 'postPublishSlot', 'postScheduledFor',
            'postEditLesson',
        ]);
        $this->postStatus = 'draft';
        $this->postLength = 'short';
        $this->postAspectRatio = '1:1';
        $this->resetValidation();
    }

    /**
     * Keep a saved post on screen even when its new status moved it to another
     * tab or outside the active category filter.
     */
    private function followPost(FacebookPost $post): void
    {
        $stage = FacebookPost::stageForStatus($post->status);

        if ($stage !== $this->stage) {
            $this->stage = $stage;
            $this->status = 'all';
            $this->resetPage('postsPage');
        }

        if ($this->category !== 'all' && $this->category !== ($post->category ?? FacebookPost::UNCATEGORIZED)) {
            $this->category = 'all';
            $this->resetPage('postsPage');
        }

        $this->expandedPostId = $post->id;
    }

    private function resetCampaignForm(): void
    {
        $this->reset([
            'editingCampaignId', 'campaignName', 'campaignGoal', 'campaignAudience', 'campaignCadence',
            'campaignTone', 'campaignHashtags', 'campaignImageWorkflow',
        ]);
        $this->campaignIsActive = true;
        $this->resetValidation();
    }

    private function uniqueCampaignSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'facebook-campaign';
        $slug = $base;
        $suffix = 2;

        while (Campaign::query()
            ->where('slug', $slug)
            ->when($this->editingCampaignId !== null, fn ($query) => $query->whereKeyNot($this->editingCampaignId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function normaliseHashtags(?string $hashtags): ?string
    {
        $normalised = implode(' ', preg_split('/\s+/', trim((string) $hashtags)) ?: []);

        return $normalised !== '' ? $normalised : null;
    }

    /** Slots are stored in UTC but only make sense read in the cadence timezone. */
    private function slotLabel(CarbonInterface $slot): string
    {
        return $slot->copy()
            ->setTimezone((string) config('publishing.facebook.cadence.timezone'))
            ->translatedFormat('D j M · H:i');
    }

    private function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['admin', 'creator']), 403);
    }
}
