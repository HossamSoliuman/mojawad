@extends('layouts.app')
@section('title','Home')
@section('content')

<section class="hero">
  <div class="hero-bg">
    @if($featured_tilawat->isNotEmpty())
    <img src="{{ $featured_tilawat->first()->cover_url }}" alt="">
    @endif
  </div>
  <div class="wrap z1" style="width:100%">
    <div style="max-width:580px">
      <div class="badge badge-gold" style="margin-bottom:1.05rem"><i class="fas fa-star"></i> Premium Tajweed Collection</div>
      <h1 style="font-size:clamp(2rem,5vw,3.7rem);margin-bottom:.85rem;line-height:1.08">
        The World's Finest<br><span class="gold">Qur'an Tilawat</span>
      </h1>
      <p style="font-size:1.08rem;color:var(--text2);margin-bottom:1.75rem;max-width:440px;line-height:1.7">
        Curated high-quality recitations by world-renowned qaris with impeccable tajweed.
      </p>
      <div style="display:flex;gap:.65rem;flex-wrap:wrap">
        <a href="{{ route('qaris.index') }}" class="btn btn-primary"><i class="fas fa-play"></i> Explore Qaris</a>
        @guest<a href="{{ route('register') }}" class="btn btn-ghost"><i class="fas fa-user-plus"></i> Join Free</a>@endguest
      </div>
    </div>
  </div>
</section>

<div class="wrap">

@if($featured_tilawat->isNotEmpty())
<section class="section">
  <div class="sec-title"><i class="fas fa-star gold"></i> Featured Tilawat</div>
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
        <a href="{{ route('tilawa.show',$t) }}"><div class="t-card-title">{{ $t->title }}</div></a>
        <a href="{{ route('qaris.show',$t->qari) }}" class="t-card-qari">{{ $t->qari->name }}</a>
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
  <div class="sec-title"><i class="fas fa-microphone gold"></i> Qaris</div>
  <div class="grid-qaris">
    @foreach($top_qaris as $q)
    <a href="{{ route('qaris.show',$q) }}" class="q-card">
      <img src="{{ $q->image_url }}" alt="{{ $q->name }}" loading="lazy">
      <div class="q-overlay">
        @if($q->is_featured)<span style="color:var(--gold);font-size:.68rem;display:block;margin-bottom:.18rem"><i class="fas fa-star"></i></span>@endif
        <div class="q-name">{{ $q->name }}</div>
        <div class="q-count">{{ $q->tilawat_count }} tilawat</div>
      </div>
      <div class="q-play-btn"><i class="fas fa-play"></i></div>
    </a>
    @endforeach
  </div>
</section>
@endif

@if($popular_tilawat->isNotEmpty())
<section class="section" style="padding-top:0">
  <div class="sec-title"><i class="fas fa-fire gold"></i> Most Loved</div>
  <div class="grid-tilawat">
    @foreach($popular_tilawat as $t)
    <div class="t-card">
      <div class="t-card-img">
        <img src="{{ $t->cover_url }}" alt="{{ $t->title }}" loading="lazy">
        <button class="t-play-btn" onclick="playTilawa({{ $t->id }},'{{ $t->audio_url }}',{{ json_encode($t->title) }},{{ json_encode($t->qari->name) }},'{{ $t->cover_url }}',{{ $t->duration }},'{{ route('tilawa.download', $t) }}')">
          <i class="fas fa-play"></i>
        </button>
      </div>
      <div class="t-card-body">
        <a href="{{ route('tilawa.show',$t) }}"><div class="t-card-title">{{ $t->title }}</div></a>
        <a href="{{ route('qaris.show',$t->qari) }}" class="t-card-qari">{{ $t->qari->name }}</a>
        <div class="t-card-meta">
          <span><i class="fas fa-clock"></i> {{ $t->formatted_duration }}</span>
          <span style="color:var(--gold)"><i class="fas fa-heart"></i> {{ number_format($t->likes_count) }}</span>
        </div>
      </div>
    </div>
    @endforeach
  </div>
</section>
@endif

@if($recent_tilawat->isNotEmpty())
<section class="section" style="padding-top:0;padding-bottom:3rem">
  <div class="sec-title"><i class="fas fa-clock-rotate-left gold"></i> Recently Added</div>
  <div class="grid-tilawat">
    @foreach($recent_tilawat as $t)
    <div class="t-card">
      <div class="t-card-img">
        <img src="{{ $t->cover_url }}" alt="{{ $t->title }}" loading="lazy">
        <button class="t-play-btn" onclick="playTilawa({{ $t->id }},'{{ $t->audio_url }}',{{ json_encode($t->title) }},{{ json_encode($t->qari->name) }},'{{ $t->cover_url }}',{{ $t->duration }},'{{ route('tilawa.download', $t) }}')">
          <i class="fas fa-play"></i>
        </button>
      </div>
      <div class="t-card-body">
        <a href="{{ route('tilawa.show',$t) }}"><div class="t-card-title">{{ $t->title }}</div></a>
        <a href="{{ route('qaris.show',$t->qari) }}" class="t-card-qari">{{ $t->qari->name }}</a>
        <div class="t-card-meta">
          <span><i class="fas fa-clock"></i> {{ $t->formatted_duration }}</span>
          <span><i class="fas fa-download"></i> {{ number_format($t->downloads_count) }}</span>
        </div>
      </div>
    </div>
    @endforeach
  </div>
</section>
@endif

</div>
@endsection
