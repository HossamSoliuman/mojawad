<div @if($this->hasActive) wire:poll.5s @endif>

    {{-- Tab switcher: items in flight vs. items waiting for a human listen --}}
    <div class="fq-tabs">
        <button type="button" wire:click="switchTab('processing')"
                class="fq-tab @if($tab === 'processing') is-active @endif">
            <i class="fas fa-gears"></i> {{ __('Processing') }}
            <span class="fq-count">{{ $this->processingCount }}</span>
        </button>
        <button type="button" wire:click="switchTab('review')"
                class="fq-tab @if($tab === 'review') is-active @endif">
            <i class="fas fa-headphones"></i> {{ __('Review') }}
            <span class="fq-count fq-count-green">{{ $this->reviewCount }}</span>
        </button>
    </div>

    @if(count($selected))
    <div class="fq-bulk">
        <span class="fq-bulk-count">{{ __(':count selected', ['count' => count($selected)]) }}</span>
        <button type="button" wire:click="confirmBulkDelete"
                class="btn btn-sm fq-bulk-delete"><i class="fas fa-trash"></i> {{ __('Delete selected') }}</button>
    </div>
    @endif

    @if($tab === 'processing')
    {{-- ── PROCESSING ─────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
        <h2 style="font-size:1.05rem;font-weight:700;margin:0">{{ __('Ingest Queue') }}</h2>
        @if($this->hasActive)
        <span class="badge badge-amber"><i class="fas fa-spinner fa-spin"></i> {{ __('Processing') }}</span>
        @endif
    </div>

    @if($this->processingSources->isEmpty())
        <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
            <i class="fas fa-industry" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
            <div style="font-size:.95rem">{{ __('Nothing being processed. Import a folder above to get started.') }}</div>
        </div>
    @else
        <div class="tbl-wrap">
            <table class="tbl">
                <thead><tr>
                    <th style="width:34px"><input type="checkbox" class="fq-check" wire:model.live="selectAll" title="{{ __('Select all') }}"></th>
                    <th>{{ __('Recitation') }}</th>
                    <th>{{ __('Qari') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($this->processingSources as $source)
                    <tr wire:key="proc-{{ $source->id }}">
                        <td><input type="checkbox" class="fq-check" value="{{ $source->id }}" wire:model.live="selected"></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:.72rem">
                                <span style="width:44px;height:44px;border-radius:8px;background:var(--bg3,#1e1e35);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--text3)"><i class="fas fa-book-quran"></i></span>
                                <div style="min-width:0">
                                    <div style="font-weight:600;font-size:.86rem">{{ $source->tilawa?->title_ar ?? __('Cleaning audio…') }}</div>
                                    <div style="font-size:.72rem;color:var(--text3)">{{ $source->surah_label ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--gold);font-size:.85rem">{{ $source->qari?->name ?? '—' }}</td>
                        <td>
                            @php($map = [
                                'pending'    => ['badge-muted', __('Pending'),    'fa-clock'],
                                'processing' => ['badge-amber', __('Processing'), 'fa-spinner fa-spin'],
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
                            <div style="display:flex;align-items:center;gap:.35rem">
                                @if($source->status === 'failed')
                                <button type="button" wire:click="retry({{ $source->id }})"
                                        class="btn-icon" style="color:var(--gold)" title="{{ __('Retry') }}"
                                        wire:loading.attr="disabled" wire:target="retry({{ $source->id }})">
                                    <i class="fas fa-rotate-right" wire:loading.remove wire:target="retry({{ $source->id }})"></i>
                                    <i class="fas fa-circle-notch fa-spin" wire:loading wire:target="retry({{ $source->id }})"></i>
                                </button>
                                @endif
                                <button type="button" wire:click="confirmDelete({{ $source->id }})"
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
    {{-- ── REVIEW ─────────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;gap:1rem;flex-wrap:wrap">
        <div>
            <h2 style="font-size:1.05rem;font-weight:700;margin:0">{{ __('Review & approve') }}</h2>
            <p style="color:var(--text2);font-size:.84rem;margin:.2rem 0 0">{{ __('Listen through each recitation, optionally set the ayah range, then approve to publish it live.') }}</p>
        </div>
        @if($this->reviewSources->isNotEmpty())
        <label class="fq-select-all"><input type="checkbox" class="fq-check" wire:model.live="selectAll"> {{ __('Select all') }}</label>
        @endif
    </div>

    @if($this->reviewSources->isEmpty())
        <div style="text-align:center;padding:3rem 1rem;color:var(--text2)">
            <i class="fas fa-headphones" style="font-size:2.2rem;display:block;margin-bottom:.7rem;opacity:.4"></i>
            <div style="font-size:.95rem">{{ __('No recitations waiting for review. Cleaned files land here automatically.') }}</div>
        </div>
    @else
        <div class="fq-review-list">
            @foreach($this->reviewSources as $source)
            @php($tilawa = $source->tilawa)
            <div class="fq-review-card" wire:key="rev-{{ $source->id }}">

                <div class="fq-review-head">
                    <div style="display:flex;align-items:flex-start;gap:.7rem;min-width:0">
                        <input type="checkbox" class="fq-check" style="margin-top:.25rem" value="{{ $source->id }}" wire:model.live="selected">
                        <div style="min-width:0">
                        <div class="fq-review-title">{{ $tilawa->title_ar }}</div>
                        <div class="fq-review-sub">
                            <i class="fas fa-microphone-lines"></i> {{ $tilawa->qari?->name ?? '—' }}
                            <span style="opacity:.4">·</span> {{ $tilawa->surah_label ?? '—' }}
                            <span style="opacity:.4">·</span> {{ $tilawa->formatted_duration }}
                        </div>
                        </div>
                    </div>
                    @if($tilawa->isMultiSurah())
                    <span class="badge badge-muted"><i class="fas fa-layer-group"></i> {{ __('Multiple surahs') }}</span>
                    @elseif($tilawa->ayah_confidence === 'high')
                    <span class="badge badge-green"><i class="fas fa-robot"></i> {{ __('Detected') }}</span>
                    @elseif($tilawa->ayah_confidence === 'low')
                    <span class="badge badge-amber"><i class="fas fa-triangle-exclamation"></i> {{ __('Low confidence') }}</span>
                    @elseif($tilawa->ayah_confidence === null && $asrEnabled && $tilawa->ayah_from === null)
                    <span class="badge badge-muted"><i class="fas fa-spinner fa-spin"></i> {{ __('Detecting ayat') }}</span>
                    @endif
                </div>

                {{-- Fast listening & seeking --}}
                <audio controls preload="metadata" src="{{ $tilawa->audio_url }}" class="fq-audio"></audio>

                <div class="fq-review-foot">
                    @if($tilawa->isMultiSurah())
                    <div class="fq-range">
                        <span class="fq-range-label"><i class="fas fa-circle-info"></i> {{ __('Spans several surahs — no single ayah range applies.') }}</span>
                    </div>
                    @else
                    <div class="fq-range">
                        <span class="fq-range-label">{{ __('Ayah range') }}</span>
                        <input type="number" min="1" wire:model="edits.{{ $tilawa->id }}.from"
                               class="form-control fq-range-input" placeholder="{{ __('From ayah') }}">
                        <span style="color:var(--text3)">–</span>
                        <input type="number" min="1" wire:model="edits.{{ $tilawa->id }}.to"
                               class="form-control fq-range-input" placeholder="{{ __('To ayah') }}">
                        <button type="button" wire:click="confirm({{ $tilawa->id }})" class="btn btn-ghost btn-xs" title="{{ __('Save the ayah range and update the title') }}">
                            <i class="fas fa-arrows-left-right-to-line"></i> {{ __('Set range') }}
                        </button>
                        @error("edits.{$tilawa->id}.to")<span class="form-error" style="font-size:.72rem">{{ $message }}</span>@enderror
                    </div>
                    @endif

                    <div class="fq-review-actions">
                        @if($asrEnabled && ! $tilawa->isMultiSurah())
                        <button type="button" wire:click="redetect({{ $tilawa->id }})" class="btn-icon" title="{{ __('Detect again') }}"><i class="fas fa-robot"></i></button>
                        @endif
                        <a href="{{ route('admin.tilawat.edit', $tilawa) }}" class="btn-icon" title="{{ __('Open tilawa') }}"><i class="fas fa-arrow-up-right-from-square"></i></a>
                        <button type="button" wire:click="confirmDelete({{ $source->id }})"
                                class="btn-icon" style="color:var(--red)" title="{{ __('Remove') }}"><i class="fas fa-trash"></i></button>
                        <button type="button" wire:click="approve({{ $tilawa->id }})" class="btn fq-approve btn-sm">
                            <i class="fas fa-circle-check"></i> {{ __('Approve & publish') }}
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
    @endif

    {{-- Delete confirmation — an in-page HTML modal, never the browser's confirm() dialog --}}
    @if($confirmingBulkDelete || $confirmingSourceId !== null)
    <div class="modal-backdrop" wire:click.self="cancelDelete"
         x-data @keydown.escape.window="$wire.cancelDelete()">
        <div class="modal" style="max-width:400px">
            <div class="modal-title"><i class="fas fa-trash" style="color:var(--red)"></i> {{ __('Confirm deletion') }}</div>
            <p style="color:var(--text2);font-size:.9rem;margin:0 0 1.4rem">
                @if($confirmingBulkDelete)
                    {{ __('Delete the selected sources and their recitations?') }}
                @else
                    {{ __('Remove this source and its recitation?') }}
                @endif
            </p>
            <div style="display:flex;gap:.6rem">
                <button type="button" wire:click="cancelDelete" class="btn btn-ghost" style="flex:1;justify-content:center">{{ __('Cancel') }}</button>
                <button type="button" wire:click="performDelete" class="btn fq-bulk-delete" style="flex:1;justify-content:center"
                        wire:loading.attr="disabled" wire:target="performDelete">
                    <i class="fas fa-trash" wire:loading.remove wire:target="performDelete"></i>
                    <i class="fas fa-circle-notch fa-spin" wire:loading wire:target="performDelete"></i>
                    {{ __('Delete') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    <style>
        .fq-tabs{display:flex;gap:.4rem;margin-bottom:1.4rem;border-bottom:1px solid var(--border)}
        .fq-tab{display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1rem;border:0;background:none;cursor:pointer;font-family:inherit;font-size:.9rem;font-weight:600;color:var(--text2);border-bottom:2px solid transparent;margin-bottom:-1px;transition:.15s}
        .fq-tab:hover{color:var(--text1)}
        .fq-tab.is-active{color:var(--gold);border-bottom-color:var(--gold)}
        .fq-count{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 .35rem;border-radius:999px;background:var(--bg3,#1e1e35);color:var(--text2);font-size:.72rem;font-weight:700}
        .fq-count-green{background:rgba(34,197,94,.16);color:#22c55e}

        .fq-bulk{display:flex;align-items:center;gap:.9rem;margin-bottom:1.1rem;padding:.55rem .9rem;border-radius:10px;background:rgba(29,185,84,.1);border:1px solid rgba(29,185,84,.28)}
        .fq-bulk-count{font-size:.84rem;font-weight:600;color:var(--text1)}
        .fq-bulk-delete{background:var(--red);border-color:var(--red);color:#fff;font-weight:600}
        .fq-bulk-delete:hover{filter:brightness(.92)}
        .fq-select-all{display:inline-flex;align-items:center;gap:.45rem;font-size:.82rem;font-weight:600;color:var(--text2);cursor:pointer}
        .fq-check{width:16px;height:16px;accent-color:#1DB954;cursor:pointer}

        .fq-review-list{display:flex;flex-direction:column;gap:.9rem}
        .fq-review-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:1.1rem 1.2rem}
        .fq-review-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:.85rem}
        .fq-review-title{font-weight:700;font-size:.98rem;color:var(--text1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .fq-review-sub{font-size:.76rem;color:var(--text3);margin-top:.2rem}
        .fq-review-sub i{color:var(--gold)}

        .fq-audio{width:100%;height:40px;margin-bottom:.9rem}

        .fq-review-foot{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
        .fq-range{display:flex;align-items:center;gap:.4rem;flex-wrap:wrap}
        .fq-range-label{font-size:.76rem;color:var(--text2);font-weight:600}
        .fq-range-input{width:74px;padding:.3rem .45rem;font-size:.8rem}
        .fq-review-actions{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap}

        .fq-approve{background:#22c55e;border-color:#22c55e;color:#04210f;font-weight:700}
        .fq-approve:hover{background:#16a34a;border-color:#16a34a}
    </style>
</div>
