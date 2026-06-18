@extends('layouts.app')
@section('title', $collection->title)
@section('meta_desc', $collection->description ?: $collection->title)
@section('og_image', $collection->cover_url)
@section('content')

<div style="position:relative;overflow:hidden;padding-bottom:2rem">
  <div style="position:absolute;inset:0">
    <img src="{{ $collection->cover_url }}" alt="" style="width:100%;height:100%;object-fit:cover;opacity:.12;filter:blur(2px)">
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(17,24,39,.88) 0%,var(--bg) 100%)"></div>
  </div>
  <div class="wrap z1" style="padding-top:2.5rem">
    <a href="{{ route('collections.index') }}" wire:navigate class="back-link"
      style="font-size:.8rem;color:var(--text2);display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1.4rem">
      <i class="fas fa-arrow-left"></i> {{ __('Collections') }}
    </a>
    <div style="display:flex;align-items:flex-end;gap:1.85rem;flex-wrap:wrap">
      <img src="{{ $collection->cover_url }}" alt="{{ $collection->title }}"
        style="width:148px;height:148px;border-radius:var(--r2);object-fit:cover;border:2px solid var(--border2);box-shadow:0 20px 60px rgba(0,0,0,.65);flex-shrink:0">
      <div>
        <div class="badge badge-gold" style="margin-bottom:.65rem"><i class="fas fa-layer-group"></i> {{ __('Collection') }}</div>
        <h1 style="font-size:clamp(1.65rem,4vw,2.7rem);margin-bottom:.4rem">{{ $collection->title }}</h1>
        <div class="gold" style="font-size:.88rem;margin-bottom:1.1rem">
          <i class="fas fa-music"></i> {{ $collection->tilawat->count() }} {{ __('Tilawat') }}
        </div>
        @if($collection->tilawat->isNotEmpty())
        <button class="btn btn-primary" onclick="document.querySelector('#cList [data-track]')?.click()">
          <i class="fas fa-play"></i> {{ __('Play All') }}
        </button>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="wrap z1" style="padding-bottom:3rem">
  @if($collection->description)
  <div class="card" style="padding:1.5rem;margin-bottom:2.1rem;max-width:680px">
    <p style="line-height:1.8;color:var(--text2)">{{ $collection->description }}</p>
  </div>
  @endif

  @if($collection->tilawat->isEmpty())
  <div style="text-align:center;padding:4rem 1rem;color:var(--text2)">
    <i class="fas fa-music-slash" style="font-size:2.5rem;display:block;margin-bottom:.65rem"></i>
    {{ __('No tilawat yet.') }}
  </div>
  @else
  <div style="display:flex;flex-direction:column;gap:.45rem" id="cList" data-queue>
    @foreach($collection->tilawat as $t)
    <div class="track-row" data-track="{{ json_encode($t->playerPayload()) }}" data-track-id="{{ $t->id }}">
      <span class="track-num">{{ $loop->iteration }}</span>
      <span class="track-eq" aria-hidden="true"><span></span><span></span><span></span></span>
      <img src="{{ $t->cover_url }}" class="track-cover" alt="">
      <div class="track-info">
        <div class="track-title">{{ $t->title }}</div>
        <div class="track-place">
          <a href="{{ route('qaris.show', $t->qari) }}" wire:navigate onclick="event.stopPropagation()" class="gold">{{ $t->qari->name }}</a>
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
  @endif
</div>
@endsection
