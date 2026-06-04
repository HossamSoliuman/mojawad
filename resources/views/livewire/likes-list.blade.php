<div>
  @if($liked->isEmpty())
  <div style="text-align:center;padding:4rem 1rem;color:var(--text2)">
    <i class="fas fa-heart-crack" style="font-size:2.5rem;display:block;margin-bottom:.65rem"></i>
    {{ __('No liked tilawat yet.') }}
  </div>
  @else
  <div class="grid-tilawat">
    @foreach($liked as $item)
    <div class="t-card">
      <div class="t-card-img">
        <img src="{{ $item->tilawa->cover_url }}" alt="{{ $item->tilawa->title }}" loading="lazy">
        <button class="t-play-btn"
          onclick="playTilawa({{ $item->tilawa->id }},'{{ $item->tilawa->audio_url }}',{{ json_encode($item->tilawa->title) }},{{ json_encode($item->tilawa->qari->name) }},'{{ $item->tilawa->cover_url }}',{{ $item->tilawa->duration }},'{{ route('tilawa.download', $item->tilawa) }}')">
          <i class="fas fa-play"></i>
        </button>
      </div>
      <div class="t-card-body">
        <a href="{{ route('tilawa.show', $item->tilawa) }}" wire:navigate>
          <div class="t-card-title">{{ $item->tilawa->title }}</div>
        </a>
        <a href="{{ route('qaris.show', $item->tilawa->qari) }}" wire:navigate class="t-card-qari">
          {{ $item->tilawa->qari->name }}
        </a>
        <div class="t-card-meta">
          <span><i class="fas fa-clock"></i> {{ $item->tilawa->formatted_duration }}</span>
          <span class="gold"><i class="fas fa-heart"></i> <span data-like-count="{{ $item->tilawa->id }}">{{ number_format($item->tilawa->likes_count) }}</span></span>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  @if($hasMoreLiked)
  <div
    wire:key="sentinel-liked-{{ $likedPerPage }}"
    x-data
    x-init="
      const io = new IntersectionObserver(([e]) => {
        if (e.isIntersecting) { io.disconnect(); $wire.loadMoreLiked(); }
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
