<div @if($this->hasActive) wire:poll.5s @endif>

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
</div>
