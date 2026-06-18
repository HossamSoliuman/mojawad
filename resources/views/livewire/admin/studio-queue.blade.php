<div @if($this->hasActive) wire:poll.5s @endif>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
        <h2 style="font-size:1.05rem;font-weight:700;margin:0">{{ __('Import Queue') }}</h2>
        @if($this->hasActive)
        <span class="badge badge-amber"><i class="fas fa-spinner fa-spin"></i> {{ __('Processing') }}</span>
        @endif
    </div>

    @if($this->sources->isEmpty())
        <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
            <i class="fab fa-youtube" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
            <div style="font-size:.95rem">{{ __('Nothing imported yet. Paste a YouTube link above to get started.') }}</div>
        </div>
    @else
        <div class="tbl-wrap">
            <table class="tbl">
                <thead><tr>
                    <th>{{ __('Source') }}</th>
                    <th>{{ __('Qari') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($this->sources as $source)
                    <tr wire:key="source-{{ $source->id }}">
                        <td>
                            <div style="display:flex;align-items:center;gap:.72rem">
                                @if($source->thumbnail_url)
                                <img src="{{ $source->thumbnail_url }}" style="width:48px;height:32px;border-radius:5px;object-fit:cover;flex-shrink:0" alt="">
                                @else
                                <span style="width:48px;height:32px;border-radius:5px;background:var(--bg3,#1e1e35);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--text3)"><i class="fab fa-youtube"></i></span>
                                @endif
                                <div style="min-width:0">
                                    <div style="font-weight:600;font-size:.86rem">{{ $source->source_title ?? __('Resolving…') }}</div>
                                    <a href="{{ $source->source_url }}" target="_blank" rel="noopener" style="font-size:.72rem;color:var(--text3);text-decoration:none">
                                        <i class="fas fa-link" style="font-size:.65rem"></i> {{ Str::limit($source->source_url, 48) }}
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--gold);font-size:.85rem">{{ $source->qari?->name ?? '—' }}</td>
                        <td>
                            @php($map = [
                                'pending'    => ['badge-muted', __('Pending'),    'fa-clock'],
                                'processing' => ['badge-amber', __('Processing'), 'fa-spinner fa-spin'],
                                'completed'  => ['badge-green', __('Imported'),   'fa-circle-check'],
                                'failed'     => ['badge-red',   __('Failed'),     'fa-circle-exclamation'],
                            ])
                            @php([$cls, $label, $icon] = $map[$source->status] ?? ['badge-muted', $source->status, 'fa-question'])
                            <span class="badge {{ $cls }}"><i class="fas {{ $icon }}"></i> {{ $label }}</span>
                            @if($source->status === 'failed' && $source->error)
                            <div style="font-size:.74rem;color:#e86060;margin-top:.35rem;max-width:240px" title="{{ $source->error }}">
                                {{ Str::limit($source->error, 70) }}
                            </div>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:.18rem;align-items:center">
                                @if($source->status === 'completed' && $source->tilawa)
                                <a href="{{ route('admin.tilawat.edit', $source->tilawa) }}" class="btn-icon" title="{{ __('Open tilawa') }}"><i class="fas fa-arrow-up-right-from-square"></i></a>
                                @endif
                                @if($source->status === 'failed')
                                <form method="POST" action="{{ route('admin.studio.retry', $source) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-xs" title="{{ __('Retry') }}"><i class="fas fa-rotate-right"></i> {{ __('Retry') }}</button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('admin.studio.destroy', $source) }}" onsubmit="return confirm(@json(__('Remove this source?')))" style="display:inline">
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
