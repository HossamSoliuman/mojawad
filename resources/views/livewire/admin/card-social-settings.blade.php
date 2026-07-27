<div style="max-width:760px;margin-top:2rem;padding:1.1rem 1.2rem;border:1px solid var(--border);border-radius:12px;background:var(--surface2, transparent)">
    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.2rem">
        <h3 style="font-size:.98rem;font-weight:700;margin:0">{{ __('Footer handles') }}</h3>
        <span class="badge badge-amber">{{ __('Applies to all cards') }}</span>
    </div>
    <p style="color:var(--text2);font-size:.8rem;margin:.25rem 0 1rem">{{ __('These social accounts appear in the footer of every generated card. Leave a field empty to hide that icon.') }}</p>

    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.9rem">
        @foreach(['youtube' => 'YouTube', 'facebook' => 'Facebook', 'website' => __('Website'), 'instagram' => 'Instagram'] as $key => $label)
        <div>
            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">{{ $label }}</label>
            <input type="text" wire:model="social.{{ $key }}" class="form-control" style="width:100%" dir="ltr">
        </div>
        @endforeach
    </div>

    <div style="display:flex;align-items:center;gap:.7rem;margin-top:1rem">
        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
            <span wire:loading.remove wire:target="save"><i class="fas fa-floppy-disk"></i> {{ __('Save handles') }}</span>
            <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin"></i> {{ __('Saving…') }}</span>
        </button>
        @if($saved)
        <span class="badge badge-green"><i class="fas fa-check"></i> {{ __('Saved') }}</span>
        @endif
    </div>
</div>
