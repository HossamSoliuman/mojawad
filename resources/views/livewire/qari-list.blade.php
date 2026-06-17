<div>
  {{-- Toolbar --}}
  <div class="list-toolbar">
    <div class="search-wrap" style="flex:1;min-width:200px">
      <i class="fas fa-magnifying-glass search-icon"></i>
      <input
        wire:model.live.debounce.300ms="search"
        type="text"
        class="search-input"
        style="width:100%"
        placeholder="{{ __('Search qaris…') }}"
      >
      @if($search !== '')
      <button type="button" class="search-clear" wire:click="$set('search', '')" aria-label="{{ __('Close') }}">
        <i class="fas fa-xmark"></i>
      </button>
      @endif
    </div>
    <select wire:model.live="sort" class="toolbar-select">
      <option value="featured">{{ __('Featured first') }}</option>
      <option value="count">{{ __('Most tilawat') }}</option>
      <option value="name">{{ __('Name A–Z') }}</option>
    </select>
  </div>

  {{-- Skeleton while searching / sorting --}}
  <div wire:loading.delay wire:target="search, sort" class="grid-qaris">
    @for($i = 0; $i < 8; $i++)
    <div class="skel skel-qcard"></div>
    @endfor
  </div>

  <div wire:loading.remove wire:target="search, sort">
    @if($qaris->isEmpty())
    <div style="text-align:center;padding:5rem 1rem;color:var(--text2)">
      <i class="fas fa-microphone-slash" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
      {{ __('No qaris found.') }}
    </div>
    @else
    <div class="grid-qaris">
      @foreach($qaris as $q)
      <a href="{{ route('qaris.show', $q) }}" wire:navigate class="q-card">
        <img src="{{ $q->image_url }}" alt="{{ $q->name }}" loading="lazy">
        <div class="q-overlay">
          @if($q->is_featured)<span style="color:var(--gold);font-size:.68rem;display:block;margin-bottom:.18rem"><i class="fas fa-star"></i></span>@endif
          <div class="q-name">{{ $q->name }}</div>
          <div class="q-count">{{ $q->tilawat_count }} {{ __('tilawat') }}</div>
        </div>
        <div class="q-play-btn"><i class="fas fa-play"></i></div>
      </a>
      @endforeach
    </div>

    {{-- Infinite scroll sentinel --}}
    @if($hasMore)
    <div
      wire:key="sentinel-{{ $perPage }}"
      x-data
      x-init="
        const io = new IntersectionObserver(([e]) => {
          if (e.isIntersecting) { io.disconnect(); $wire.loadMore(); }
        }, { rootMargin: '200px' });
        io.observe($el);
      "
      style="display:flex;justify-content:center;padding:2rem"
    >
      <i class="fas fa-spinner fa-spin" style="color:var(--gold);font-size:1.2rem"></i>
    </div>
    @endif
    @endif
  </div>
</div>
