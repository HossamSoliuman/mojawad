<div @if($this->hasActive) wire:poll.5s @endif>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
        <h2 style="font-size:1.05rem;font-weight:700;margin:0">{{ __('Ingest Queue') }}</h2>
        @if($this->hasActive)
        <span class="badge badge-amber"><i class="fas fa-spinner fa-spin"></i> {{ __('Processing') }}</span>
        @endif
    </div>

    @if($this->sources->isEmpty())
        <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
            <i class="fas fa-industry" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
            <div style="font-size:.95rem">{{ __('Nothing ingested yet. Upload a source above to get started.') }}</div>
        </div>
    @else
        <div class="tbl-wrap">
            <table class="tbl">
                <thead><tr>
                    <th>{{ __('Recitation') }}</th>
                    <th>{{ __('Qari') }}</th>
                    <th>{{ __('Ayah range') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($this->sources as $source)
                    @php($tilawa = $source->tilawa)
                    <tr wire:key="src-{{ $source->id }}">
                        <td>
                            <div style="display:flex;align-items:center;gap:.72rem">
                                <span style="width:44px;height:44px;border-radius:8px;background:var(--bg3,#1e1e35);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--text3)"><i class="fas fa-book-quran"></i></span>
                                <div style="min-width:0">
                                    <div style="font-weight:600;font-size:.86rem">{{ $tilawa?->title_ar ?? __('Cleaning audio…') }}</div>
                                    <div style="font-size:.72rem;color:var(--text3)">{{ $source->surah_number ? config('surahs.'.$source->surah_number) : '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--gold);font-size:.85rem">{{ $source->qari?->name ?? '—' }}</td>
                        <td>
                            @if($tilawa)
                            <div style="display:flex;align-items:center;gap:.35rem">
                                <input type="number" min="1" wire:model="edits.{{ $tilawa->id }}.from"
                                       class="form-control" style="width:74px;padding:.3rem .45rem;font-size:.8rem" placeholder="{{ __('From ayah') }}">
                                <span style="color:var(--text3)">–</span>
                                <input type="number" min="1" wire:model="edits.{{ $tilawa->id }}.to"
                                       class="form-control" style="width:74px;padding:.3rem .45rem;font-size:.8rem" placeholder="{{ __('To ayah') }}">
                            </div>
                            @error('to')<span class="form-error" style="font-size:.72rem">{{ $message }}</span>@enderror
                            @else
                            <span style="color:var(--text3)">—</span>
                            @endif
                        </td>
                        <td>
                            @php($map = [
                                'pending'    => ['badge-muted', __('Pending'),    'fa-clock'],
                                'processing' => ['badge-amber', __('Processing'), 'fa-spinner fa-spin'],
                                'completed'  => ['badge-green', __('Ingested'),   'fa-circle-check'],
                                'failed'     => ['badge-red',   __('Failed'),     'fa-circle-exclamation'],
                            ])
                            @php([$cls, $label, $icon] = $map[$source->status] ?? ['badge-muted', $source->status, 'fa-question'])
                            <span class="badge {{ $cls }}"><i class="fas {{ $icon }}"></i> {{ $label }}</span>

                            @if($tilawa)
                                @if($tilawa->ayah_confidence === 'high')
                                <span class="badge badge-green" style="margin-top:.3rem"><i class="fas fa-robot"></i> {{ __('Detected') }}</span>
                                @elseif($tilawa->ayah_confidence === 'low')
                                <div style="font-size:.74rem;color:#e0a852;margin-top:.35rem"><i class="fas fa-triangle-exclamation"></i> {{ __('Low confidence — please review') }}</div>
                                @elseif($tilawa->ayah_confidence === null && $source->status === 'completed' && $asrEnabled && $tilawa->ayah_from === null)
                                <div style="font-size:.74rem;color:var(--text2);margin-top:.35rem"><i class="fas fa-spinner fa-spin"></i> {{ __('Detecting ayat') }}</div>
                                @endif
                            @endif

                            @if($source->status === 'failed' && $source->error)
                            <div style="font-size:.74rem;color:#e86060;margin-top:.35rem;max-width:240px" title="{{ $source->error }}">
                                {{ Str::limit($source->error, 70) }}
                            </div>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:.2rem;align-items:center;flex-wrap:wrap">
                                @if($tilawa)
                                <button type="button" wire:click="confirm({{ $tilawa->id }})" class="btn btn-primary btn-xs" title="{{ __('Confirm ayah range') }}">
                                    <i class="fas fa-check"></i> {{ __('Confirm') }}
                                </button>
                                @if($asrEnabled)
                                <button type="button" wire:click="redetect({{ $tilawa->id }})" class="btn-icon" title="{{ __('Detect again') }}"><i class="fas fa-robot"></i></button>
                                @endif
                                <a href="{{ route('admin.tilawat.edit', $tilawa) }}" class="btn-icon" title="{{ __('Open tilawa') }}"><i class="fas fa-arrow-up-right-from-square"></i></a>
                                @endif
                                <button type="button" wire:click="deleteSource({{ $source->id }})"
                                        wire:confirm="{{ __('Remove this source and its recitation?') }}"
                                        class="btn-icon" style="color:var(--red)" title="{{ __('Remove') }}"><i class="fas fa-trash"></i></button>
                            </div>

                            @if($tilawa)
                            <div wire:ignore x-data="{ open: false }" style="margin-top:.45rem">
                                <button type="button" @click="open = ! open" class="btn btn-ghost btn-xs" title="{{ __('Listen') }}">
                                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-headphones'"></i> {{ __('Listen') }}
                                </button>
                                <div x-show="open" x-cloak style="margin-top:.45rem">
                                    <audio controls preload="none" src="{{ $tilawa->audio_url }}"
                                           style="width:100%;max-width:260px;height:34px"></audio>
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
</div>
