@props(['status' => 'inactive', 'name' => 'status'])

<label class="status-switch">
  <input type="hidden" name="{{ $name }}" value="inactive">
  <input type="checkbox" class="status-switch-input" name="{{ $name }}" value="active" @checked($status === 'active')>
  <span class="status-switch-track"><span class="status-switch-knob"></span></span>
  <span class="status-switch-text" data-on="{{ __('Active') }}" data-off="{{ __('Not Active') }}"></span>
</label>
