@extends('layouts.app')
@section('title', $tilawa->title)
@section('meta_desc', $tilawa->description ?: $tilawa->title.' — '.$tilawa->qari->name)
@section('og_type', 'music.song')
@section('og_image', $tilawa->cover_url)
@section('content')

<div class="wrap z1" style="padding-top:3rem;padding-bottom:4rem;max-width:860px">
  <a href="{{ route('qaris.show',$tilawa->qari) }}" wire:navigate class="back-link"
    style="font-size:.8rem;color:var(--text2);display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1.85rem">
    <i class="fas fa-arrow-left"></i> {{ $tilawa->qari->name }}
  </a>

  <div style="display:flex;gap:2.1rem;flex-wrap:wrap;margin-bottom:2.6rem" data-track-id="{{ $tilawa->id }}">
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
        @if($tilawa->surah_name)
        <span class="badge badge-gold"><i class="fas fa-book-open"></i> {{ $tilawa->surah_name }}</span>
        @endif
        <span class="badge badge-muted"><i class="fas fa-clock"></i> {{ $tilawa->formatted_duration }}</span>
        <span class="badge badge-muted">
          <i class="fas fa-heart"></i> <span data-like-count="{{ $tilawa->id }}">{{ number_format($tilawa->likes_count) }}</span>
        </span>
        <span class="badge badge-muted">
          <i class="fas fa-play"></i> <span data-play-count="{{ $tilawa->id }}">{{ number_format($tilawa->plays_count) }}</span>
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
        <button class="btn btn-primary" data-track="{{ json_encode($tilawa->playerPayload()) }}">
          <i class="fas fa-play"></i> {{ __('Play') }}
        </button>

        <button type="button" class="btn btn-ghost btn-sm show-like-btn" id="likeBtn"
          data-like-btn="{{ $tilawa->id }}"
          data-liked-text="{{ __('Liked') }}" data-like-text="{{ __('Like') }}"
          onclick="toggleLike({{ $tilawa->id }})">
          <i class="fas fa-heart"></i>
          <span id="likeBtnText">{{ __('Like') }}</span>
        </button>

        <button type="button" class="btn btn-ghost btn-sm show-save-btn" id="saveBtn"
          data-save-btn="{{ $tilawa->id }}"
          data-saved-text="{{ __('Saved') }}" data-save-text="{{ __('Save') }}"
          onclick="toggleSave({{ $tilawa->id }})">
          <i class="fas fa-bookmark"></i>
          <span id="saveBtnText">{{ __('Save') }}</span>
        </button>

        <a href="{{ route('tilawa.download',$tilawa) }}" class="btn btn-ghost btn-sm">
          <i class="fas fa-download"></i> {{ __('Download') }}
        </a>

        <button type="button" class="btn btn-ghost btn-sm" onclick="window.shareTilawa()">
          <i class="fas fa-share-nodes"></i> {{ __('Share') }}
        </button>
      </div>
    </div>
  </div>

  @if($tilawa->description)
  <div class="card" style="padding:1.5rem;margin-bottom:2.1rem">
    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.09em;color:var(--text2);margin-bottom:.65rem;font-family:var(--font-display)">
      {{ __('About') }}
    </div>
    <p style="line-height:1.8;color:var(--text2)">{{ $tilawa->description }}</p>
  </div>
  @endif

  @if($related->isNotEmpty())
  <div class="sec-title"><i class="fas fa-layer-group gold"></i> {{ __('More from') }} {{ $tilawa->qari->name }}</div>
  <div class="grid-tilawat" data-queue>
    @foreach($related as $r)
    <div class="t-card" data-track-id="{{ $r->id }}">
      <div class="t-card-img">
        <img src="{{ $r->cover_url }}" alt="{{ $r->title }}" loading="lazy">
        <button class="t-play-btn" data-track="{{ json_encode($r->playerPayload()) }}">
          <i class="fas fa-play"></i>
        </button>
      </div>
      <div class="t-card-body">
        <a href="{{ route('tilawa.show',$r) }}" wire:navigate><div class="t-card-title">{{ $r->title }}</div></a>
        <div class="t-card-qari">{{ $r->qari->name }}</div>
        <div class="t-card-meta">
          <span><i class="fas fa-clock"></i> {{ $r->formatted_duration }}</span>
          <button type="button" class="row-like" data-like-btn="{{ $r->id }}"
            onclick="window.toggleTilawaLike({{ $r->id }})" title="{{ __('Like') }}">
            <i class="fas fa-heart"></i> <span data-like-count="{{ $r->id }}">{{ number_format($r->likes_count) }}</span>
          </button>
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

function paintShowLikeBtn() {
  const btn = document.getElementById('likeBtn');
  if (!btn) return;
  const liked = window.isTilawaLiked(btn.dataset.likeBtn);
  btn.classList.toggle('btn-primary', liked);
  btn.classList.toggle('btn-ghost', !liked);
  const txt = document.getElementById('likeBtnText');
  if (txt) txt.textContent = liked ? btn.dataset.likedText : btn.dataset.likeText;
}
window._likedIdsReady.then(paintShowLikeBtn);
paintShowLikeBtn();

function toggleSave(id) {
  const btn = document.getElementById('saveBtn');
  if (btn) btn.disabled = true;
  Promise.resolve(window.toggleTilawaSave(id)).finally(() => { if (btn) btn.disabled = false; });
}

function paintShowSaveBtn() {
  const btn = document.getElementById('saveBtn');
  if (!btn) return;
  const saved = window.isTilawaSaved(btn.dataset.saveBtn);
  btn.classList.toggle('btn-primary', saved);
  btn.classList.toggle('btn-ghost', !saved);
  const txt = document.getElementById('saveBtnText');
  if (txt) txt.textContent = saved ? btn.dataset.savedText : btn.dataset.saveText;
}
window._savedIdsReady.then(paintShowSaveBtn);
paintShowSaveBtn();

// One persistent listener (survives wire:navigate) that targets the current
// track-page buttons via their data attributes, so they never go stale.
if (!window._showLikeSync) {
  window._showLikeSync = true;
  document.addEventListener('livewire:navigated', () => { paintShowLikeBtn(); paintShowSaveBtn(); });
  window.addEventListener('tilawa-like-changed', (e) => {
    const btn = document.getElementById('likeBtn');
    if (!btn || Number(btn.dataset.likeBtn) !== Number(e.detail.id)) return;
    paintShowLikeBtn();
  });
  window.addEventListener('tilawa-save-changed', (e) => {
    const btn = document.getElementById('saveBtn');
    if (!btn || Number(btn.dataset.saveBtn) !== Number(e.detail.id)) return;
    paintShowSaveBtn();
  });
}
</script>
@endpush
@endsection
