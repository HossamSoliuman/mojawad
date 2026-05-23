<div>
  {{-- Toolbar --}}
  <div style="display:flex;gap:.65rem;align-items:center;flex-wrap:wrap;margin-bottom:1.65rem">
    <div style="position:relative;flex:1;min-width:200px">
      <i class="fas fa-magnifying-glass" style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--text3);font-size:.85rem;pointer-events:none"></i>
      <input
        wire:model.live.debounce.300ms="search"
        type="text"
        placeholder="{{ __('Search qaris…') }}"
        style="width:100%;padding:.55rem .75rem .55rem 2.2rem;background:var(--surface);border:1px solid var(--border);border-radius:var(--r1);color:var(--text1);font-size:.88rem;box-sizing:border-box"
      >
    </div>
    <select wire:model.live="sort" style="padding:.55rem .9rem;background:var(--surface);border:1px solid var(--border);border-radius:var(--r1);color:var(--text1);font-size:.85rem;cursor:pointer">
      <option value="featured">{{ __('Featured first') }}</option>
      <option value="count">{{ __('Most tilawat') }}</option>
      <option value="name">{{ __('Name A–Z') }}</option>
    </select>
  </div>

  {{-- Grid --}}
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

  <div wire:loading style="text-align:center;padding:1rem">
    <i class="fas fa-circle-notch fa-spin" style="color:var(--gold);font-size:1rem;opacity:.6"></i>
  </div>
</div>
