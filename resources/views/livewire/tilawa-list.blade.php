<div>
  {{-- Toolbar: search + surah filter + sort --}}
  <div class="list-toolbar">
    <div class="search-wrap" style="flex:1;min-width:180px">
      <i class="fas fa-magnifying-glass search-icon"></i>
      <input
        wire:model.live.debounce.300ms="search"
        type="text"
        class="search-input"
        style="width:100%"
        placeholder="{{ __('Search tilawat…') }}"
      >
      @if($search !== '')
      <button type="button" class="search-clear" wire:click="$set('search', '')" aria-label="{{ __('Close') }}">
        <i class="fas fa-xmark"></i>
      </button>
      @endif
    </div>

    @if($surahs->isNotEmpty())
    <select wire:model.live="surah" class="toolbar-select">
      <option value="">{{ __('All surahs') }}</option>
      @foreach($surahs as $n)
      <option value="{{ $n }}">{{ config('surahs.'.$n, $n) }}</option>
      @endforeach
    </select>
    @endif

    <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap">
      @foreach(['latest' => __('Latest'), 'oldest' => __('Oldest'), 'popular' => __('Most liked')] as $val => $label)
      <button
        wire:click="$set('sort', '{{ $val }}')"
        class="btn btn-sm {{ $sort === $val ? 'btn-primary' : 'btn-ghost' }}"
        style="font-size:.78rem;padding:.28rem .75rem"
      >{{ $label }}</button>
      @endforeach
    </div>
  </div>

  {{-- Skeleton while filtering / sorting --}}
  <div wire:loading.delay wire:target="search, sort, surah" style="display:flex;flex-direction:column;gap:.45rem">
    @for($i = 0; $i < 6; $i++)
    <div class="skel-row">
      <span class="skel skel-num"></span>
      <span class="skel skel-cover"></span>
      <span class="skel skel-line" style="flex:1"></span>
      <span class="skel skel-line" style="width:52px"></span>
    </div>
    @endfor
  </div>

  <div wire:loading.remove wire:target="search, sort, surah">
    @if($tilawat->isEmpty())
    <div style="text-align:center;padding:4rem 1rem;color:var(--text2)">
      <i class="fas fa-music-slash" style="font-size:2.5rem;display:block;margin-bottom:.65rem"></i>
      {{ $search !== '' || $surah ? __('No tilawat match your filters.') : __('No tilawat yet.') }}
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:.45rem" id="tList" data-queue>
      @foreach($tilawat as $t)
      <div class="track-row"
        data-track="{{ json_encode($t->playerPayload()) }}"
        data-track-id="{{ $t->id }}">
        <span class="track-num">{{ $loop->iteration }}</span>
        <span class="track-eq" aria-hidden="true"><span></span><span></span><span></span></span>
        <img src="{{ $t->cover_url }}" class="track-cover" alt="">
        <div class="track-info">
          <div class="track-title">{{ $t->title }}</div>
          <div class="track-place">
            @if($t->surah_name)<span class="gold">{{ $t->surah_name }}</span>@endif
            @if($t->recorded_place)<i class="fas fa-location-dot"></i> {{ $t->recorded_place }}@endif
          </div>
        </div>
        <span style="font-size:.74rem;color:var(--text2);font-family:monospace;flex-shrink:0">{{ $t->formatted_duration }}</span>
        <button type="button" class="row-like" data-like-btn="{{ $t->id }}"
          onclick="window.toggleTilawaLike({{ $t->id }})" title="{{ __('Like') }}">
          <i class="fas fa-heart"></i> <span data-like-count="{{ $t->id }}">{{ number_format($t->likes_count) }}</span>
        </button>
        <a href="{{ route('tilawa.show', $t) }}" wire:navigate onclick="event.stopPropagation()" class="btn-icon" title="{{ __('Details') }}">
          <i class="fas fa-circle-info"></i>
        </a>
      </div>
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
      style="display:flex;justify-content:center;padding:1.5rem"
    >
      <i class="fas fa-spinner fa-spin" style="color:var(--gold);font-size:1.2rem"></i>
    </div>
    @endif
    @endif
  </div>
</div>
