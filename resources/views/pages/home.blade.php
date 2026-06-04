@extends('layouts.app')
@section('title','Home')
@section('content')

@php
  $hero_slides = ($hero_qaris ?? collect())->isNotEmpty() ? $hero_qaris : $top_qaris;
@endphp
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

    @if($hero_slides->isNotEmpty())
    <div class="hero-marquee">
      <div class="marquee-col">
        <div class="marquee-track">
          @foreach($hero_slides as $q)@include('partials.marquee-qari')@endforeach
          @foreach($hero_slides as $q)@include('partials.marquee-qari')@endforeach
        </div>
      </div>
      <div class="marquee-col marquee-col-2">
        <div class="marquee-track">
          @foreach($hero_slides->reverse() as $q)@include('partials.marquee-qari')@endforeach
          @foreach($hero_slides->reverse() as $q)@include('partials.marquee-qari')@endforeach
        </div>
      </div>
    </div>
    @endif
  </div>
</section>

<div class="wrap">

@if($featured_tilawat->isNotEmpty())
<section class="section">
  <div class="sec-title"><i class="fas fa-star gold"></i> {{ __('Featured Tilawat') }}</div>
  <div class="grid-tilawat">
    @foreach($featured_tilawat as $t)
    <div class="t-card">
      <div class="t-card-img">
        <img src="{{ $t->cover_url }}" alt="{{ $t->title }}" loading="lazy">
        <button class="t-play-btn" onclick="playTilawa({{ $t->id }},'{{ $t->audio_url }}',{{ json_encode($t->title) }},{{ json_encode($t->qari->name) }},'{{ $t->cover_url }}',{{ $t->duration }},'{{ route('tilawa.download', $t) }}')">
          <i class="fas fa-play"></i>
        </button>
      </div>
      <div class="t-card-body">
        <a href="{{ route('tilawa.show',$t) }}" wire:navigate><div class="t-card-title">{{ $t->title }}</div></a>
        <a href="{{ route('qaris.show',$t->qari) }}" wire:navigate class="t-card-qari">{{ $t->qari->name }}</a>
        <div class="t-card-meta">
          <span><i class="fas fa-clock"></i> {{ $t->formatted_duration }}</span>
          <span><i class="fas fa-heart"></i> {{ number_format($t->likes_count) }}</span>
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
  <div class="t-list-grid">
    @foreach($popular_tilawat as $t)
    <div class="t-list-item" onclick="playTilawa({{ $t->id }},'{{ $t->audio_url }}',{{ json_encode($t->title) }},{{ json_encode($t->qari->name) }},'{{ $t->cover_url }}',{{ $t->duration }},'{{ route('tilawa.download', $t) }}')">
      <img src="{{ $t->cover_url }}" class="t-list-cover" alt="{{ $t->title }}" loading="lazy">
      <div class="t-list-info">
        <div class="t-list-title"><a href="{{ route('tilawa.show',$t) }}" wire:navigate onclick="event.stopPropagation()" style="color:inherit">{{ $t->title }}</a></div>
        <div class="t-list-qari"><a href="{{ route('qaris.show',$t->qari) }}" wire:navigate onclick="event.stopPropagation()" style="color:inherit">{{ $t->qari->name }}</a></div>
      </div>
      <div class="t-list-meta">
        <span><i class="fas fa-clock"></i> {{ $t->formatted_duration }}</span>
        <span style="color:var(--gold)"><i class="fas fa-heart"></i> {{ number_format($t->likes_count) }}</span>
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
  <div class="t-list-grid">
    @foreach($recent_tilawat as $t)
    <div class="t-list-item" onclick="playTilawa({{ $t->id }},'{{ $t->audio_url }}',{{ json_encode($t->title) }},{{ json_encode($t->qari->name) }},'{{ $t->cover_url }}',{{ $t->duration }},'{{ route('tilawa.download', $t) }}')">
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

@push('scripts')
<script>
  document.addEventListener('alpine:init', () => {
    Alpine.data('quranRadio', () => ({
      audio: null,
      playing: false,
      loading: false,
      stream: 'https://n0e.radiojar.com/8s5u5tpdtwzuv',
      init() {
        if (!window.quranRadioAudio) {
          // Keep one Audio element alive for the whole session (survives wire:navigate).
          // Pre-buffer the live stream up-front so the first click starts instantly.
          const a = new Audio();
          a.preload = 'auto';
          a.src = this.stream;
          a.load();
          window.quranRadioAudio = a;
        }
        this.audio = window.quranRadioAudio;
        this.playing = !this.audio.paused && !!this.audio.src;
        this.audio.addEventListener('playing', () => { this.playing = true; this.loading = false; });
        this.audio.addEventListener('pause', () => { this.playing = false; });
        this.audio.addEventListener('waiting', () => { this.loading = true; });
        this.audio.addEventListener('error', () => { this.playing = false; this.loading = false; });
      },
      toggle() {
        if (this.playing) {
          this.audio.pause();
          return;
        }
        // Optimistic UI: flip to loading the instant the user clicks.
        this.loading = true;
        if (window.globalAudio && !window.globalAudio.paused) {
          window.globalAudio.pause();
        }
        if (!this.audio.src) {
          this.audio.src = this.stream;
        }
        this.audio.play().catch(() => { this.loading = false; });
      },
    }));
  });
</script>
@endpush
@endsection
