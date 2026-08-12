<div class="fbr" x-data="campaignRepository()">
    <header class="fbr-hero">
        <div class="fbr-hero-copy">
            <span class="fbr-eyebrow"><i class="fab fa-facebook-f"></i> {{ __('Campaign workspace') }}</span>
            <h2>{{ __('Facebook post repository') }}</h2>
            <p>{{ __('Plan, review, and reuse campaign posts from one organized library.') }}</p>
        </div>
        <div class="fbr-hero-actions">
            <button type="button" wire:click="createCampaign" class="btn btn-ghost btn-sm">
                <i class="fas fa-folder-plus"></i> {{ __('New campaign') }}
            </button>
            <button type="button" wire:click="createPost" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> {{ __('New post') }}
            </button>
        </div>
    </header>

    @if($notice)
        <div class="fbr-notice" role="status">
            <i class="fas fa-circle-check"></i>
            <span>{{ $notice }}</span>
            <button type="button" wire:click="$set('notice', null)" aria-label="{{ __('Dismiss') }}"><i class="fas fa-xmark"></i></button>
        </div>
    @endif

    @php($stageMeta = [
        'creation' => ['label' => __('In creation'), 'hint' => __('Drafts and posts ready to schedule.'), 'icon' => 'fa-pen-ruler'],
        'scheduled' => ['label' => __('Scheduled to post'), 'hint' => __('Waiting for their publishing slot.'), 'icon' => 'fa-calendar-day'],
        'posted' => ['label' => __('Posted'), 'hint' => __('Already published on the page.'), 'icon' => 'fa-paper-plane'],
    ])

    <nav class="fbr-tabs" aria-label="{{ __('Publishing stage') }}">
        @foreach($stageMeta as $stageKey => $meta)
            <button type="button" wire:key="stage-{{ $stageKey }}" wire:click="selectStage('{{ $stageKey }}')"
                    class="fbr-tab fbr-tab-{{ $stageKey }} {{ $stage === $stageKey ? 'is-active' : '' }}"
                    aria-current="{{ $stage === $stageKey ? 'page' : 'false' }}">
                <i class="fas {{ $meta['icon'] }}"></i>
                <span><strong>{{ $meta['label'] }}</strong><small>{{ $meta['hint'] }}</small></span>
                <em>{{ number_format($this->stageCounts[$stageKey] ?? 0) }}</em>
            </button>
        @endforeach
    </nav>

    <div class="fbr-workspace">
        <aside class="fbr-side">
            <div class="fbr-panel-heading">
                <div>
                    <span>{{ __('Categories') }}</span>
                    <small>{{ count($this->categoryCards) }}</small>
                </div>
            </div>

            <button type="button" wire:click="selectCategory('all')" class="fbr-cat {{ $category === 'all' ? 'is-active' : '' }}">
                <span class="fbr-cat-icon"><i class="fas fa-box-archive"></i></span>
                <span class="fbr-cat-copy"><strong>{{ __('All categories') }}</strong><small>{{ $stageMeta[$stage]['label'] }}</small></span>
                <span class="fbr-cat-count">{{ number_format($this->stageCounts[$stage] ?? 0) }}</span>
            </button>

            <div class="fbr-cat-list">
                @foreach($this->categoryCards as $categoryCard)
                    <button type="button" wire:key="category-{{ $categoryCard['key'] }}" wire:click="selectCategory('{{ $categoryCard['key'] }}')"
                            class="fbr-cat {{ $category === $categoryCard['key'] ? 'is-active' : '' }}"
                            style="--category-color:{{ $categoryCard['color'] }}">
                        <span class="fbr-cat-icon"><i class="fas {{ $categoryCard['icon'] }}"></i></span>
                        <span class="fbr-cat-copy"><strong>{{ __($categoryCard['label']) }}</strong></span>
                        <span class="fbr-cat-count">{{ $categoryCard['count'] }}</span>
                    </button>
                @endforeach
            </div>

            @error('campaignDelete')
                <p class="fbr-side-error">{{ $message }}</p>
            @enderror
        </aside>

        <main class="fbr-library">
            @php($activeCampaign = $campaign !== 'all' ? $this->campaigns->firstWhere('id', (int) $campaign) : null)
            @php($activeCategory = $category !== 'all' ? collect($this->categoryCards)->firstWhere('key', $category) : null)
            <div class="fbr-library-head">
                <div>
                    <h3>{{ $activeCategory ? __($activeCategory['label']) : $stageMeta[$stage]['label'] }}</h3>
                    <p>{{ $activeCampaign?->goal ?: $stageMeta[$stage]['hint'] }}</p>
                </div>
                @if($activeCampaign)
                    <div class="fbr-head-actions">
                        <button type="button" wire:click="editCampaign({{ $activeCampaign->id }})" class="btn btn-ghost btn-xs"><i class="fas fa-pen"></i> {{ __('Campaign brief') }}</button>
                        <button type="button" wire:click="confirmDeleteCampaign({{ $activeCampaign->id }})" class="btn btn-ghost btn-xs" title="{{ __('Delete campaign') }}"><i class="fas fa-trash"></i></button>
                    </div>
                @endif
            </div>

            <div class="fbr-toolbar">
                <label class="fbr-search">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search titles, copy, or hashtags') }}">
                </label>
                @if($this->campaigns->count() > 1)
                    <label>
                        <span>{{ __('Campaign') }}</span>
                        <select wire:model.live="campaign">
                            <option value="all">{{ __('Every campaign') }}</option>
                            @foreach($this->campaigns as $campaignItem)
                                <option value="{{ $campaignItem->id }}">{{ $campaignItem->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @if(count($this->statusOptions) > 1)
                    <label>
                        <span>{{ __('Status') }}</span>
                        <select wire:model.live="status">
                            <option value="all">{{ __('Any status') }}</option>
                            @foreach($this->statusOptions as $statusOption)
                                <option value="{{ $statusOption }}">{{ __(str($statusOption)->title()->toString()) }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @if($status !== 'all' || $category !== 'all' || trim($search) !== '')
                    <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-xs"><i class="fas fa-rotate-left"></i> {{ __('Clear') }}</button>
                @endif
            </div>

            @if($this->posts->isEmpty())
                <div class="fbr-empty">
                    <span><i class="fas {{ $this->campaigns->isEmpty() ? 'fa-folder-plus' : 'fa-file-circle-plus' }}"></i></span>
                    <h3>{{ $this->campaigns->isEmpty() ? __('Create the first campaign') : __('No posts found') }}</h3>
                    <p>{{ $this->campaigns->isEmpty() ? __('Start with a campaign, then build its publishing queue.') : __('Nothing is in this stage yet. Change the filters or add a post.') }}</p>
                    <button type="button" wire:click="{{ $this->campaigns->isEmpty() ? 'createCampaign' : 'createPost' }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> {{ $this->campaigns->isEmpty() ? __('New campaign') : __('New post') }}
                    </button>
                </div>
            @else
                <div class="fbr-post-list">
                    @foreach($this->posts as $post)
                        @php($categoryMeta = $post->categoryPresentation())
                        <article wire:key="post-{{ $post->id }}" class="fbr-post {{ $expandedPostId === $post->id ? 'is-expanded' : '' }}">
                            <div class="fbr-post-summary">
                                <button type="button" wire:click="togglePost({{ $post->id }})" class="fbr-post-toggle" aria-expanded="{{ $expandedPostId === $post->id ? 'true' : 'false' }}">
                                    <span class="fbr-status-dot fbr-status-{{ $post->status }}"></span>
                                    <span class="fbr-post-main">
                                        <span class="fbr-post-kicker">
                                            @if($campaign === 'all' && $this->campaigns->count() > 1)<span>{{ $post->campaign->name }}</span>@endif
                                            <span style="--category-color:{{ $categoryMeta['color'] }}"><i class="fas {{ $categoryMeta['icon'] }}"></i> {{ __($categoryMeta['label']) }}</span>
                                        </span>
                                        <strong>{{ $post->title }}</strong>
                                        <span class="fbr-post-excerpt">{{ $post->hook }}</span>
                                    </span>
                                    <span class="fbr-post-meta">
                                        <span class="fbr-status-label fbr-status-{{ $post->status }}">{{ __(str($post->status)->title()->toString()) }}</span>
                                        @if($post->scheduled_for)<time datetime="{{ $post->scheduled_for->toIso8601String() }}"><i class="fas fa-calendar"></i> {{ $post->scheduled_for->format('M j, H:i') }}</time>@endif
                                        <span><i class="fas fa-text-width"></i> {{ mb_strlen($post->fullText()) }}</span>
                                    </span>
                                    <i class="fas fa-chevron-down fbr-chevron"></i>
                                </button>
                                <div class="fbr-post-actions">
                                    <button type="button" @click="copyText(@js($post->fullText()), 'post-{{ $post->id }}')" class="btn btn-primary btn-xs">
                                        <i class="fas" :class="copied === 'post-{{ $post->id }}' ? 'fa-check' : 'fa-copy'"></i>
                                        <span x-text="copied === 'post-{{ $post->id }}' ? @js(__('Copied')) : @js(__('Copy'))"></span>
                                    </button>
                                    <button type="button" wire:click="editPost({{ $post->id }})" class="btn btn-ghost btn-xs"><i class="fas fa-pen"></i> {{ __('Edit') }}</button>
                                </div>
                            </div>

                            @if($expandedPostId === $post->id)
                                <div class="fbr-post-detail">
                                    <section class="fbr-copy-preview">
                                        <div class="fbr-detail-heading"><span><i class="fas fa-align-left"></i> {{ __('Publishing copy') }}</span><small>{{ $post->length === 'long' ? __('Long post') : __('Short post') }}</small></div>
                                        <div class="fbr-copy-body">
                                            <strong>{{ $post->hook }}</strong>
                                            <p>{{ $post->body }}</p>
                                            @if($post->cta)<p class="fbr-cta">{{ $post->cta }}</p>@endif
                                            @if($post->hashtagList() !== [])
                                                <div class="fbr-hashtags">@foreach($post->hashtagList() as $hashtag)<span wire:key="post-{{ $post->id }}-tag-{{ $loop->index }}">{{ $hashtag }}</span>@endforeach</div>
                                            @endif
                                        </div>
                                    </section>

                                    <aside class="fbr-creative">
                                        <div class="fbr-detail-heading"><span><i class="fas fa-image"></i> {{ __('Creative asset') }}</span><small>{{ $post->aspect_ratio }}</small></div>
                                        @if($post->hasImage())
                                            <a href="{{ route('admin.social.poster', $post) }}?v={{ $post->imageVersion() }}" target="_blank" class="fbr-image">
                                                <img src="{{ route('admin.social.poster', $post) }}?v={{ $post->imageVersion() }}" alt="{{ $post->alt_text }}">
                                            </a>
                                            <div class="fbr-asset-actions">
                                                <a href="{{ route('admin.social.poster.download', $post) }}" class="btn btn-primary btn-xs"><i class="fas fa-download"></i> {{ __('Download') }}</a>
                                                <button type="button" wire:click.renderless="revealImageInExplorer({{ $post->id }})" class="btn btn-ghost btn-xs"><i class="fas fa-folder-open"></i> {{ __('Show in folder') }}</button>
                                            </div>
                                        @else
                                            <div class="fbr-image-pending">
                                                <i class="fas fa-image"></i>
                                                <strong>{{ __('Image pending') }}</strong>
                                                <span>{{ $post->image_file ?: __('No file name assigned') }}</span>
                                            </div>
                                        @endif

                                        @if($post->visual_brief || $post->image_prompt || $post->review_notes)
                                            <details class="fbr-creative-notes">
                                                <summary>{{ __('Creative brief and review notes') }}</summary>
                                                @if($post->visual_brief)<div><span>{{ __('Visual brief') }}</span><p>{{ $post->visual_brief }}</p></div>@endif
                                                @if($post->image_prompt)<div><span>{{ __('Image prompt') }}</span><p dir="ltr">{{ $post->image_prompt }}</p></div>@endif
                                                @if($post->reviewNoteList() !== [])
                                                    <div class="fbr-review"><span>{{ __('Check before publishing') }}</span><ul>@foreach($post->reviewNoteList() as $note)<li wire:key="post-{{ $post->id }}-note-{{ $loop->index }}">{{ $note }}</li>@endforeach</ul></div>
                                                @endif
                                            </details>
                                        @endif
                                    </aside>
                                </div>
                                <footer class="fbr-post-footer">
                                    <span>{{ __('Updated') }} {{ $post->updated_at->diffForHumans() }}</span>
                                    <button type="button" wire:click="confirmDeletePost({{ $post->id }})" class="fbr-danger-link"><i class="fas fa-trash"></i> {{ __('Delete post') }}</button>
                                </footer>
                            @endif
                        </article>
                    @endforeach
                </div>

                @if($this->posts->hasPages())
                    <div class="fbr-pagination">{{ $this->posts->links() }}</div>
                @endif
            @endif
        </main>
    </div>

    @if($showPostForm)
        <div class="modal-backdrop" wire:click.self="closePostForm" x-data @keydown.escape.window="$wire.closePostForm()">
            <form wire:submit="savePost" class="modal fbr-form-modal">
                <div class="fbr-modal-head">
                    <div><span>{{ $editingPostId ? __('Edit repository item') : __('Add repository item') }}</span><h3>{{ $editingPostId ? __('Edit Facebook post') : __('Create Facebook post') }}</h3></div>
                    <button type="button" wire:click="closePostForm" aria-label="{{ __('Close') }}"><i class="fas fa-xmark"></i></button>
                </div>

                <div class="fbr-form-grid">
                    <label class="fbr-field fbr-span-2"><span>{{ __('Campaign') }} *</span><select wire:model="postCampaignId" class="form-control">@foreach($this->campaigns as $campaignItem)<option value="{{ $campaignItem->id }}">{{ $campaignItem->name }}</option>@endforeach</select>@error('postCampaignId')<small>{{ $message }}</small>@enderror</label>
                    <label class="fbr-field fbr-span-2"><span>{{ __('Internal title') }} *</span><input type="text" wire:model="postTitle" class="form-control" placeholder="{{ __('A clear title for the repository') }}">@error('postTitle')<small>{{ $message }}</small>@enderror</label>
                    <label class="fbr-field"><span>{{ __('Status') }}</span><select wire:model.live="postStatus" class="form-control">@foreach(\App\Models\FacebookPost::STATUSES as $statusOption)<option value="{{ $statusOption }}">{{ __(str($statusOption)->title()->toString()) }}</option>@endforeach</select>@error('postStatus')<small>{{ $message }}</small>@enderror</label>
                    <label class="fbr-field"><span>{{ __('Category') }}</span><input type="text" wire:model="postCategory" list="facebook-categories" class="form-control" placeholder="hadith"><datalist id="facebook-categories">@foreach(array_keys(\App\Models\FacebookPost::CATEGORIES) as $categoryOption)<option value="{{ $categoryOption }}"></option>@endforeach</datalist>@error('postCategory')<small>{{ $message }}</small>@enderror</label>
                    <label class="fbr-field"><span>{{ __('Post length') }}</span><select wire:model="postLength" class="form-control"><option value="short">{{ __('Short') }}</option><option value="long">{{ __('Long') }}</option></select></label>
                    <label class="fbr-field"><span>{{ __('Suggested slot') }}</span><input type="text" wire:model="postPublishSlot" class="form-control" placeholder="{{ __('Monday evening') }}"></label>
                    @if($postStatus === 'scheduled')
                        <label class="fbr-field fbr-span-2"><span>{{ __('Scheduled for') }} *</span><input type="datetime-local" wire:model="postScheduledFor" class="form-control">@error('postScheduledFor')<small>{{ $message }}</small>@enderror</label>
                    @endif
                    <label class="fbr-field fbr-span-2"><span>{{ __('Hook') }} *</span><textarea wire:model="postHook" rows="2" class="form-control" placeholder="{{ __('The first line before See more') }}"></textarea>@error('postHook')<small>{{ $message }}</small>@enderror</label>
                    <label class="fbr-field fbr-span-2"><span>{{ __('Post body') }} *</span><textarea wire:model="postBody" rows="7" class="form-control" placeholder="{{ __('Write the publishing copy here') }}"></textarea>@error('postBody')<small>{{ $message }}</small>@enderror</label>
                    <label class="fbr-field fbr-span-2"><span>{{ __('Call to action') }}</span><textarea wire:model="postCta" rows="2" class="form-control"></textarea></label>
                    <label class="fbr-field fbr-span-2"><span>{{ __('Hashtags') }}</span><input type="text" wire:model="postHashtags" class="form-control" placeholder="#mojawad #quran"></label>
                </div>

                <details class="fbr-form-advanced">
                    <summary><span><i class="fas fa-wand-magic-sparkles"></i> {{ __('Creative asset details') }}</span><small>{{ __('Optional') }}</small></summary>
                    <div class="fbr-form-grid">
                        <label class="fbr-field"><span>{{ __('Aspect ratio') }}</span><select wire:model="postAspectRatio" class="form-control">@foreach(\App\Models\FacebookPost::ASPECT_RATIOS as $ratio)<option value="{{ $ratio }}">{{ $ratio }}</option>@endforeach</select></label>
                        <label class="fbr-field"><span>{{ __('Image file name') }}</span><input type="text" wire:model="postImageFile" class="form-control" placeholder="campaign-post.png">@error('postImageFile')<small>{{ $message }}</small>@enderror</label>
                        <label class="fbr-field fbr-span-2"><span>{{ __('Visual brief') }}</span><textarea wire:model="postVisualBrief" rows="3" class="form-control"></textarea></label>
                        <label class="fbr-field fbr-span-2"><span>{{ __('Image prompt') }}</span><textarea wire:model="postImagePrompt" rows="5" class="form-control" dir="ltr"></textarea></label>
                        <label class="fbr-field fbr-span-2"><span>{{ __('Text on poster') }}</span><textarea wire:model="postVisualText" rows="2" class="form-control"></textarea></label>
                        <label class="fbr-field fbr-span-2"><span>{{ __('Alt text') }}</span><textarea wire:model="postAltText" rows="2" class="form-control"></textarea></label>
                        <label class="fbr-field fbr-span-2"><span>{{ __('Review notes') }}</span><textarea wire:model="postReviewNotes" rows="3" class="form-control" placeholder="{{ __('One check per line') }}"></textarea></label>
                    </div>
                </details>

                <div class="fbr-modal-actions">
                    <button type="button" wire:click="closePostForm" class="btn btn-ghost">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="savePost"><span wire:loading.remove wire:target="savePost"><i class="fas fa-check"></i> {{ __('Save post') }}</span><span wire:loading wire:target="savePost">{{ __('Saving…') }}</span></button>
                </div>
            </form>
        </div>
    @endif

    @if($showCampaignForm)
        <div class="modal-backdrop" wire:click.self="closeCampaignForm" x-data @keydown.escape.window="$wire.closeCampaignForm()">
            <form wire:submit="saveCampaign" class="modal fbr-form-modal fbr-campaign-modal">
                <div class="fbr-modal-head">
                    <div><span>{{ __('Campaign brief') }}</span><h3>{{ $editingCampaignId ? __('Edit campaign') : __('New campaign') }}</h3></div>
                    <button type="button" wire:click="closeCampaignForm" aria-label="{{ __('Close') }}"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="fbr-form-grid">
                    <label class="fbr-field fbr-span-2"><span>{{ __('Campaign name') }} *</span><input type="text" wire:model="campaignName" class="form-control" autofocus>@error('campaignName')<small>{{ $message }}</small>@enderror</label>
                    <label class="fbr-field fbr-span-2"><span>{{ __('Goal') }}</span><textarea wire:model="campaignGoal" rows="3" class="form-control"></textarea></label>
                    <label class="fbr-field fbr-span-2"><span>{{ __('Audience') }}</span><textarea wire:model="campaignAudience" rows="2" class="form-control"></textarea></label>
                    <label class="fbr-field"><span>{{ __('Cadence') }}</span><input type="text" wire:model="campaignCadence" class="form-control" placeholder="{{ __('3 posts per week') }}"></label>
                    <label class="fbr-field"><span>{{ __('Core hashtags') }}</span><input type="text" wire:model="campaignHashtags" class="form-control" placeholder="#mojawad #quran"></label>
                    <label class="fbr-field fbr-span-2"><span>{{ __('Tone guidelines') }}</span><textarea wire:model="campaignTone" rows="3" class="form-control" placeholder="{{ __('One guideline per line') }}"></textarea></label>
                    <label class="fbr-field fbr-span-2"><span>{{ __('Image workflow') }}</span><textarea wire:model="campaignImageWorkflow" rows="3" class="form-control"></textarea></label>
                    <label class="fbr-check fbr-span-2"><input type="checkbox" wire:model="campaignIsActive"><span><strong>{{ __('Active campaign') }}</strong><small>{{ __('Show this campaign first in the repository.') }}</small></span></label>
                </div>
                <div class="fbr-modal-actions"><button type="button" wire:click="closeCampaignForm" class="btn btn-ghost">{{ __('Cancel') }}</button><button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveCampaign"><i class="fas fa-check"></i> {{ __('Save campaign') }}</button></div>
            </form>
        </div>
    @endif

    @if($deletePostId)
        <div class="modal-backdrop" wire:click.self="$set('deletePostId', null)" x-data @keydown.escape.window="$wire.set('deletePostId', null)">
            <div class="modal fbr-confirm-modal"><span class="fbr-confirm-icon"><i class="fas fa-trash"></i></span><h3>{{ __('Delete this post?') }}</h3><p>{{ __('This removes the post from the campaign repository. This action cannot be undone.') }}</p><div><button type="button" wire:click="$set('deletePostId', null)" class="btn btn-ghost">{{ __('Cancel') }}</button><button type="button" wire:click="deletePost" class="btn btn-danger">{{ __('Delete post') }}</button></div></div>
        </div>
    @endif

    @if($deleteCampaignId)
        <div class="modal-backdrop" wire:click.self="$set('deleteCampaignId', null)" x-data @keydown.escape.window="$wire.set('deleteCampaignId', null)">
            <div class="modal fbr-confirm-modal"><span class="fbr-confirm-icon"><i class="fas fa-folder-minus"></i></span><h3>{{ __('Delete this campaign?') }}</h3><p>{{ __('Only empty campaigns can be deleted. This action cannot be undone.') }}</p><div><button type="button" wire:click="$set('deleteCampaignId', null)" class="btn btn-ghost">{{ __('Cancel') }}</button><button type="button" wire:click="deleteCampaign" class="btn btn-danger">{{ __('Delete campaign') }}</button></div></div>
        </div>
    @endif

    <script>
    function campaignRepository() {
        return {
            copied: null,
            copyTimer: null,
            async copyText(text, key) {
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(text);
                    } else {
                        const textarea = document.createElement('textarea');
                        textarea.value = text;
                        textarea.style.position = 'fixed';
                        textarea.style.opacity = '0';
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        textarea.remove();
                    }

                    this.copied = key;
                    window.clearTimeout(this.copyTimer);
                    this.copyTimer = window.setTimeout(() => this.copied = null, 1800);
                } catch (error) {
                    this.copied = null;
                }
            },
        };
    }
    </script>
</div>
