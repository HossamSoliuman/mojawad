<div x-data="brandPreview()" @if($this->hasActive) wire:poll.5s @endif>

    {{-- Pipeline tabs: Selection → Preparation → Publishing → Published --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;gap:1rem;flex-wrap:wrap">
        <div class="range-pills" style="display:inline-flex;gap:.15rem;flex-wrap:wrap">
            @foreach([
                'selection'   => ['fa-hand-pointer',     __('Selection'),   $this->selectionCount],
                'preparation' => ['fa-wand-magic-sparkles', __('Preparation'), $this->preparationCount],
                'publishing'  => ['fa-tower-broadcast',  __('Publishing'),  $this->publishingCount],
                'published'   => ['fa-circle-check',      __('Published'),   $this->publishedCount],
            ] as $key => $meta)
            <button type="button" wire:click="$set('tab', '{{ $key }}')"
                    class="range-pill {{ $tab === $key ? 'active' : '' }}"
                    style="border:0;cursor:pointer;padding:.45rem 1rem;{{ $tab === $key ? '' : 'background:transparent' }}">
                <i class="fas {{ $meta[0] }}"></i> {{ $meta[1] }} ({{ $meta[2] }})
            </button>
            @endforeach
        </div>
        @if($this->hasActive)
        <span class="badge badge-amber"><i class="fas fa-spinner fa-spin"></i> {{ __('Working…') }}</span>
        @endif
    </div>

    @if($tab === 'selection')
        {{-- ══════════ SELECTION: browse qaris, pick recitations into the pipeline ══════════ --}}
        <p style="color:var(--text2);font-size:.84rem;margin:0 0 1rem">{{ __('Pick a qari to see their recitations, then add the ones you want to produce. Adding a recitation moves it to Preparation.') }}</p>

        @if($this->selectionQaris->isEmpty())
            <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
                <i class="fas fa-hand-pointer" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
                <div style="font-size:.95rem">{{ __('Every recitation is already in the pipeline. Nothing left to select.') }}</div>
            </div>
        @else
            <div style="display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                {{-- Qari list --}}
                <div style="flex:0 0 280px;max-width:100%;display:flex;flex-direction:column;gap:.35rem">
                    @foreach($this->selectionQaris as $qari)
                    <button type="button" wire:key="sel-q-{{ $qari->id }}" wire:click="selectQari({{ $qari->id }})"
                            style="display:flex;align-items:center;gap:.7rem;text-align:start;border:1px solid var(--border);border-radius:10px;padding:.55rem .7rem;cursor:pointer;background:{{ $selectedQariId === $qari->id ? 'var(--bg3,#1e1e35)' : 'transparent' }}">
                        <span style="width:40px;height:40px;border-radius:8px;overflow:hidden;flex-shrink:0;background:var(--bg3,#1e1e35);display:flex;align-items:center;justify-content:center;color:var(--text3)">
                            <img src="{{ $qari->image_url }}" style="width:100%;height:100%;object-fit:cover" alt="">
                        </span>
                        <span style="min-width:0;flex:1">
                            <span style="display:block;font-weight:600;font-size:.86rem">{{ $qari->name }}</span>
                            <span style="display:block;font-size:.72rem;color:var(--text3)">{{ $qari->selectable_count }} {{ __('recitations') }}</span>
                        </span>
                        <i class="fas fa-chevron-{{ $selectedQariId === $qari->id ? 'down' : 'left' }}" style="font-size:.7rem;color:var(--text3)"></i>
                    </button>
                    @endforeach
                </div>

                {{-- Recitations of the opened qari --}}
                <div style="flex:1;min-width:300px">
                    @if($selectedQariId === null)
                        <div style="text-align:center;padding:3rem 1rem;color:var(--text3);border:1px dashed var(--border);border-radius:12px">
                            <i class="fas fa-arrow-{{ app()->getLocale() === 'en' ? 'left' : 'right' }}" style="opacity:.5;margin-inline-end:.4rem"></i>
                            {{ __('Choose a qari to list their recitations.') }}
                        </div>
                    @elseif($this->selectionTilawat->isEmpty())
                        <div style="text-align:center;padding:3rem 1rem;color:var(--text3)">{{ __('No recitations left for this qari.') }}</div>
                    @else
                        <div class="tbl-wrap">
                            <table class="tbl">
                                <thead><tr>
                                    <th>{{ __('Recitation') }}</th>
                                    <th style="width:1%"></th>
                                </tr></thead>
                                <tbody>
                                    @foreach($this->selectionTilawat as $tilawa)
                                    <tr wire:key="sel-t-{{ $tilawa->id }}">
                                        <td>
                                            <div style="font-weight:600;font-size:.86rem">{{ $tilawa->title_ar }}</div>
                                            <div style="font-size:.72rem;color:var(--text3)">{{ $tilawa->surah_label }} · {{ $tilawa->formatted_duration }}</div>
                                        </td>
                                        <td>
                                            <button type="button" wire:click="addToProduction({{ $tilawa->id }})" class="btn btn-primary btn-xs" style="white-space:nowrap">
                                                <i class="fas fa-plus"></i> {{ __('Add to production') }}
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif

    @elseif($tab === 'preparation')
        {{-- ══════════ PREPARATION: design the card, render the animated video ══════════ --}}
        @if($this->preparationList->isEmpty())
            <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
                <i class="fas fa-wand-magic-sparkles" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
                <div style="font-size:.95rem">{{ __('Nothing here yet. Add recitations from the Selection tab.') }}</div>
            </div>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead><tr>
                        <th>{{ __('Recitation') }}</th>
                        <th>{{ __('Video card') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach($this->preparationList as $tilawa)
                        <tr wire:key="prep-{{ $tilawa->id }}">
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
                                    'ready'      => ['badge-green', __('Video ready'),   'fa-circle-check'],
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
                                    @if($tilawa->brand_status === 'failed')
                                    <button type="button" wire:click="retryRender({{ $tilawa->id }})"
                                            wire:loading.attr="disabled" wire:target="retryRender({{ $tilawa->id }})"
                                            class="btn btn-ghost btn-xs" style="color:var(--amber,#d9a441)">
                                        <i class="fas fa-rotate-right"></i> {{ __('Retry') }}
                                    </button>
                                    @else
                                    <button type="button" wire:click="prepare({{ $tilawa->id }})" class="btn btn-ghost btn-xs">
                                        <i class="fas fa-gears"></i> {{ __('Quick render') }}
                                    </button>
                                    @endif
                                    @endif
                                    @if($tilawa->brand_status === 'ready')
                                    <button type="button" wire:click="moveToPublishing({{ $tilawa->id }})" class="btn btn-ghost btn-xs" style="color:var(--gold,#c9a153)">
                                        <i class="fas fa-arrow-right"></i> {{ __('Move to publishing') }}
                                    </button>
                                    @endif
                                    <button type="button" wire:click="confirmRemove({{ $tilawa->id }})"
                                            class="btn-icon" style="color:var(--red)" title="{{ __('Remove from production') }}"><i class="fas fa-xmark"></i></button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    @elseif($tab === 'publishing')
        {{-- ══════════ PUBLISHING: compose per-platform meta, upload to platforms ══════════ --}}
        @if($this->publishingList->isEmpty())
            <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
                <i class="fas fa-tower-broadcast" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
                <div style="font-size:.95rem">{{ __('No videos here yet. Move a ready recitation from Preparation.') }}</div>
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
                        @foreach($this->publishingList as $tilawa)
                        <tr wire:key="pub-{{ $tilawa->id }}">
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
                                        <div style="font-size:.72rem;color:var(--text3)">{{ $tilawa->qari?->name }} · {{ $tilawa->formatted_duration }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>@include('livewire.admin.partials.publication-tracking', ['tilawa' => $tilawa])</td>
                            <td>
                                <div style="display:flex;gap:.2rem;align-items:center;flex-wrap:wrap">
                                    <button type="button" class="btn btn-ghost btn-xs"
                                            @click="show(@js($tilawa->brand_video_url), @js($tilawa->brand_card_image_url ?? $tilawa->brand_cover_url), @js($tilawa->title_ar))">
                                        <i class="fas fa-eye"></i> {{ __('Preview') }}
                                    </button>
                                    <button type="button" wire:click="openPublish({{ $tilawa->id }})" class="btn btn-primary btn-xs">
                                        <i class="fas fa-tower-broadcast"></i> {{ __('Compose & publish') }}
                                    </button>
                                    <button type="button" wire:click="moveToPreparation({{ $tilawa->id }})" class="btn-icon" title="{{ __('Back to preparation') }}">
                                        <i class="fas fa-arrow-{{ app()->getLocale() === 'en' ? 'left' : 'right' }}"></i>
                                    </button>
                                    <button type="button" wire:click="confirmRemove({{ $tilawa->id }})"
                                            class="btn-icon" style="color:var(--red)" title="{{ __('Remove from production') }}"><i class="fas fa-xmark"></i></button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    @else
        {{-- ══════════ PUBLISHED: the finished, live recitations ══════════ --}}
        @if($this->publishedList->isEmpty())
            <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
                <i class="fas fa-circle-check" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
                <div style="font-size:.95rem">{{ __('Nothing published yet.') }}</div>
            </div>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead><tr>
                        <th>{{ __('Recitation') }}</th>
                        <th>{{ __('Where it went live') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach($this->publishedList as $tilawa)
                        <tr wire:key="done-{{ $tilawa->id }}">
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
                            <td>@include('livewire.admin.partials.publication-tracking', ['tilawa' => $tilawa])</td>
                            <td>
                                <div style="display:flex;gap:.2rem;align-items:center;flex-wrap:wrap">
                                    @if($tilawa->brand_video_url)
                                    <button type="button" class="btn btn-ghost btn-xs"
                                            @click="show(@js($tilawa->brand_video_url), @js($tilawa->brand_card_image_url ?? $tilawa->brand_cover_url), @js($tilawa->title_ar))">
                                        <i class="fas fa-eye"></i> {{ __('Preview') }}
                                    </button>
                                    @endif
                                    <button type="button" wire:click="openPublish({{ $tilawa->id }})" class="btn btn-ghost btn-xs">
                                        <i class="fas fa-tower-broadcast"></i> {{ __('Publish elsewhere') }}
                                    </button>
                                    <button type="button" wire:click="confirmRemove({{ $tilawa->id }})"
                                            class="btn-icon" style="color:var(--red)" title="{{ __('Remove from production') }}"><i class="fas fa-xmark"></i></button>
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

    {{-- Publish composer: per-platform title + description, then upload --}}
    @if($publishFor !== null)
    <div class="modal-backdrop" wire:click.self="cancelPublish" x-data @keydown.escape.window="$wire.cancelPublish()">
        <div class="modal" style="max-width:560px;width:96vw;max-height:92vh;overflow:auto">
            <div class="modal-title" style="margin-bottom:.4rem"><i class="fas fa-tower-broadcast"></i> {{ __('Compose & publish') }}</div>
            <p style="color:var(--text3);font-size:.8rem;margin:0 0 1rem">{{ __('Pick the platforms and write the title & description that will go live on each one.') }}</p>

            <div style="display:flex;flex-direction:column;gap:.9rem">
                {{-- Podcast --}}
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer">
                    <input type="checkbox" value="podcast" wire:model.live="platforms"> {{ __('Podcast (Spotify / Anghami)') }}
                </label>

                {{-- YouTube --}}
                <div style="border:1px solid var(--border);border-radius:10px;padding:.7rem">
                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer;font-weight:600">
                        <input type="checkbox" value="youtube" wire:model.live="platforms"> {{ __('YouTube') }}
                        @unless($youtubeEnabled)<span style="font-size:.72rem;color:var(--text3);font-weight:400">({{ __('Not configured') }})</span>@endunless
                    </label>
                    @if(in_array('youtube', $platforms, true))
                    <div style="display:flex;flex-direction:column;gap:.5rem;margin-top:.6rem">
                        <input type="text" wire:model="ytTitle" class="form-control" style="width:100%" placeholder="{{ __('Title') }}">
                        <textarea wire:model="ytDescription" rows="3" class="form-control" style="width:100%;resize:vertical" placeholder="{{ __('Description') }}"></textarea>
                    </div>
                    @endif
                </div>

                {{-- Facebook --}}
                <div style="border:1px solid var(--border);border-radius:10px;padding:.7rem">
                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer;font-weight:600">
                        <input type="checkbox" value="facebook" wire:model.live="platforms"> {{ __('Facebook') }}
                        @unless($facebookEnabled)<span style="font-size:.72rem;color:var(--text3);font-weight:400">({{ __('Not configured') }})</span>@endunless
                    </label>
                    @if(in_array('facebook', $platforms, true))
                    <div style="display:flex;flex-direction:column;gap:.5rem;margin-top:.6rem">
                        <input type="text" wire:model="fbTitle" class="form-control" style="width:100%" placeholder="{{ __('Title') }}">
                        <textarea wire:model="fbDescription" rows="3" class="form-control" style="width:100%;resize:vertical" placeholder="{{ __('Description') }}"></textarea>
                    </div>
                    @endif
                </div>
            </div>

            <div style="display:flex;gap:.6rem;margin-top:1.2rem">
                <button type="button" wire:click="cancelPublish" class="btn btn-ghost" style="flex:1;justify-content:center">{{ __('Cancel') }}</button>
                <button type="button" wire:click="doPublish" wire:loading.attr="disabled" class="btn btn-primary" style="flex:1;justify-content:center"><i class="fas fa-check"></i> {{ __('Publish') }}</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Remove-from-pipeline confirmation — an in-page modal, never the browser's confirm() --}}
    @if($confirmingRemoveId !== null)
    <div class="modal-backdrop" wire:click.self="cancelRemove" x-data @keydown.escape.window="$wire.cancelRemove()">
        <div class="modal" style="max-width:420px">
            <div class="modal-title"><i class="fas fa-xmark" style="color:var(--red)"></i> {{ __('Remove from production') }}</div>
            <p style="color:var(--text2);font-size:.9rem;margin:0 0 1.4rem">
                {{ __('Drop this recitation out of the production pipeline? It stays on the site and its assets are kept — you can add it again from Selection.') }}
            </p>
            <div style="display:flex;gap:.6rem">
                <button type="button" wire:click="cancelRemove" class="btn btn-ghost" style="flex:1;justify-content:center">{{ __('Cancel') }}</button>
                <button type="button" wire:click="performRemove" class="btn btn-primary" style="flex:1;justify-content:center;background:var(--red);border-color:var(--red)">
                    <i class="fas fa-xmark"></i> {{ __('Remove') }}
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
