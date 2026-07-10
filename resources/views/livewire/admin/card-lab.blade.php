<div style="display:flex;gap:1.4rem;align-items:flex-start;flex-wrap:wrap">

    <div style="flex:0 0 320px;max-width:100%;display:flex;flex-direction:column;gap:.9rem">
        <div>
            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">{{ __('Pick a recitation') }}</label>
            <select wire:model.live="tilawaId" class="form-control" style="width:100%">
                <option value="">{{ __('— manual input —') }}</option>
                @foreach($this->tilawat as $tilawa)
                    <option value="{{ $tilawa->id }}" wire:key="lab-t-{{ $tilawa->id }}">{{ $tilawa->title_ar }} · {{ $tilawa->qari?->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">{{ __('Qari name') }}</label>
            <input type="text" wire:model.live.debounce.400ms="qariName" class="form-control" style="width:100%">
        </div>

        <div>
            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">{{ __('Surah name') }}</label>
            <input type="text" wire:model.live.debounce.400ms="surahName" class="form-control" style="width:100%">
        </div>

        <div>
            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">{{ __('Extra text (optional)') }}</label>
            <textarea wire:model.live.debounce.400ms="extraText" rows="3" class="form-control" style="width:100%;resize:vertical"></textarea>
        </div>

        <div style="display:flex;flex-direction:column;gap:.5rem">
            <button type="button" wire:click="renderCard" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                <span wire:loading.remove wire:target="renderCard"><i class="fas fa-image"></i> {{ __('Render card image') }}</span>
                <span wire:loading wire:target="renderCard"><i class="fas fa-spinner fa-spin"></i> {{ __('Rendering…') }}</span>
            </button>
            @if($cardPath && $this->tilawat->firstWhere('id', $tilawaId)?->audio_path)
            <button type="button" wire:click="renderSample" wire:loading.attr="disabled" class="btn btn-ghost btn-sm">
                <span wire:loading.remove wire:target="renderSample"><i class="fas fa-clapperboard"></i> {{ __('Render sample video (15s)') }}</span>
                <span wire:loading wire:target="renderSample"><i class="fas fa-spinner fa-spin"></i> {{ __('Rendering…') }}</span>
            </button>
            @endif
        </div>

        @if($error)
        <div style="padding:.7rem;border:1px solid #e86060;border-radius:10px;background:rgba(232,96,96,.08);color:#e86060;font-size:.78rem;word-break:break-word">
            <i class="fas fa-circle-exclamation"></i> {{ $error }}
        </div>
        @endif
    </div>

    <div style="flex:1;min-width:320px;display:flex;flex-direction:column;gap:1.1rem">
        <div>
            <div style="font-size:.8rem;font-weight:600;margin-bottom:.4rem">{{ __('Live preview') }} <span style="color:var(--text3);font-weight:400">({{ __('exactly what gets captured') }})</span></div>
            <div style="position:relative;width:640px;max-width:100%;aspect-ratio:16/9;overflow:hidden;border-radius:12px;border:1px solid var(--border);background:#000">
                <iframe srcdoc="{{ $this->previewHtml }}"
                        style="position:absolute;top:0;left:0;width:1920px;height:1080px;transform:scale(.3333);transform-origin:top left;border:0;pointer-events:none"></iframe>
            </div>
        </div>

        @if($this->cardUrl)
        <div>
            <div style="font-size:.8rem;font-weight:600;margin-bottom:.4rem">
                {{ __('Rendered image') }}
                <span class="badge badge-green" style="margin-inline-start:.4rem"><i class="fas fa-stopwatch"></i> {{ $cardMs }} {{ __('ms') }}</span>
                <a href="{{ $this->cardUrl }}" target="_blank" rel="noopener" style="margin-inline-start:.4rem;font-size:.75rem">{{ __('Download') }}</a>
            </div>
            <img src="{{ $this->cardUrl }}" style="width:640px;max-width:100%;border-radius:12px;border:1px solid var(--border)" alt="">
        </div>
        @endif

        @if($this->videoUrl)
        <div>
            <div style="font-size:.8rem;font-weight:600;margin-bottom:.4rem">
                {{ __('Sample video') }}
                <span class="badge badge-green" style="margin-inline-start:.4rem"><i class="fas fa-stopwatch"></i> {{ $videoMs }} {{ __('ms') }}</span>
            </div>
            <video src="{{ $this->videoUrl }}" controls playsinline style="width:640px;max-width:100%;border-radius:12px;border:1px solid var(--border);background:#000"></video>
        </div>
        @endif
    </div>

</div>
