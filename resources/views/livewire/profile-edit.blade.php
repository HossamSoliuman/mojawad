<div x-data="{ open: @entangle('open') }">

  <div style="display:flex;gap:.5rem;align-items:center">
    <button type="button" @click="open = true" class="ni-icon-btn" title="{{ __('Edit account') }}"
      aria-label="{{ __('Edit account') }}">
      <i class="fas fa-pen"></i>
    </button>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="ni-icon-btn" title="{{ __('Logout') }}" aria-label="{{ __('Logout') }}">
        <i class="fas fa-arrow-right-from-bracket flip-rtl" style="color:var(--red)"></i>
      </button>
    </form>
  </div>

  <div class="modal-backdrop" x-show="open" x-transition style="display:none" @keydown.escape.window="open = false">
    <div class="modal" @click.outside="open = false">
      <div class="modal-title"><i class="fas fa-user-pen gold"></i> {{ __('Edit account') }}</div>

      <form wire:submit="save">
        <div class="form-group">
          <label class="form-label"><i class="fas fa-user"></i> {{ __('Name') }}</label>
          <input type="text" wire:model="name" class="form-control" placeholder="{{ __('Your Name') }}">
          @error('name')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fas fa-envelope"></i> {{ __('Email') }}</label>
          <input type="email" wire:model="email" class="form-control" placeholder="you@example.com">
          @error('email')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fas fa-lock"></i> {{ __('New Password') }}</label>
          <input type="password" wire:model="password" class="form-control"
            placeholder="{{ __('Leave blank to keep current') }}">
          @error('password')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fas fa-lock"></i> {{ __('Confirm Password') }}</label>
          <input type="password" wire:model="password_confirmation" class="form-control"
            placeholder="{{ __('Repeat password') }}">
        </div>

        <div style="display:flex;gap:.6rem;margin-top:.4rem">
          <button type="button" class="btn" @click="open = false" style="flex:1;justify-content:center">
            {{ __('Cancel') }}
          </button>
          <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"
            wire:loading.attr="disabled" wire:target="save">
            <i class="fas fa-check" wire:loading.remove wire:target="save"></i>
            <i class="fas fa-circle-notch fa-spin" wire:loading wire:target="save"></i>
            {{ __('Save') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
