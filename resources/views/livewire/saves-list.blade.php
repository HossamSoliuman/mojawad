<div @guest x-data x-init="$wire.loadGuest([...(window._savedIds || [])])" @endguest>
  @guest
  <div class="alert alert-warning" style="align-items:center">
    <i class="fas fa-circle-info"></i>
    <span style="flex:1">{{ __('Your saved tilawat are stored on this device only. Sign in to keep them everywhere.') }}</span>
    <a href="{{ route('login') }}" class="btn btn-ghost btn-xs">{{ __('Login') }}</a>
  </div>
  @endguest

  @if($saved->isEmpty())
  <div style="text-align:center;padding:4rem 1rem;color:var(--text2)">
    <i class="fas fa-bookmark" style="font-size:2.5rem;display:block;margin-bottom:.65rem;opacity:.5"></i>
    <p style="margin-bottom:1rem">{{ __('No saved tilawat yet.') }}</p>
    <a href="{{ route('qaris.index') }}" wire:navigate class="btn btn-primary btn-sm">
      <i class="fas fa-compass"></i> {{ __('Explore Qaris') }}
    </a>
  </div>
  @else
  <div class="grid-tilawat" data-queue>
    @foreach($saved as $t)
    <div class="t-card" data-track-id="{{ $t->id }}" wire:key="saved-{{ $t->id }}">
      <div class="t-card-img">
        <img src="{{ $t->cover_url }}" alt="{{ $t->title }}" loading="lazy">
        <button class="t-play-btn" data-track="{{ json_encode($t->playerPayload()) }}">
          <i class="fas fa-play"></i>
        </button>
        <button type="button" class="card-save" data-save-btn="{{ $t->id }}"
          onclick="window.toggleTilawaSave({{ $t->id }})" title="{{ __('Save') }}" aria-label="{{ __('Save') }}">
          <i class="fas fa-bookmark"></i>
        </button>
      </div>
      <div class="t-card-body">
        <a href="{{ route('tilawa.show', $t) }}" wire:navigate>
          <div class="t-card-title">{{ $t->title }}</div>
        </a>
        <a href="{{ route('qaris.show', $t->qari) }}" wire:navigate class="t-card-qari">
          {{ $t->qari->name }}
        </a>
        <div class="t-card-meta">
          <span><i class="fas fa-clock"></i> {{ $t->formatted_duration }}</span>
          <button type="button" class="row-like" data-like-btn="{{ $t->id }}"
            onclick="window.toggleTilawaLike({{ $t->id }})" title="{{ __('Like') }}">
            <i class="fas fa-heart"></i> <span data-like-count="{{ $t->id }}">{{ number_format($t->likes_count) }}</span>
          </button>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  @if($hasMoreSaved)
  <div
    wire:key="sentinel-saved-{{ $savedPerPage }}"
    x-data
    x-init="
      const io = new IntersectionObserver(([e]) => {
        if (e.isIntersecting) { io.disconnect(); $wire.loadMoreSaved(); }
      }, { rootMargin: '200px' });
      io.observe($el);
    "
    style="display:flex;justify-content:center;padding:1.5rem"
  >
    <i class="fas fa-spinner fa-spin" style="color:var(--gold);font-size:1.2rem"></i>
  </div>
  @endif
  @endif

  <div wire:loading style="text-align:center;padding:1rem">
    <i class="fas fa-circle-notch fa-spin" style="color:var(--gold);font-size:1rem;opacity:.6"></i>
  </div>
</div>
