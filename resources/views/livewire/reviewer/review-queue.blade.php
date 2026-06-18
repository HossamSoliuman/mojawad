<div x-data="{ toast: '', show: false }"
     @review-done.window="toast = $event.detail.message; show = true; setTimeout(() => show = false, 3500)">

    {{-- Toast --}}
    <div x-show="show" x-transition class="alert alert-success review-toast">
        <i class="fas fa-check-circle"></i> <span x-text="toast"></span>
    </div>

    @if($this->tilawat->isEmpty())
        <div style="text-align:center;padding:4rem 1rem;color:var(--text2)">
            <i class="fas fa-clipboard-check" style="font-size:2.4rem;display:block;margin-bottom:.8rem;color:var(--green)"></i>
            <div style="font-size:1.05rem;font-weight:600">{{ __('All caught up') }}</div>
            <div style="font-size:.86rem;margin-top:.3rem">{{ __('There are no tilawat waiting for review.') }}</div>
        </div>
    @else
        @php($active = $this->activeTilawa)

        {{-- Active card — full detail, the one being worked on --}}
        @if($active)
            <div class="review-focus" wire:key="focus-{{ $active->id }}">
                <div class="review-focus-main">
                    <div class="review-card-head">
                        <img class="rc-cover" src="{{ $active->cover_url }}" alt="">
                        <div style="min-width:0;flex:1">
                            <div class="review-focus-title" dir="rtl" title="{{ $active->title_ar }}">{{ $active->title_ar }}</div>
                            @if($active->title_en)
                                <div style="font-size:.82rem;color:var(--text2)">{{ $active->title_en }}</div>
                            @endif
                            <div class="review-meta-row" style="margin-top:.4rem">
                                <img src="{{ $active->qari->image_url }}" style="width:22px;height:22px;border-radius:50%;object-fit:cover" alt="">
                                <span style="color:var(--gold)">{{ $active->qari->name }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Meta --}}
                    <div style="display:flex;flex-wrap:wrap;gap:.5rem .95rem">
                        <span class="review-meta-row"><i class="fas fa-user"></i> {{ $active->uploader?->name ?? '—' }}</span>
                        <span class="review-meta-row" style="font-family:monospace"><i class="fas fa-clock"></i> {{ $active->formatted_duration }}</span>
                        @if($active->recorded_place)
                            <span class="review-meta-row"><i class="fas fa-location-dot"></i> {{ $active->recorded_place }}</span>
                        @endif
                        @if($active->recorded_at)
                            <span class="review-meta-row"><i class="fas fa-calendar"></i> {{ $active->recorded_at->format('d M Y') }}</span>
                        @endif
                    </div>

                    {{-- Audio preview --}}
                    <button type="button" class="btn btn-ghost btn-sm" style="align-self:flex-start"
                        onclick="playTilawa({{ $active->id }},'{{ $active->audio_url }}',{{ Illuminate\Support\Js::from($active->title_ar) }},{{ Illuminate\Support\Js::from($active->qari->name) }},'{{ $active->cover_url }}',{{ $active->duration }})">
                        <i class="fas fa-play"></i> {{ __('Listen') }}
                    </button>

                    {{-- History --}}
                    @if($active->reviews->isNotEmpty())
                        <button type="button" class="btn-icon" style="align-self:flex-start;font-size:.8rem;color:var(--text2);width:auto;gap:.35rem;display:flex;align-items:center"
                                wire:click="toggleHistory({{ $active->id }})">
                            <i class="fas fa-clock-rotate-left"></i>
                            {{ $historyId === $active->id ? __('Hide history') : __('Submission history') }} ({{ $active->reviews->count() }})
                        </button>
                        @if($historyId === $active->id)
                            <div class="review-history-panel">
                                @foreach($active->reviews as $r)
                                    <div class="rh-item">
                                        <span class="badge {{ ['submitted'=>'badge-muted','resubmitted'=>'badge-blue','approved'=>'badge-green','rejected'=>'badge-red'][$r->action] ?? 'badge-muted' }}">{{ __($r->action) }}</span>
                                        <div style="flex:1">
                                            <div style="color:var(--text3);font-size:.72rem">{{ $r->created_at->format('d M Y · H:i') }}{{ $r->reviewer ? ' · '.$r->reviewer->name : '' }}</div>
                                            @if($r->note)<div style="color:var(--text2);margin-top:.15rem">{{ $r->note }}</div>@endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>

                <div class="review-focus-side">
                    {{-- Verify checklist (reviewer guidance) --}}
                    <div class="review-checklist">
                        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text3)">{{ __('Verify before approving') }}</div>
                        <label><input type="checkbox"> {{ __('Title is correct') }}</label>
                        <label><input type="checkbox"> {{ __('Audio is clear & complete') }}</label>
                        <label><input type="checkbox"> {{ __('Qari is correct') }}</label>
                    </div>

                    {{-- Reject note form --}}
                    @if($rejectingId === $active->id)
                        <div style="display:flex;flex-direction:column;gap:.5rem">
                            <textarea wire:model="rejectionNote" rows="3" class="form-control"
                                placeholder="{{ __('Explain what the creator needs to fix…') }}"></textarea>
                            @error('rejectionNote')<span class="form-error">{{ $message }}</span>@enderror
                            <div style="display:flex;gap:.5rem">
                                <button class="btn btn-sm" style="background:var(--red);color:#fff" wire:click="reject({{ $active->id }})">
                                    <i class="fas fa-paper-plane"></i> {{ __('Send rejection') }}
                                </button>
                                <button class="btn btn-ghost btn-sm" wire:click="cancelReject">{{ __('Cancel') }}</button>
                            </div>
                        </div>
                    @else
                        <div class="review-actions">
                            <button class="btn btn-primary btn-sm" style="flex:1" wire:click="approve({{ $active->id }})"
                                    wire:loading.attr="disabled" wire:target="approve({{ $active->id }})">
                                <i class="fas fa-check"></i> {{ __('Approve') }}
                            </button>
                            <button class="btn btn-ghost btn-sm" style="flex:1;color:#e86060" wire:click="startReject({{ $active->id }})">
                                <i class="fas fa-xmark"></i> {{ __('Reject') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Queue — compact cards grouped into rows; click one to load it into the focus area --}}
        <div class="review-strip-head">
            <span><i class="fas fa-layer-group"></i> {{ __('In queue') }} ({{ $this->tilawat->count() }})</span>
            <div class="review-group-toggle">
                <button type="button" wire:click="setGroupBy('qari')" class="{{ $groupBy === 'qari' ? 'is-active' : '' }}">
                    <i class="fas fa-microphone"></i> {{ __('By reciter') }}
                </button>
                <button type="button" wire:click="setGroupBy('creator')" class="{{ $groupBy === 'creator' ? 'is-active' : '' }}">
                    <i class="fas fa-user-pen"></i> {{ __('By creator') }}
                </button>
            </div>
        </div>

        @foreach($this->groupedTilawat as $groupName => $items)
            <div class="review-group" wire:key="grp-{{ $groupBy }}-{{ $loop->index }}">
                <div class="review-group-label">
                    <i class="fas {{ $groupBy === 'creator' ? 'fa-user-pen' : 'fa-microphone' }}"></i>
                    <span>{{ $groupName }}</span>
                    <span class="review-group-count">{{ $items->count() }}</span>
                </div>
                <div class="review-strip">
                    @foreach($items as $t)
                        <button type="button" wire:key="chip-{{ $t->id }}"
                                wire:click="focus({{ $t->id }})"
                                class="review-chip {{ $active && $active->id === $t->id ? 'is-active' : '' }}">
                            <img src="{{ $t->cover_url }}" alt="">
                            <div class="review-chip-body">
                                <div class="review-chip-title" dir="rtl">{{ $t->title_ar }}</div>
                                <div class="review-chip-qari">{{ $groupBy === 'creator' ? $t->qari->name : ($t->uploader?->name ?? '—') }}</div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
