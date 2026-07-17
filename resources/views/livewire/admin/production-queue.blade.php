<div x-data="brandPreview()" @if($this->hasActive) wire:poll.5s @endif>

    {{-- Tab bar: Create videos ↔ Publish & track --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;gap:1rem;flex-wrap:wrap">
        <div class="range-pills" style="display:inline-flex;gap:.15rem">
            <button type="button" wire:click="$set('tab', 'create')"
                    class="range-pill {{ $tab === 'create' ? 'active' : '' }}"
                    style="border:0;cursor:pointer;padding:.45rem 1rem;{{ $tab === 'create' ? '' : 'background:transparent' }}">
                <i class="fas fa-clapperboard"></i> {{ __('Create videos') }} ({{ $this->createCount }})
            </button>
            <button type="button" wire:click="$set('tab', 'publish')"
                    class="range-pill {{ $tab === 'publish' ? 'active' : '' }}"
                    style="border:0;cursor:pointer;padding:.45rem 1rem;{{ $tab === 'publish' ? '' : 'background:transparent' }}">
                <i class="fas fa-tower-broadcast"></i> {{ __('Publish & track') }} ({{ $this->publishCount }})
            </button>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center">
            @if($tab === 'create' && count($selected) > 0)
            <button type="button" wire:click="bulkPrepare" class="btn btn-primary btn-sm">
                <i class="fas fa-gears"></i> {{ __('Prepare selected') }} ({{ count($selected) }})
            </button>
            @endif
            @if($this->hasActive)
            <span class="badge badge-amber"><i class="fas fa-spinner fa-spin"></i> {{ __('Working…') }}</span>
            @endif
        </div>
    </div>

    @if($tab === 'create')
        {{-- ══════════ CREATE TAB: design the card, render the animated video ══════════ --}}
        @if($this->createList->isEmpty())
            <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
                <i class="fas fa-clapperboard" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
                <div style="font-size:.95rem">{{ __('Nothing to create yet. Ingest a source in the Factory first.') }}</div>
            </div>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead><tr>
                        <th style="width:32px"></th>
                        <th>{{ __('Recitation') }}</th>
                        <th>{{ __('Video card') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach($this->createList as $tilawa)
                        <tr wire:key="prod-c-{{ $tilawa->id }}">
                            <td><input type="checkbox" value="{{ $tilawa->id }}" wire:model.live="selected" @disabled($tilawa->brand_status === 'processing')></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:.72rem">
                                    <span style="width:44px;height:44px;border-radius:8px;background:var(--bg3,#1e1e35);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--text3);overflow:hidden">
                                        @if($tilawa->qari?->image)
                                        <img src="{{ $tilawa->qari->image_url }}" style="width:100%;height:100%;object-fit:cover" alt="">
                                        @else
                                        <i class="fas fa-book-quran"></i>
                                        @endif
                                    </span>
                                    <div style="min-width:0">
                                        <div style="font-weight:600;font-size:.86rem">{{ $tilawa->title_ar }}</div>
                                        <div style="font-size:.72rem;color:var(--text3)">{{ $tilawa->qari?->name }} · {{ $tilawa->formatted_duration }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if(filled($tilawa->brand_card['qari_name'] ?? $tilawa->brand_card['surah_name'] ?? $tilawa->brand_card['extra_text'] ?? null))
                                <span class="badge badge-green"><i class="fas fa-wand-magic-sparkles"></i> {{ __('Customized') }}</span>
                                @else
                                <span class="badge badge-muted">{{ __('Default card') }}</span>
                                @endif
                            </td>
                            <td>
                                @php($bmap = [
                                    'none'       => ['badge-muted', __('Not prepared'), 'fa-circle-dashed'],
                                    'processing' => ['badge-amber', __('Preparing…'),   'fa-spinner fa-spin'],
                                    'failed'     => ['badge-red',   __('Failed'),       'fa-circle-exclamation'],
                                ])
                                @php([$bcls, $blabel, $bicon] = $bmap[$tilawa->brand_status] ?? ['badge-muted', $tilawa->brand_status, 'fa-question'])
                                <span class="badge {{ $bcls }}"><i class="fas {{ $bicon }}"></i> {{ $blabel }}</span>
                                @if($tilawa->brand_status === 'failed' && $tilawa->brand_error)
                                <div style="font-size:.74rem;color:#e86060;margin-top:.35rem;max-width:220px" title="{{ $tilawa->brand_error }}">{{ Str::limit($tilawa->brand_error, 70) }}</div>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:.2rem;align-items:center;flex-wrap:wrap">
                                    @if($tilawa->brand_status !== 'processing')
                                    <button type="button" wire:click="openEditor({{ $tilawa->id }})" class="btn btn-primary btn-xs">
                                        <i class="fas fa-wand-magic-sparkles"></i> {{ __('Design & render') }}
                                    </button>
                                    <button type="button" wire:click="prepare({{ $tilawa->id }})" class="btn btn-ghost btn-xs">
                                        <i class="fas fa-gears"></i> {{ $tilawa->brand_status === 'failed' ? __('Retry') : __('Quick render') }}
                                    </button>
                                    @endif
                                    <button type="button" wire:click="confirmDelete({{ $tilawa->id }})"
                                            class="btn-icon" style="color:var(--red)" title="{{ __('Remove') }}"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @else
        {{-- ══════════ PUBLISH TAB: publish ready videos, manage & track them ══════════ --}}
        <div style="display:flex;gap:.3rem;margin-bottom:.9rem;flex-wrap:wrap">
            @foreach(['all' => __('All'), 'unpublished' => __('Awaiting publish'), 'published' => __('Published')] as $filter => $flabel)
            <button type="button" wire:click="$set('publishFilter', '{{ $filter }}')"
                    class="btn {{ $publishFilter === $filter ? 'btn-primary' : 'btn-ghost' }} btn-xs">{{ $flabel }}</button>
            @endforeach
        </div>

        @if($this->publishList->isEmpty())
            <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
                <i class="fas fa-tower-broadcast" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
                <div style="font-size:.95rem">{{ __('No videos here yet. Render one in the Create tab first.') }}</div>
            </div>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead><tr>
                        <th>{{ __('Recitation') }}</th>
                        <th>{{ __('Platforms & tracking') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach($this->publishList as $tilawa)
                        <tr wire:key="prod-p-{{ $tilawa->id }}">
                            <td>
                                <div style="display:flex;align-items:center;gap:.72rem">
                                    @php($thumb = $tilawa->brand_card_image_url ?? $tilawa->brand_cover_url)
                                    @if($thumb)
                                    <img src="{{ $thumb }}" style="width:78px;height:44px;border-radius:8px;object-fit:cover;flex-shrink:0;background:#000" alt="">
                                    @else
                                    <span style="width:78px;height:44px;border-radius:8px;background:var(--bg3,#1e1e35);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--text3)"><i class="fas fa-film"></i></span>
                                    @endif
                                    <div style="min-width:0">
                                        <div style="font-weight:600;font-size:.86rem">{{ $tilawa->title_ar }}</div>
                                        <div style="font-size:.72rem;color:var(--text3)">
                                            {{ $tilawa->qari?->name }} · {{ $tilawa->formatted_duration }}
                                            · <i class="fas fa-headphones" style="opacity:.7"></i> {{ number_format($tilawa->plays_count) }} {{ __('plays') }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php($pubs = $tilawa->publications->keyBy('platform'))
                                <div style="display:flex;flex-direction:column;gap:.3rem">
                                    @foreach(['podcast' => __('Podcast (Spotify / Anghami)'), 'youtube' => __('YouTube'), 'facebook' => __('Facebook')] as $platform => $plabel)
                                        @php($pub = $pubs->get($platform))
                                        @if($pub)
                                        @php($pmap = [
                                            'pending'    => ['badge-muted', __('Pending'),     'fa-clock'],
                                            'processing' => ['badge-amber', __('Publishing…'), 'fa-spinner fa-spin'],
                                            'completed'  => ['badge-green', __('Published'),   'fa-circle-check'],
                                            'failed'     => ['badge-red',   __('Failed'),      'fa-circle-exclamation'],
                                        ])
                                        @php([$pcls, $plbl, $pic] = $pmap[$pub->status] ?? ['badge-muted', $pub->status, 'fa-question'])
                                        <div style="display:flex;align-items:center;gap:.3rem;flex-wrap:wrap">
                                            <span style="font-size:.72rem;color:var(--text3);width:112px">{{ $plabel }}</span>
                                            @if($pub->status === 'completed' && $pub->external_url)
                                            <a href="{{ $pub->external_url }}" target="_blank" rel="noopener" class="badge {{ $pcls }}" style="text-decoration:none"><i class="fas {{ $pic }}"></i> {{ $plbl }} <i class="fas fa-arrow-up-right-from-square" style="font-size:.6rem"></i></a>
                                            @else
                                            <span class="badge {{ $pcls }}"><i class="fas {{ $pic }}"></i> {{ $plbl }}</span>
                                            @endif
                                            @if($pub->status === 'completed' && $pub->published_at)
                                            <span style="font-size:.7rem;color:var(--text3)">{{ $pub->published_at->diffForHumans() }}</span>
                                            @endif
                                            @if($pub->status === 'failed')
                                            <button type="button" wire:click="retryPublication({{ $pub->id }})" class="btn-icon" title="{{ __('Retry') }}"><i class="fas fa-rotate-right"></i></button>
                                            @if($pub->error)
                                            <span style="font-size:.7rem;color:#e86060;max-width:180px" title="{{ $pub->error }}">{{ Str::limit($pub->error, 40) }}</span>
                                            @endif
                                            @endif
                                        </div>
                                        @endif
                                    @endforeach
                                    @if($pubs->isEmpty())
                                    <span style="font-size:.74rem;color:var(--text3)">{{ __('Not published yet') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="display:flex;gap:.2rem;align-items:center;flex-wrap:wrap">
                                    <button type="button" class="btn btn-ghost btn-xs"
                                            @click="show(@js($tilawa->brand_video_url), @js($tilawa->brand_card_image_url ?? $tilawa->brand_cover_url), @js($tilawa->title_ar))">
                                        <i class="fas fa-eye"></i> {{ __('Preview') }}
                                    </button>
                                    <button type="button" wire:click="openPublish({{ $tilawa->id }})" class="btn btn-primary btn-xs">
                                        <i class="fas fa-tower-broadcast"></i> {{ __('Publish') }}
                                    </button>
                                    <button type="button" wire:click="prepare({{ $tilawa->id }})" class="btn-icon" title="{{ __('Re-render the video') }}">
                                        <i class="fas fa-rotate"></i>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $tilawa->id }})"
                                            class="btn-icon" style="color:var(--red)" title="{{ __('Remove') }}"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    {{-- Card editor: texts, optional info about the tilawa, and the animation toggles --}}
    @if($editingId !== null)
    <div class="modal-backdrop" wire:click.self="closeEditor" x-data @keydown.escape.window="$wire.closeEditor()">
        <div class="modal" style="max-width:1120px;width:96vw;max-height:92vh;overflow:auto">
            <div class="modal-title" style="margin-bottom:1rem"><i class="fas fa-wand-magic-sparkles"></i> {{ __('Video card & animation') }}</div>
            <div style="display:flex;gap:1.4rem;align-items:flex-start;flex-wrap:wrap">
                <div style="flex:0 0 300px;max-width:100%;display:flex;flex-direction:column;gap:.8rem">
                    <div>
                        <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">{{ __('Qari name') }}</label>
                        <input type="text" wire:model.live.debounce.400ms="cardQariName" class="form-control" style="width:100%">
                    </div>
                    <div>
                        <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">{{ __('Surah name') }}</label>
                        <input type="text" wire:model.live.debounce.400ms="cardSurahName" class="form-control" style="width:100%">
                    </div>
                    <div>
                        <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">{{ __('Description / info (optional)') }}</label>
                        <textarea wire:model.live.debounce.400ms="cardExtraText" rows="4" class="form-control" style="width:100%;resize:vertical"
                                  placeholder="{{ __('e.g. where and when the tilawa was recorded') }}"></textarea>
                    </div>
                    <div style="border:1px solid var(--border);border-radius:10px;padding:.7rem;display:flex;flex-direction:column;gap:.5rem">
                        <div style="font-size:.78rem;font-weight:700"><i class="fas fa-film"></i> {{ __('Animation') }}</div>
                        <label style="display:flex;align-items:center;gap:.45rem;font-size:.8rem;cursor:pointer">
                            <input type="checkbox" wire:model.live="cardAnimatePhoto"> {{ __('Photo drifts gently up & down') }}
                        </label>
                        <label style="display:flex;align-items:center;gap:.45rem;font-size:.8rem;cursor:pointer">
                            <input type="checkbox" wire:model.live="cardAnimateText"> {{ __('Text rises & fades in') }}
                        </label>
                    </div>
                </div>
                <div style="flex:1;min-width:320px">
                    <div style="font-size:.8rem;font-weight:600;margin-bottom:.4rem">
                        {{ __('Live preview') }}
                        <span style="color:var(--text3);font-weight:400">({{ __('animates like the final video') }})</span>
                    </div>
                    <div style="position:relative;width:672px;max-width:100%;aspect-ratio:16/9;overflow:hidden;border-radius:12px;border:1px solid var(--border);background:#000">
                        <iframe srcdoc="{{ $this->editorPreviewHtml }}"
                                style="position:absolute;top:0;left:0;width:1920px;height:1080px;transform:scale(.35);transform-origin:top left;border:0;pointer-events:none"></iframe>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:.5rem;margin-top:1.2rem;flex-wrap:wrap">
                <button type="button" wire:click="saveAndPrepare" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                    <span wire:loading.remove wire:target="saveAndPrepare"><i class="fas fa-clapperboard"></i> {{ __('Save & render video') }}</span>
                    <span wire:loading wire:target="saveAndPrepare"><i class="fas fa-spinner fa-spin"></i> {{ __('Working…') }}</span>
                </button>
                <button type="button" wire:click="saveCard" class="btn btn-ghost btn-sm"><i class="fas fa-floppy-disk"></i> {{ __('Save only') }}</button>
                <button type="button" wire:click="closeEditor" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Publish platform picker --}}
    @if($publishFor !== null)
    <div class="modal-backdrop" wire:click.self="cancelPublish" x-data @keydown.escape.window="$wire.cancelPublish()">
        <div class="modal" style="max-width:420px">
            <div class="modal-title" style="margin-bottom:.8rem"><i class="fas fa-tower-broadcast"></i> {{ __('Select platforms') }}</div>
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;margin-bottom:.45rem;cursor:pointer">
                <input type="checkbox" value="podcast" wire:model="platforms"> {{ __('Podcast (Spotify / Anghami)') }}
            </label>
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;margin-bottom:.45rem;cursor:pointer">
                <input type="checkbox" value="youtube" wire:model="platforms"> {{ __('YouTube') }}
                @unless($youtubeEnabled)<span style="font-size:.72rem;color:var(--text3)">({{ __('Not configured') }})</span>@endunless
            </label>
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;margin-bottom:1rem;cursor:pointer">
                <input type="checkbox" value="facebook" wire:model="platforms"> {{ __('Facebook') }}
                @unless($facebookEnabled)<span style="font-size:.72rem;color:var(--text3)">({{ __('Not configured') }})</span>@endunless
            </label>
            <div style="display:flex;gap:.6rem">
                <button type="button" wire:click="cancelPublish" class="btn btn-ghost" style="flex:1;justify-content:center">{{ __('Cancel') }}</button>
                <button type="button" wire:click="doPublish" class="btn btn-primary" style="flex:1;justify-content:center"><i class="fas fa-check"></i> {{ __('Publish') }}</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete confirmation — an in-page HTML modal, never the browser's confirm() dialog --}}
    @if($confirmingDeleteId !== null)
    <div class="modal-backdrop" wire:click.self="cancelDelete" x-data @keydown.escape.window="$wire.cancelDelete()">
        <div class="modal" style="max-width:400px">
            <div class="modal-title"><i class="fas fa-trash" style="color:var(--red)"></i> {{ __('Confirm deletion') }}</div>
            <p style="color:var(--text2);font-size:.9rem;margin:0 0 1.4rem">
                {{ __('Delete this recitation and all its assets?') }}
            </p>
            <div style="display:flex;gap:.6rem">
                <button type="button" wire:click="cancelDelete" class="btn btn-ghost" style="flex:1;justify-content:center">{{ __('Cancel') }}</button>
                <button type="button" wire:click="performDelete" class="btn btn-primary" style="flex:1;justify-content:center;background:var(--red);border-color:var(--red)">
                    <i class="fas fa-trash"></i> {{ __('Delete') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Branded-video preview — teleported to <body> so wire:poll never disturbs it --}}
    <template x-teleport="body">
        <div x-show="open" x-transition.opacity
             style="position:fixed;inset:0;z-index:1000;background:rgba(15,23,42,.8);display:flex;align-items:center;justify-content:center;padding:1.5rem"
             @click.self="close()" @keydown.escape.window="close()">
            <div style="position:relative;display:flex;flex-direction:column;align-items:center;gap:.75rem">
                <button type="button" @click="close()" title="{{ __('Close') }}"
                        style="position:absolute;top:-14px;inset-inline-end:-14px;width:38px;height:38px;border-radius:50%;background:#fff;border:0;cursor:pointer;color:#0f172a;box-shadow:0 4px 14px rgba(0,0,0,.35);font-size:1rem;z-index:2">
                    <i class="fas fa-xmark"></i>
                </button>
                <video x-ref="vid" :src="url" :poster="poster" controls autoplay playsinline
                       style="height:min(80vh,540px);width:auto;max-width:94vw;aspect-ratio:16/9;background:#000;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.5)"></video>
                <div style="color:#fff;font-size:.85rem;font-weight:600" x-text="title"></div>
            </div>
        </div>
    </template>

    <script>
    function brandPreview(){
        return {
            open:false, url:'', poster:'', title:'',
            show(url, poster, title){ this.url=url; this.poster=poster; this.title=title; this.open=true; },
            close(){ this.open=false; if(this.$refs.vid) this.$refs.vid.pause(); this.url=''; },
        };
    }
    </script>
</div>
