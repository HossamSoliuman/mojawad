<div>
  <div class="list-toolbar">
    <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap">
      @foreach(['popular' => __('Most liked'), 'downloads' => __('Most downloaded'), 'latest' => __('Latest')] as $val => $label)
      <button
        wire:click="$set('sort', '{{ $val }}')"
        class="btn btn-sm {{ $sort === $val ? 'btn-primary' : 'btn-ghost' }}"
        style="font-size:.78rem;padding:.28rem .75rem"
      >{{ $label }}</button>
      @endforeach
    </div>
  </div>

  <div wire:loading.delay wire:target="sort" style="width:100%">
    <div style="display:flex;flex-direction:column;gap:.45rem">
      @for($i = 0; $i < 6; $i++)
      <div class="skel-row">
        <span class="skel skel-num"></span>
        <span class="skel skel-cover"></span>
        <span class="skel skel-line" style="flex:1"></span>
        <span class="skel skel-line" style="width:52px"></span>
      </div>
      @endfor
    </div>
  </div>

  <div wire:loading.remove wire:target="sort">
    @if($tilawat->isEmpty())
    <div style="text-align:center;padding:4rem 1rem;color:var(--text2)">
      <i class="fas fa-book-open" style="font-size:2.5rem;display:block;margin-bottom:.65rem;opacity:.5"></i>
      {{ __('No tilawat yet.') }}
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:.45rem" data-queue>
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
            <a href="{{ route('qaris.show', $t->qari) }}" wire:navigate onclick="event.stopPropagation()" class="gold">{{ $t->qari->name }}</a>
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

    @if($hasMore)
    <div
      wire:key="sentinel-surah-{{ $perPage }}"
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
