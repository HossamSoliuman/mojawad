{{-- Factory · Production tab switcher --}}
<div style="display:flex;gap:.4rem;margin-bottom:1.8rem;border-bottom:1px solid var(--border);padding-bottom:0">
  <a href="{{ route('admin.publishing.factory') }}"
     class="btn btn-sm {{ request()->routeIs('admin.publishing.factory') ? 'btn-primary' : 'btn-ghost' }}"
     style="border-radius:10px 10px 0 0">
    <i class="fas fa-industry"></i> {{ __('Factory') }}
  </a>
  <a href="{{ route('admin.publishing.production') }}"
     class="btn btn-sm {{ request()->routeIs('admin.publishing.production') ? 'btn-primary' : 'btn-ghost' }}"
     style="border-radius:10px 10px 0 0">
    <i class="fas fa-tower-broadcast"></i> {{ __('Production') }}
  </a>
</div>
