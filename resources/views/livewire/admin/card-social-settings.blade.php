<form wire:submit="save" style="width:100%;margin:0 0 1.4rem;padding:1.15rem 1.2rem;border:1px solid var(--border);border-radius:14px;background:var(--surface2,#fff);box-shadow:0 8px 24px rgba(15,23,42,.04)">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
        <div>
            <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                <span style="width:32px;height:32px;border-radius:9px;background:#eff6ff;color:#2563eb;display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-link"></i></span>
                <h3 style="font-size:.98rem;font-weight:700;margin:0">{{ __('Card footer links') }}</h3>
                <span class="badge badge-amber">{{ __('Applies to all cards') }}</span>
            </div>
            <p style="color:var(--text2);font-size:.8rem;margin:.45rem 0 0">{{ __('Set the accounts shown beside the social icons on every generated card. Leave a field empty to hide it.') }}</p>
        </div>
        @if($saved)
        <span class="badge badge-green"><i class="fas fa-check"></i> {{ __('Saved') }}</span>
        @endif
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.85rem">
        @foreach([
            'youtube' => ['YouTube', 'fab fa-youtube', '#ff0000', 'youtube.com/@mojawad'],
            'facebook' => ['Facebook', 'fab fa-facebook-f', '#1877f2', 'facebook.com/mojawad'],
            'website' => [__('Website'), 'fas fa-globe', '#b58b19', 'mojawad.net'],
            'instagram' => ['Instagram', 'fab fa-instagram', '#c13584', 'instagram.com/mojawad'],
        ] as $key => [$label, $icon, $color, $placeholder])
        <div>
            <label for="card-social-{{ $key }}" class="form-label" style="display:flex;align-items:center;gap:.4rem;margin-bottom:.35rem">
                <i class="{{ $icon }}" style="color:{{ $color }};width:15px;text-align:center"></i> {{ $label }}
            </label>
            <input id="card-social-{{ $key }}" type="text" wire:model.blur="social.{{ $key }}" class="form-control" style="width:100%" dir="ltr" inputmode="url" autocomplete="off" placeholder="{{ $placeholder }}">
            @error("social.$key")
            <div class="form-error" style="margin-top:.3rem">{{ $message }}</div>
            @enderror
        </div>
        @endforeach
    </div>

    <div style="display:flex;align-items:center;justify-content:flex-end;gap:.7rem;margin-top:1rem">
        <button type="submit" class="btn btn-primary btn-sm">
            <span wire:loading.remove wire:target="save"><i class="fas fa-floppy-disk"></i> {{ __('Save handles') }}</span>
            <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin"></i> {{ __('Saving…') }}</span>
        </button>
    </div>
</form>
