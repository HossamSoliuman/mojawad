<div x-data="campaignCopy()">

    {{-- ══════════ CAMPAIGN BRIEF ══════════ --}}
    @if($this->meta !== [])
    <section class="fbc-brief">
        <div class="fbc-brief-head">
            <div>
                <span class="fbc-brief-eyebrow"><i class="fab fa-facebook"></i> {{ $this->meta['name'] ?? __('Facebook campaign') }}</span>
                <p>{{ $this->meta['goal'] ?? '' }}</p>
            </div>
            <div class="fbc-brief-paths">
                <div class="fbc-brief-file" title="{{ $this->filePath }}">
                    <i class="fas fa-file-code"></i>
                    <span>{{ __('Campaign file') }}</span>
                    <button type="button" @click="copyText(@js($this->filePath), 'campaign-file')">
                        <i class="fas" :class="copied === 'campaign-file' ? 'fa-check' : 'fa-copy'"></i>
                        <span x-text="copied === 'campaign-file' ? @js(__('Copied')) : @js(__('Copy path'))"></span>
                    </button>
                </div>
                <div class="fbc-brief-file" title="{{ $this->imageDirectory }}">
                    <i class="fas fa-images"></i>
                    <span>{{ __('Images folder') }}</span>
                    <button type="button" @click="copyText(@js($this->imageDirectory), 'campaign-images')">
                        <i class="fas" :class="copied === 'campaign-images' ? 'fa-check' : 'fa-copy'"></i>
                        <span x-text="copied === 'campaign-images' ? @js(__('Copied')) : @js(__('Copy path'))"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="fbc-brief-grid">
            @if(! empty($this->meta['audience']))
            <div><span class="fbc-brief-lbl">{{ __('Audience') }}</span>{{ $this->meta['audience'] }}</div>
            @endif
            @if(! empty($this->meta['cadence']))
            <div><span class="fbc-brief-lbl">{{ __('Cadence') }}</span>{{ $this->meta['cadence'] }}</div>
            @endif
            @if(! empty($this->meta['core_hashtags']))
            <div><span class="fbc-brief-lbl">{{ __('Core hashtags') }}</span>{{ implode(' ', $this->meta['core_hashtags']) }}</div>
            @endif
        </div>

        @if(! empty($this->meta['tone']))
        <ul class="fbc-tone">
            @foreach($this->meta['tone'] as $rule)
            <li><i class="fas fa-check"></i> {{ $rule }}</li>
            @endforeach
        </ul>
        @endif

        @if(! empty($this->meta['image_workflow']))
        <div class="fbc-workflow">
            <i class="fas fa-wand-magic-sparkles"></i>
            <span>{{ $this->meta['image_workflow'] }}</span>
        </div>
        @endif
    </section>
    @endif

    {{-- ══════════ FILTERS ══════════ --}}
    <div class="fbc-filters">
        <div class="range-pills" style="display:inline-flex;gap:.15rem;flex-wrap:wrap">
            @foreach($this->categoryChips as $chip)
            <button type="button" wire:key="cat-{{ $chip['key'] }}" wire:click="$set('category', '{{ $chip['key'] }}')"
                    class="range-pill {{ $category === $chip['key'] ? 'active' : '' }}"
                    style="border:0;cursor:pointer;padding:.45rem 1rem;{{ $category === $chip['key'] ? '' : 'background:transparent' }}">
                <i class="fas {{ $chip['icon'] }}"></i> {{ $chip['label'] }} ({{ $chip['count'] }})
            </button>
            @endforeach
        </div>

        <div class="fbc-filters-row">
            <label class="fbc-select">
                <span>{{ __('Length') }}</span>
                <select wire:model.live="length">
                    <option value="all">{{ __('Any length') }}</option>
                    <option value="short">{{ __('Short post') }}</option>
                    <option value="long">{{ __('Long post') }}</option>
                </select>
            </label>

            <label class="fbc-select">
                <span>{{ __('Visual') }}</span>
                <select wire:model.live="visual">
                    <option value="all">{{ __('Any visual') }}</option>
                    <option value="poster">{{ __('Poster') }}</option>
                    <option value="still">{{ __('Still') }}</option>
                </select>
            </label>

            <label class="fbc-select fbc-search">
                <span>{{ __('Search') }}</span>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search inside the posts…') }}">
            </label>

            <button type="button" wire:click="$toggle('needsImage')"
                    class="btn btn-xs {{ $needsImage ? 'btn-primary' : 'btn-ghost' }}"
                    title="{{ __('Show only the posts whose image still has to be generated') }}">
                <i class="fas {{ $needsImage ? 'fa-square-check' : 'fa-square' }}"></i> {{ __('Needs an image') }}
            </button>

            @if($this->hasFilters)
            <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-xs">
                <i class="fas fa-rotate-left"></i> {{ __('Clear filters') }}
            </button>
            @endif
        </div>
    </div>

    {{-- ══════════ POSTS ══════════ --}}
    @if($this->fileMissing)
        <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
            <i class="fas fa-file-circle-question" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
            <div style="font-size:.95rem">{{ __('The campaign file was not found.') }}</div>
            <div style="font-size:.8rem;color:var(--text3);margin-top:.35rem">{{ $this->filePath }}</div>
        </div>
    @elseif($this->posts === [])
        <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
            <i class="fas fa-magnifying-glass" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
            <div style="font-size:.95rem">{{ __('No post matches these filters.') }}</div>
        </div>
    @else
        <div class="fbc-list">
            @foreach($this->posts as $post)
            <article wire:key="post-{{ $post['id'] }}" class="sharing-kit fbc-post">

                {{-- Header: what this post is, and one button that copies the whole thing --}}
                <div class="sharing-kit-header">
                    <div style="min-width:0">
                        <div class="fbc-tags">
                            <span class="fbc-tag" style="--tag-color:{{ $post['category_color'] }}">
                                <i class="fas {{ $post['category_icon'] }}"></i> {{ $post['category_label'] }}
                            </span>
                            <span class="fbc-tag fbc-tag-{{ $post['length'] }}">
                                <i class="fas {{ $post['length'] === 'long' ? 'fa-align-left' : 'fa-bolt' }}"></i>
                                {{ $post['length'] === 'long' ? __('Long post') : __('Short post') }}
                            </span>
                            <span class="fbc-tag fbc-tag-{{ $post['visual'] }}">
                                <i class="fas {{ $post['visual'] === 'poster' ? 'fa-paintbrush' : 'fa-image' }}"></i>
                                {{ $post['visual'] === 'poster' ? __('Poster') : __('Still') }}
                            </span>
                            @if($post['publish_slot'] !== '')
                            <span class="fbc-tag fbc-tag-slot"><i class="fas fa-clock"></i> {{ $post['publish_slot'] }}</span>
                            @endif
                            <span class="fbc-tag fbc-tag-count"><i class="fas fa-text-width"></i> {{ $post['char_count'] }} {{ __('chars') }}</span>
                        </div>
                        <h3 class="fbc-title">{{ $post['title'] }}</h3>
                    </div>
                    <button type="button" class="btn btn-primary btn-xs"
                            @click="copyText(@js($post['text']), 'post-{{ $post['id'] }}')">
                        <i class="fas" :class="copied === 'post-{{ $post['id'] }}' ? 'fa-check' : 'fa-copy'"></i>
                        <span x-text="copied === 'post-{{ $post['id'] }}' ? @js(__('Copied')) : @js(__('Copy post'))"></span>
                    </button>
                </div>

                <div class="fbc-grid">

                    {{-- Left: the post exactly as it will be pasted --}}
                    <div class="fbc-col">
                        <div class="sharing-hook">
                            <div class="sharing-field-heading">
                                <span><i class="fas fa-fire"></i> {{ __('Hook — first line before “See more”') }}</span>
                                <button type="button" @click="copyText(@js($post['hook']), 'hook-{{ $post['id'] }}')">
                                    <i class="fas" :class="copied === 'hook-{{ $post['id'] }}' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'hook-{{ $post['id'] }}' ? @js(__('Copied')) : @js(__('Copy'))"></span>
                                </button>
                            </div>
                            <div class="sharing-copy-text">{{ $post['hook'] }}</div>
                        </div>

                        <div class="sharing-field">
                            <div class="sharing-field-heading">
                                <span><i class="fas fa-align-right"></i> {{ __('Post text') }}</span>
                                <button type="button" @click="copyText(@js(implode("\n\n", $post['body'])), 'body-{{ $post['id'] }}')">
                                    <i class="fas" :class="copied === 'body-{{ $post['id'] }}' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'body-{{ $post['id'] }}' ? @js(__('Copied')) : @js(__('Copy'))"></span>
                                </button>
                            </div>
                            <div class="sharing-copy-text">{{ implode("\n\n", $post['body']) }}</div>
                        </div>

                        @if($post['cta'] !== '')
                        <div class="sharing-field">
                            <div class="sharing-field-heading">
                                <span><i class="fas fa-hand-pointer"></i> {{ __('Call to action') }}</span>
                                <button type="button" @click="copyText(@js($post['cta']), 'cta-{{ $post['id'] }}')">
                                    <i class="fas" :class="copied === 'cta-{{ $post['id'] }}' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'cta-{{ $post['id'] }}' ? @js(__('Copied')) : @js(__('Copy'))"></span>
                                </button>
                            </div>
                            <div class="sharing-copy-text">{{ $post['cta'] }}</div>
                        </div>
                        @endif

                        @if($post['hashtags'] !== [])
                        <div class="sharing-field">
                            <div class="sharing-field-heading">
                                <span><i class="fas fa-hashtag"></i> {{ __('Hashtags') }}</span>
                                <button type="button" @click="copyText(@js(implode(' ', $post['hashtags'])), 'tags-{{ $post['id'] }}')">
                                    <i class="fas" :class="copied === 'tags-{{ $post['id'] }}' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'tags-{{ $post['id'] }}' ? @js(__('Copied')) : @js(__('Copy'))"></span>
                                </button>
                            </div>
                            <div class="fbc-hashtags">
                                @foreach($post['hashtags'] as $hashtag)
                                <span>{{ $hashtag }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Right: the image that ships with the text — generated, then dropped in the folder --}}
                    <aside class="fbc-col fbc-visual fbc-visual-{{ $post['visual'] }}">
                        <div class="fbc-visual-head">
                            <i class="fas {{ $post['visual'] === 'poster' ? 'fa-paintbrush' : 'fa-image' }}"></i>
                            <span>{{ $post['visual'] === 'poster' ? __('Poster — designed artwork') : __('Still — plain image, no design') }}</span>
                            @if($post['aspect_ratio'] !== '')
                            <span class="fbc-ratio">{{ $post['aspect_ratio'] }}</span>
                            @endif
                        </div>

                        {{-- The image space itself: the generated file, or where to put it --}}
                        <div class="fbc-image-slot">
                            @if($post['image_ready'])
                            <a href="{{ route('admin.social.poster', $post['id']) }}?v={{ $post['image_version'] }}" target="_blank"
                               class="fbc-image-frame" style="aspect-ratio:{{ str_replace(':', '/', $post['aspect_ratio'] ?: '1:1') }}">
                                <img src="{{ route('admin.social.poster', $post['id']) }}?v={{ $post['image_version'] }}" alt="{{ $post['alt_text'] }}">
                                <span class="fbc-image-zoom"><i class="fas fa-up-right-and-down-left-from-center"></i></span>
                            </a>

                            <div class="fbc-image-actions">
                                <a href="{{ route('admin.social.poster.download', $post['id']) }}" class="btn btn-primary btn-xs">
                                    <i class="fas fa-download"></i> {{ __('Download image') }}
                                </a>
                                <button type="button" class="btn btn-ghost btn-xs"
                                        title="{{ __('Copy the full file path for pasting into a file picker') }}"
                                        @click="copyText(@js($post['image_path']), 'imgpath-{{ $post['id'] }}')">
                                    <i class="fas" :class="copied === 'imgpath-{{ $post['id'] }}' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'imgpath-{{ $post['id'] }}' ? @js(__('Path copied')) : @js(__('Copy image path'))"></span>
                                </button>
                                <button type="button" wire:click.renderless="revealImageInExplorer('{{ $post['id'] }}')"
                                        wire:loading.attr="disabled" wire:target="revealImageInExplorer('{{ $post['id'] }}')"
                                        class="btn btn-ghost btn-xs">
                                    <i class="fas fa-folder-open"></i> {{ __('Show in folder') }}
                                </button>
                            </div>
                            <div class="fbc-image-file">{{ $post['image_file'] }}</div>
                            @else
                            <div class="fbc-image-empty" style="aspect-ratio:{{ str_replace(':', '/', $post['aspect_ratio'] ?: '1:1') }}">
                                <i class="fas fa-wand-magic-sparkles"></i>
                                <strong>{{ __('Image not generated yet') }}</strong>
                                <span>{{ __('Generate it with the prompt below, then save it in the images folder as:') }}</span>
                                <code>{{ $post['image_file'] !== '' ? $post['image_file'] : __('— no file name set in the campaign file —') }}</code>
                            </div>
                            <div class="fbc-image-actions">
                                <button type="button" class="btn btn-ghost btn-xs"
                                        @click="copyText(@js($this->imageDirectory), 'imgdir-{{ $post['id'] }}')">
                                    <i class="fas" :class="copied === 'imgdir-{{ $post['id'] }}' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'imgdir-{{ $post['id'] }}' ? @js(__('Path copied')) : @js(__('Copy images folder'))"></span>
                                </button>
                                @if($post['image_file'] !== '')
                                <button type="button" class="btn btn-ghost btn-xs"
                                        @click="copyText(@js($post['image_file']), 'imgname-{{ $post['id'] }}')">
                                    <i class="fas" :class="copied === 'imgname-{{ $post['id'] }}' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'imgname-{{ $post['id'] }}' ? @js(__('Copied')) : @js(__('Copy file name'))"></span>
                                </button>
                                @endif
                            </div>
                            @endif
                        </div>

                        @if($post['image_prompt'] !== '')
                        <div class="sharing-field">
                            <div class="sharing-field-heading">
                                <span><i class="fas fa-robot"></i> {{ __('Image prompt — paste into ChatGPT') }}</span>
                                <button type="button" @click="copyText(@js($post['image_prompt']), 'prompt-{{ $post['id'] }}')">
                                    <i class="fas" :class="copied === 'prompt-{{ $post['id'] }}' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'prompt-{{ $post['id'] }}' ? @js(__('Copied')) : @js(__('Copy prompt'))"></span>
                                </button>
                            </div>
                            <div class="sharing-copy-text fbc-prompt" dir="ltr">{{ $post['image_prompt'] }}</div>
                        </div>
                        @endif

                        <div class="sharing-field">
                            <div class="sharing-field-heading">
                                <span>{{ __('Visual brief') }}</span>
                                <button type="button" @click="copyText(@js($post['visual_brief']), 'brief-{{ $post['id'] }}')">
                                    <i class="fas" :class="copied === 'brief-{{ $post['id'] }}' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'brief-{{ $post['id'] }}' ? @js(__('Copied')) : @js(__('Copy'))"></span>
                                </button>
                            </div>
                            <div class="sharing-copy-text">{{ $post['visual_brief'] }}</div>
                        </div>

                        @if($post['visual_text'] !== '')
                        <div class="sharing-field">
                            <div class="sharing-field-heading">
                                <span>{{ __('Text on the poster') }}</span>
                                <button type="button" @click="copyText(@js($post['visual_text']), 'vtext-{{ $post['id'] }}')">
                                    <i class="fas" :class="copied === 'vtext-{{ $post['id'] }}' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'vtext-{{ $post['id'] }}' ? @js(__('Copied')) : @js(__('Copy'))"></span>
                                </button>
                            </div>
                            <div class="sharing-copy-text fbc-poster-text">{{ $post['visual_text'] }}</div>
                        </div>
                        @endif

                        @if($post['alt_text'] !== '')
                        <div class="sharing-field">
                            <div class="sharing-field-heading">
                                <span>{{ __('Alt text') }}</span>
                                <button type="button" @click="copyText(@js($post['alt_text']), 'alt-{{ $post['id'] }}')">
                                    <i class="fas" :class="copied === 'alt-{{ $post['id'] }}' ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied === 'alt-{{ $post['id'] }}' ? @js(__('Copied')) : @js(__('Copy'))"></span>
                                </button>
                            </div>
                            <div class="sharing-copy-text">{{ $post['alt_text'] }}</div>
                        </div>
                        @endif

                        @if($post['verify'] !== [])
                        <div class="fbc-verify">
                            <div class="fbc-verify-head"><i class="fas fa-triangle-exclamation"></i> {{ __('Check before publishing') }}</div>
                            <ul>
                                @foreach($post['verify'] as $item)
                                <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </aside>
                </div>
            </article>
            @endforeach
        </div>
    @endif

    <script>
    function campaignCopy(){
        return {
            copied:null, copyTimer:null,
            async copyText(text, key){
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
