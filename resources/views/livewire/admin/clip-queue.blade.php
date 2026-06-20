<div x-data="clipPreview()" @if($this->hasActive) wire:poll.5s @endif>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
        <h2 style="font-size:1.05rem;font-weight:700;margin:0">{{ __('Rendered Clips') }}</h2>
        @if($this->hasActive)
        <span class="badge badge-amber"><i class="fas fa-spinner fa-spin"></i> {{ __('Rendering…') }}</span>
        @endif
    </div>

    @if($this->clips->isEmpty())
        <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
            <i class="fas fa-film" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
            <div style="font-size:.95rem">{{ __('No clips yet. Build one above to get started.') }}</div>
        </div>
    @else
        <div class="tbl-wrap">
            <table class="tbl">
                <thead><tr>
                    <th>{{ __('Clip') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('TikTok') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($this->clips as $clip)
                    <tr wire:key="clip-{{ $clip->id }}">
                        <td>
                            <div style="display:flex;align-items:center;gap:.72rem">
                                @if($clip->poster_url)
                                <img src="{{ $clip->poster_url }}" style="width:32px;height:56px;border-radius:5px;object-fit:cover;flex-shrink:0" alt="">
                                @else
                                <span style="width:32px;height:56px;border-radius:5px;background:var(--bg3,#1e1e35);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--text3)"><i class="fas fa-film"></i></span>
                                @endif
                                <div style="min-width:0">
                                    <div style="font-weight:600;font-size:.86rem">{{ $clip->tilawa?->title ?? __('Deleted tilawa') }}</div>
                                    <div style="font-size:.72rem;color:var(--text3)">
                                        {{ $clip->tilawa?->qari?->name }} · {{ $clip->clip_duration }}{{ __('s') }} · {{ $clip->template }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php($map = [
                                'pending'    => ['badge-muted', __('Pending'),    'fa-clock'],
                                'processing' => ['badge-amber', __('Rendering…'), 'fa-spinner fa-spin'],
                                'completed'  => ['badge-green', __('Ready'),      'fa-circle-check'],
                                'failed'     => ['badge-red',   __('Failed'),     'fa-circle-exclamation'],
                            ])
                            @php([$cls, $label, $icon] = $map[$clip->status] ?? ['badge-muted', $clip->status, 'fa-question'])
                            <span class="badge {{ $cls }}"><i class="fas {{ $icon }}"></i> {{ $label }}</span>
                            @if($clip->status === 'failed' && $clip->error)
                            <div style="font-size:.74rem;color:#e86060;margin-top:.35rem;max-width:240px" title="{{ $clip->error }}">
                                {{ Str::limit($clip->error, 70) }}
                            </div>
                            @endif
                        </td>
                        <td>
                            @if($clip->status === 'completed')
                            <form method="POST" action="{{ route('admin.clips.tiktok', $clip) }}" style="display:flex;gap:.3rem;align-items:center">
                                @csrf @method('PATCH')
                                <input type="url" name="tiktok_url" value="{{ $clip->tiktok_url }}" placeholder="{{ __('Paste TikTok link') }}" dir="ltr"
                                       class="form-control" style="font-size:.74rem;padding:.3rem .5rem;max-width:170px">
                                <button type="submit" class="btn-icon" title="{{ __('Save') }}"><i class="fas fa-check"></i></button>
                            </form>
                            @else
                            <span style="color:var(--text3)">—</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:.18rem;align-items:center">
                                @if($clip->status === 'completed')
                                <button type="button" class="btn btn-ghost btn-xs" title="{{ __('Preview') }}"
                                        data-url="{{ $clip->output_url }}"
                                        data-poster="{{ $clip->poster_url }}"
                                        data-title="{{ $clip->tilawa?->title ?? __('Short video') }}"
                                        data-dl="{{ route('admin.clips.download', $clip) }}"
                                        @click="show($el.dataset.url, $el.dataset.poster, $el.dataset.title, $el.dataset.dl)">
                                    <i class="fas fa-eye"></i> {{ __('Preview') }}
                                </button>
                                <a href="{{ route('admin.clips.download', $clip) }}" class="btn btn-primary btn-xs" title="{{ __('Download video') }}"><i class="fas fa-download"></i> {{ __('Download') }}</a>
                                @endif
                                @if($clip->status === 'failed')
                                <form method="POST" action="{{ route('admin.clips.retry', $clip) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-xs" title="{{ __('Retry') }}"><i class="fas fa-rotate-right"></i> {{ __('Retry') }}</button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('admin.clips.destroy', $clip) }}" onsubmit="return confirm(@json(__('Remove this clip?')))" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon" style="color:var(--red)" title="{{ __('Remove') }}"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Rendered-clip player — teleported to <body> so wire:poll never disturbs it --}}
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
                       style="height:min(82vh,720px);width:auto;max-width:94vw;aspect-ratio:9/16;background:#000;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.5)"></video>
                <div style="display:flex;align-items:center;gap:1rem;color:#fff;font-size:.85rem">
                    <span x-text="title" style="font-weight:600"></span>
                    <a :href="download" class="btn btn-primary btn-xs"><i class="fas fa-download"></i> {{ __('Download') }}</a>
                </div>
            </div>
        </div>
    </template>
</div>
