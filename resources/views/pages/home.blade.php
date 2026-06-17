@extends('layouts.app')
@section('title','Home')
@section('content')

@if(!empty($hero_shorts))
{{-- ── Hero "Shorts" — TikTok-style autoplay overlay shown on each fresh visit ── --}}
<div class="shorts-overlay" x-data="heroShorts(@js($hero_shorts))" x-show="open" x-cloak
  @keydown.escape.window="close()" style="display:none">
  <div class="shorts-stage" @click="toggleMute()">
    <button class="shorts-close" @click.stop="close()" aria-label="{{ __('Close') }}"><i class="fas fa-xmark"></i></button>

    <video x-ref="video" class="shorts-media" x-show="current && current.type === 'video'"
      :src="current ? current.src : ''" :poster="current ? current.poster : ''" playsinline loop></video>

    <div class="shorts-audio" x-show="current && current.type === 'audio'"
      :style="current && current.poster ? `background-image:url('${current.poster}')` : ''">
      <audio x-ref="audio" :src="current ? current.src : ''" loop></audio>
      <div class="shorts-audio-eq" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
    </div>

    <div class="shorts-mute" @click.stop="toggleMute()" :title="muted ? '{{ __('Unmute') }}' : '{{ __('Mute') }}'">
      <i class="fas" :class="muted ? 'fa-volume-xmark' : 'fa-volume-high'"></i>
    </div>

    <div class="shorts-tap-hint" x-show="muted" x-transition.opacity>
      <i class="fas fa-volume-high"></i> {{ __('Tap to unmute') }}
    </div>

    <div class="shorts-info">
      <span class="shorts-badge"><i class="fas fa-clapperboard"></i> {{ __('Short') }}</span>
      <div class="shorts-title" x-text="current ? current.title : ''"></div>
    </div>

    <button class="shorts-next" x-show="items.length > 1" @click.stop="next()" aria-label="{{ __('Next') }}">
      <i class="fas fa-forward-step"></i>
    </button>
  </div>
</div>
@endif

<section class="hero" x-data="quranRadio()">
  <div class="hero-grid">
    <div class="hero-live z1">
      <span class="hero-eyebrow"><span class="radio-dot"></span> {{ __('Live') }} · {{ __('Broadcasting from Cairo') }}</span>
      <h1 class="hero-heading">{{ __('The Holy Qur\'an, with you everywhere') }}</h1>
      <p class="hero-sub">{{ __('Listen to the live broadcast and explore recitations from your favorite reciters.') }}</p>

      <div class="radio-card" :class="playing && 'playing'">
        <div class="radio-live">
          <span class="radio-dot"></span> {{ __('Live') }}
        </div>
        <div class="radio-eq" aria-hidden="true">
          <span></span><span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
        <div class="radio-meta">
          <div class="radio-name">{{ __('Holy Qur\'an Radio') }}</div>
          <div class="radio-from"><i class="fas fa-tower-broadcast"></i> {{ __('Broadcasting from Cairo') }}</div>
        </div>
        <button class="radio-btn" @click="toggle()" :title="playing ? '{{ __('Pause') }}' : '{{ __('Listen Live') }}'">
          <i class="fas" :class="loading ? 'fa-spinner fa-spin' : (playing ? 'fa-pause' : 'fa-play')"></i>
        </button>
      </div>
    </div>
  </div>
</section>

<div class="wrap">

<section class="section" x-data="continueListening()" @tilawa-history-changed.window="load()"
  x-show="items.length" x-cloak style="display:none">
  <div class="sec-title"><i class="fas fa-clock-rotate-left gold"></i> {{ __('Continue listening') }}</div>
  <div class="rail" data-queue>
    <template x-for="(t, idx) in items" :key="t.id">
      <div class="t-card rail-card" :data-track-id="t.id">
        <div class="t-card-img">
          <img :src="t.cover" :alt="t.title" loading="lazy">
          <button class="t-play-btn" @click="play(idx)" :title="'{{ __('Play') }}'">
            <i class="fas fa-play"></i>
          </button>
          <button class="card-dismiss" @click.stop="dismiss(t.id)" :title="'{{ __('Remove from history') }}'">
            <i class="fas fa-xmark"></i>
          </button>
          <div class="t-card-progress"><div class="t-card-progress-fill" :style="'width:' + pct(t) + '%'"></div></div>
        </div>
        <div class="t-card-body">
          <div class="t-card-title" x-text="t.title"></div>
          <div class="t-card-qari" x-text="t.qari"></div>
        </div>
      </div>
    </template>
  </div>
</section>

@if($featured_tilawat->isNotEmpty())
<section class="section">
  <div class="sec-title"><i class="fas fa-star gold"></i> {{ __('Featured Tilawat') }}</div>
  <div class="grid-tilawat" data-queue>
    @foreach($featured_tilawat as $t)
    <div class="t-card" data-track-id="{{ $t->id }}">
      <div class="t-card-img">
        <img src="{{ $t->cover_url }}" alt="{{ $t->title }}" loading="lazy">
        <button class="t-play-btn" data-track="{{ json_encode($t->playerPayload()) }}">
          <i class="fas fa-play"></i>
        </button>
      </div>
      <div class="t-card-body">
        <a href="{{ route('tilawa.show',$t) }}" wire:navigate><div class="t-card-title">{{ $t->title }}</div></a>
        <a href="{{ route('qaris.show',$t->qari) }}" wire:navigate class="t-card-qari">{{ $t->qari->name }}</a>
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
</section>
@endif

@if($top_qaris->isNotEmpty())
<section class="section" style="padding-top:0">
  <div class="sec-title"><i class="fas fa-microphone gold"></i> {{ __('Qaris') }}</div>
  <div class="grid-qaris">
    @foreach($top_qaris as $q)
    <a href="{{ route('qaris.show',$q) }}" wire:navigate class="q-card">
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
</section>
@endif

@if($popular_tilawat->isNotEmpty())
<section class="section" style="padding-top:0">
  <div class="sec-title"><i class="fas fa-fire gold"></i> {{ __('Most Loved') }}</div>
  <div class="t-list-grid" data-queue>
    @foreach($popular_tilawat as $t)
    <div class="t-list-item" data-track="{{ json_encode($t->playerPayload()) }}" data-track-id="{{ $t->id }}">
      <img src="{{ $t->cover_url }}" class="t-list-cover" alt="{{ $t->title }}" loading="lazy">
      <div class="t-list-info">
        <div class="t-list-title"><a href="{{ route('tilawa.show',$t) }}" wire:navigate onclick="event.stopPropagation()" style="color:inherit">{{ $t->title }}</a></div>
        <div class="t-list-qari"><a href="{{ route('qaris.show',$t->qari) }}" wire:navigate onclick="event.stopPropagation()" style="color:inherit">{{ $t->qari->name }}</a></div>
      </div>
      <div class="t-list-meta">
        <span><i class="fas fa-clock"></i> {{ $t->formatted_duration }}</span>
        <button type="button" class="row-like" data-like-btn="{{ $t->id }}"
          onclick="window.toggleTilawaLike({{ $t->id }})" title="{{ __('Like') }}">
          <i class="fas fa-heart"></i> <span data-like-count="{{ $t->id }}">{{ number_format($t->likes_count) }}</span>
        </button>
      </div>
      <div class="t-list-play"><i class="fas fa-play"></i></div>
    </div>
    @endforeach
  </div>
</section>
@endif

@if($recent_tilawat->isNotEmpty())
<section class="section" style="padding-top:0;padding-bottom:3rem">
  <div class="sec-title"><i class="fas fa-clock-rotate-left gold"></i> {{ __('Recently Added') }}</div>
  <div class="t-list-grid" data-queue>
    @foreach($recent_tilawat as $t)
    <div class="t-list-item" data-track="{{ json_encode($t->playerPayload()) }}" data-track-id="{{ $t->id }}">
      <img src="{{ $t->cover_url }}" class="t-list-cover" alt="{{ $t->title }}" loading="lazy">
      <div class="t-list-info">
        <div class="t-list-title"><a href="{{ route('tilawa.show',$t) }}" wire:navigate onclick="event.stopPropagation()" style="color:inherit">{{ $t->title }}</a></div>
        <div class="t-list-qari"><a href="{{ route('qaris.show',$t->qari) }}" wire:navigate onclick="event.stopPropagation()" style="color:inherit">{{ $t->qari->name }}</a></div>
      </div>
      <div class="t-list-meta">
        <span><i class="fas fa-clock"></i> {{ $t->formatted_duration }}</span>
        <span><i class="fas fa-download"></i> {{ number_format($t->downloads_count) }}</span>
      </div>
      <div class="t-list-play"><i class="fas fa-play"></i></div>
    </div>
    @endforeach
  </div>
</section>
@endif

</div>

@endsection
