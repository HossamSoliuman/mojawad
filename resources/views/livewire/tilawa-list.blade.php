<div>
  {{-- Sort bar --}}
  <div style="display:flex;align-items:center;gap:.65rem;margin-bottom:1.1rem;flex-wrap:wrap">
    <span style="font-size:.8rem;color:var(--text2)">{{ __('Sort:') }}</span>
    @foreach(['latest' => __('Latest'), 'oldest' => __('Oldest'), 'popular' => __('Most liked')] as $val => $label)
    <button
      wire:click="$set('sort', '{{ $val }}')"
      class="btn btn-sm {{ $sort === $val ? 'btn-primary' : 'btn-ghost' }}"
      style="font-size:.78rem;padding:.28rem .75rem"
    >{{ $label }}</button>
    @endforeach
  </div>

  @if($tilawat->isEmpty())
  <div style="text-align:center;padding:4rem 1rem;color:var(--text2)">
    <i class="fas fa-music-slash" style="font-size:2.5rem;display:block;margin-bottom:.65rem"></i>
    {{ __('No tilawat yet.') }}
  </div>
  @else
  <div style="display:flex;flex-direction:column;gap:.45rem" id="tList">
    @foreach($tilawat as $t)
    <div class="track-row"
      onclick="playTilawa({{ $t->id }},'{{ $t->audio_url }}',{{ json_encode($t->title) }},{{ json_encode($t->qari->name) }},'{{ $t->cover_url }}',{{ $t->duration }},'{{ route('tilawa.download', $t) }}')">
      <span class="track-num">{{ $loop->iteration }}</span>
      <img src="{{ $t->cover_url }}" class="track-cover" alt="">
      <div class="track-info">
        <div class="track-title">{{ $t->title }}</div>
        @if($t->recorded_place)
        <div class="track-place"><i class="fas fa-location-dot"></i> {{ $t->recorded_place }}</div>
        @endif
      </div>
      <span style="font-size:.74rem;color:var(--text2);font-family:monospace;flex-shrink:0">{{ $t->formatted_duration }}</span>
      <span style="font-size:.74rem;color:var(--text2);flex-shrink:0"><i class="fas fa-heart"></i> {{ number_format($t->likes_count) }}</span>
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

  <div wire:loading style="text-align:center;padding:1rem">
    <i class="fas fa-circle-notch fa-spin" style="color:var(--gold);font-size:1rem;opacity:.6"></i>
  </div>
</div>
