@extends('layouts.app')
@section('title', $tilawa->title)
@section('content')

<div class="wrap z1" style="padding-top:3rem;padding-bottom:4rem;max-width:860px">
  <a href="{{ route('qaris.show',$tilawa->qari) }}" wire:navigate
    style="font-size:.8rem;color:var(--text2);display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1.85rem">
    <i class="fas fa-arrow-left"></i> {{ $tilawa->qari->name }}
  </a>

  <div style="display:flex;gap:2.1rem;flex-wrap:wrap;margin-bottom:2.6rem">
    <img src="{{ $tilawa->cover_url }}" alt="{{ $tilawa->title }}"
      style="width:205px;height:205px;border-radius:var(--r2);object-fit:cover;border:2px solid var(--border2);box-shadow:0 20px 60px rgba(0,0,0,.55);flex-shrink:0">

    <div style="flex:1;min-width:200px">
      @if($tilawa->is_featured)
      <div class="badge badge-gold" style="margin-bottom:.65rem"><i class="fas fa-star"></i> {{ __('Featured') }}</div>
      @endif
      <h1 style="font-size:clamp(1.3rem,3vw,2rem);margin-bottom:.4rem">{{ $tilawa->title }}</h1>
      <a href="{{ route('qaris.show',$tilawa->qari) }}" wire:navigate class="gold"
        style="display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1.1rem;font-size:.96rem">
        <i class="fas fa-microphone"></i> {{ $tilawa->qari->name }}
      </a>

      <div style="display:flex;gap:.38rem;flex-wrap:wrap;margin-bottom:1.5rem">
        <span class="badge badge-muted"><i class="fas fa-clock"></i> {{ $tilawa->formatted_duration }}</span>
        <span class="badge badge-muted">
          <i class="fas fa-heart"></i> <span data-like-count="{{ $tilawa->id }}">{{ number_format($tilawa->likes_count) }}</span>
        </span>
        <span class="badge badge-muted"><i class="fas fa-download"></i> {{ number_format($tilawa->downloads_count) }}</span>
        @if($tilawa->recorded_at)
        <span class="badge badge-muted"><i class="fas fa-calendar"></i> {{ $tilawa->recorded_at->format('Y') }}</span>
        @endif
        @if($tilawa->recorded_place)
        <span class="badge badge-muted"><i class="fas fa-location-dot"></i> {{ $tilawa->recorded_place }}</span>
        @endif
      </div>

      <div style="display:flex;gap:.55rem;flex-wrap:wrap;align-items:center">
        <button class="btn btn-primary"
          onclick="playTilawa({{ $tilawa->id }},'{{ $tilawa->audio_url }}',{{ json_encode($tilawa->title) }},{{ json_encode($tilawa->qari->name) }},'{{ $tilawa->cover_url }}',{{ $tilawa->duration }},'{{ route('tilawa.download', $tilawa) }}')">
          <i class="fas fa-play"></i> {{ __('Play') }}
        </button>

        <button class="btn btn-sm {{ $liked ? 'btn-primary' : 'btn-ghost' }}" id="likeBtn"
          data-tilawa-id="{{ $tilawa->id }}"
          data-liked-text="{{ __('Liked') }}" data-like-text="{{ __('Like') }}"
          onclick="toggleLike({{ $tilawa->id }})">
          <i class="fas fa-heart"></i>
          <span id="likeBtnText">{{ $liked ? __('Liked') : __('Like') }}</span>
        </button>

        <a href="{{ route('tilawa.download',$tilawa) }}" class="btn btn-ghost btn-sm">
          <i class="fas fa-download"></i> {{ __('Download') }}
        </a>
      </div>
    </div>
  </div>

  @if($tilawa->description)
  <div class="card" style="padding:1.5rem;margin-bottom:2.1rem">
    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.09em;color:var(--text2);margin-bottom:.65rem;font-family:'Cinzel',serif">
      {{ __('About') }}
    </div>
    <p style="line-height:1.8;color:var(--text2)">{{ $tilawa->description }}</p>
  </div>
  @endif

  @if($related->isNotEmpty())
  <div class="sec-title"><i class="fas fa-layer-group gold"></i> {{ __('More from') }} {{ $tilawa->qari->name }}</div>
  <div class="grid-tilawat">
    @foreach($related as $r)
    <div class="t-card">
      <div class="t-card-img">
        <img src="{{ $r->cover_url }}" alt="{{ $r->title }}" loading="lazy">
        <button class="t-play-btn"
          onclick="playTilawa({{ $r->id }},'{{ $r->audio_url }}',{{ json_encode($r->title) }},{{ json_encode($r->qari->name) }},'{{ $r->cover_url }}',{{ $r->duration }},'{{ route('tilawa.download', $r) }}')">
          <i class="fas fa-play"></i>
        </button>
      </div>
      <div class="t-card-body">
        <a href="{{ route('tilawa.show',$r) }}" wire:navigate><div class="t-card-title">{{ $r->title }}</div></a>
        <div class="t-card-qari">{{ $r->qari->name }}</div>
        <div class="t-card-meta">
          <span><i class="fas fa-clock"></i> {{ $r->formatted_duration }}</span>
          <span><i class="fas fa-heart"></i> <span data-like-count="{{ $r->id }}">{{ number_format($r->likes_count) }}</span></span>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  @endif
</div>

@push('scripts')
<script>
function toggleLike(id) {
  const btn = document.getElementById('likeBtn');
  if (btn) btn.disabled = true;
  window.toggleTilawaLike(id).finally(() => { if (btn) btn.disabled = false; });
}

function paintLikeBtn(liked) {
  const btn = document.getElementById('likeBtn');
  if (!btn) return;
  btn.classList.toggle('btn-primary', liked);
  btn.classList.toggle('btn-ghost', !liked);
  const txt = document.getElementById('likeBtnText');
  if (txt) txt.textContent = liked ? btn.dataset.likedText : btn.dataset.likeText;
}

// For signed-out visitors the server renders the button as un-liked; reflect
// any like they stored locally on this device.
function initGuestLikeBtn() {
  if (window._likeEnabled) return;
  const btn = document.getElementById('likeBtn');
  if (btn) paintLikeBtn(window.isTilawaLiked(btn.dataset.tilawaId));
}
initGuestLikeBtn();

// One persistent listener (survives wire:navigate) that targets the current
// track-page button via its data attributes, so it never goes stale.
if (!window._showLikeSync) {
  window._showLikeSync = true;
  document.addEventListener('livewire:navigated', initGuestLikeBtn);
  window.addEventListener('tilawa-like-changed', (e) => {
    const btn = document.getElementById('likeBtn');
    if (!btn || Number(btn.dataset.tilawaId) !== Number(e.detail.id)) return;
    paintLikeBtn(e.detail.liked);
  });
}
</script>
@endpush
@endsection
