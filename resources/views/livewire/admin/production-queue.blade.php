<div x-data="brandPreview()" @if($this->hasActive) wire:poll.5s @endif>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;gap:1rem;flex-wrap:wrap">
        <h2 style="font-size:1.05rem;font-weight:700;margin:0">{{ __('Release Queue') }}</h2>
        <div style="display:flex;gap:.5rem;align-items:center">
            @if(count($selected) > 0)
            <button type="button" wire:click="bulkPrepare" class="btn btn-primary btn-sm">
                <i class="fas fa-gears"></i> {{ __('Prepare selected') }} ({{ count($selected) }})
            </button>
            @endif
            @if($this->hasActive)
            <span class="badge badge-amber"><i class="fas fa-spinner fa-spin"></i> {{ __('Working…') }}</span>
            @endif
        </div>
    </div>

    @if($this->tilawat->isEmpty())
        <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
            <i class="fas fa-tower-broadcast" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
            <div style="font-size:.95rem">{{ __('Nothing ready to publish yet. Ingest a source in the Factory first.') }}</div>
        </div>
    @else
        <div class="tbl-wrap">
            <table class="tbl">
                <thead><tr>
                    <th style="width:32px"></th>
                    <th>{{ __('Recitation') }}</th>
                    <th>{{ __('Brand assets') }}</th>
                    <th>{{ __('Platforms') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($this->tilawat as $tilawa)
                    <tr wire:key="prod-{{ $tilawa->id }}">
                        <td><input type="checkbox" value="{{ $tilawa->id }}" wire:model.live="selected"></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:.72rem">
                                @if($tilawa->brand_cover_url)
                                <img src="{{ $tilawa->brand_cover_url }}" style="width:44px;height:44px;border-radius:8px;object-fit:cover;flex-shrink:0" alt="">
                                @else
                                <span style="width:44px;height:44px;border-radius:8px;background:var(--bg3,#1e1e35);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--text3)"><i class="fas fa-book-quran"></i></span>
                                @endif
                                <div style="min-width:0">
                                    <div style="font-weight:600;font-size:.86rem">{{ $tilawa->title_ar }}</div>
                                    <div style="font-size:.72rem;color:var(--text3)">{{ $tilawa->qari?->name }} · {{ $tilawa->formatted_duration }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php($bmap = [
                                'none'       => ['badge-muted', __('Not prepared'), 'fa-circle-dashed'],
                                'processing' => ['badge-amber', __('Preparing…'),   'fa-spinner fa-spin'],
                                'ready'      => ['badge-green', __('Ready'),        'fa-circle-check'],
                                'failed'     => ['badge-red',   __('Failed'),       'fa-circle-exclamation'],
                            ])
                            @php([$bcls, $blabel, $bicon] = $bmap[$tilawa->brand_status] ?? ['badge-muted', $tilawa->brand_status, 'fa-question'])
                            <span class="badge {{ $bcls }}"><i class="fas {{ $bicon }}"></i> {{ $blabel }}</span>
                            @if($tilawa->brand_status === 'failed' && $tilawa->brand_error)
                            <div style="font-size:.74rem;color:#e86060;margin-top:.35rem;max-width:220px" title="{{ $tilawa->brand_error }}">{{ Str::limit($tilawa->brand_error, 70) }}</div>
                            @endif
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
                                    <div style="display:flex;align-items:center;gap:.3rem">
                                        <span style="font-size:.72rem;color:var(--text3);width:112px">{{ $plabel }}</span>
                                        @if($pub->status === 'completed' && $pub->external_url)
                                        <a href="{{ $pub->external_url }}" target="_blank" rel="noopener" class="badge {{ $pcls }}" style="text-decoration:none"><i class="fas {{ $pic }}"></i> {{ $plbl }}</a>
                                        @else
                                        <span class="badge {{ $pcls }}"><i class="fas {{ $pic }}"></i> {{ $plbl }}</span>
                                        @endif
                                        @if($pub->status === 'failed')
                                        <button type="button" wire:click="retryPublication({{ $pub->id }})" class="btn-icon" title="{{ __('Retry') }}"><i class="fas fa-rotate-right"></i></button>
                                        @endif
                                    </div>
                                    @endif
                                @endforeach
                                @if($pubs->isEmpty())
                                <span style="font-size:.74rem;color:var(--text3)">—</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;gap:.2rem;align-items:center;flex-wrap:wrap">
                                @if(in_array($tilawa->brand_status, ['none', 'failed'], true))
                                <button type="button" wire:click="prepare({{ $tilawa->id }})" class="btn btn-primary btn-xs">
                                    <i class="fas fa-gears"></i> {{ $tilawa->brand_status === 'failed' ? __('Retry') : __('Prepare') }}
                                </button>
                                @elseif($tilawa->brand_status === 'ready')
                                <button type="button" class="btn btn-ghost btn-xs"
                                        @click="show(@js($tilawa->brand_video_url), @js($tilawa->brand_cover_url), @js($tilawa->title_ar))">
                                    <i class="fas fa-eye"></i> {{ __('Preview') }}
                                </button>
                                <button type="button" wire:click="openPublish({{ $tilawa->id }})" class="btn btn-primary btn-xs">
                                    <i class="fas fa-tower-broadcast"></i> {{ __('Publish') }}
                                </button>
                                @endif
                                <button type="button" wire:click="delete({{ $tilawa->id }})"
                                        wire:confirm="{{ __('Delete this recitation and all its assets?') }}"
                                        class="btn-icon" style="color:var(--red)" title="{{ __('Remove') }}"><i class="fas fa-trash"></i></button>
                            </div>

                            @if($publishFor === $tilawa->id)
                            <div style="margin-top:.6rem;padding:.7rem;border:1px solid var(--border);border-radius:10px;background:var(--bg)">
                                <div style="font-size:.78rem;font-weight:600;margin-bottom:.5rem">{{ __('Select platforms') }}</div>
                                <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;margin-bottom:.35rem">
                                    <input type="checkbox" value="podcast" wire:model="platforms"> {{ __('Podcast (Spotify / Anghami)') }}
                                </label>
                                <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;margin-bottom:.35rem">
                                    <input type="checkbox" value="youtube" wire:model="platforms"> {{ __('YouTube') }}
                                    @unless($youtubeEnabled)<span style="font-size:.7rem;color:var(--text3)">({{ __('Not configured') }})</span>@endunless
                                </label>
                                <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;margin-bottom:.6rem">
                                    <input type="checkbox" value="facebook" wire:model="platforms"> {{ __('Facebook') }}
                                    @unless($facebookEnabled)<span style="font-size:.7rem;color:var(--text3)">({{ __('Not configured') }})</span>@endunless
                                </label>
                                <div style="display:flex;gap:.3rem">
                                    <button type="button" wire:click="doPublish" class="btn btn-primary btn-xs"><i class="fas fa-check"></i> {{ __('Publish') }}</button>
                                    <button type="button" wire:click="cancelPublish" class="btn btn-ghost btn-xs">{{ __('Cancel') }}</button>
                                </div>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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
