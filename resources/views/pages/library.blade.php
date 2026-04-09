@extends('layouts.app')
@section('title','Library')
@section('content')
<div class="page-hd">
  <div class="wrap"><h1><i class="fas fa-bookmark gold"></i> Library</h1><p>Your saved and liked tilawat</p></div>
</div>
<div class="wrap" style="padding-bottom:3rem" x-data="{tab:'saved'}">
  <div style="display:flex;gap:.4rem;margin-bottom:1.65rem;border-bottom:1px solid var(--border);padding-bottom:.65rem">
    <button @click="tab='saved'" :class="tab==='saved'?'btn-primary':'btn-ghost'" class="btn btn-sm">
      <i class="fas fa-bookmark"></i> Saved
    </button>
    <button @click="tab='liked'" :class="tab==='liked'?'btn-primary':'btn-ghost'" class="btn btn-sm">
      <i class="fas fa-heart"></i> Liked
    </button>
  </div>

  <div x-show="tab==='saved'">
    @php $saved = auth()->user()->savedTilawat()->with('tilawa.qari')->latest()->get(); @endphp
    @if($saved->isEmpty())
    <div style="text-align:center;padding:4rem 1rem;color:var(--text2)">
      <i class="fas fa-bookmark-slash" style="font-size:2.5rem;display:block;margin-bottom:.65rem"></i>
      No saved tilawat yet.
    </div>
    @else
    <div class="grid-tilawat">
      @foreach($saved as $item)
      <div class="t-card">
        <div class="t-card-img">
          <img src="{{ $item->tilawa->cover_url }}" alt="{{ $item->tilawa->title }}" loading="lazy">
          <button class="t-play-btn"
            onclick="playTilawa({{ $item->tilawa->id }},'{{ $item->tilawa->audio_url }}',{{ json_encode($item->tilawa->title) }},{{ json_encode($item->tilawa->qari->name) }},'{{ $item->tilawa->cover_url }}',{{ $item->tilawa->duration }},'{{ route('tilawa.download', $item->tilawa) }}')">
            <i class="fas fa-play"></i>
          </button>
        </div>
        <div class="t-card-body">
          <a href="{{ route('tilawa.show',$item->tilawa) }}"><div class="t-card-title">{{ $item->tilawa->title }}</div></a>
          <a href="{{ route('qaris.show',$item->tilawa->qari) }}" class="t-card-qari">{{ $item->tilawa->qari->name }}</a>
          <div class="t-card-meta"><span><i class="fas fa-clock"></i> {{ $item->tilawa->formatted_duration }}</span></div>
        </div>
      </div>
      @endforeach
    </div>
    @endif
  </div>

  <div x-show="tab==='liked'" style="display:none">
    @php $liked = auth()->user()->likes()->with('tilawa.qari')->latest()->get(); @endphp
    @if($liked->isEmpty())
    <div style="text-align:center;padding:4rem 1rem;color:var(--text2)">
      <i class="fas fa-heart-crack" style="font-size:2.5rem;display:block;margin-bottom:.65rem"></i>
      No liked tilawat yet.
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
          <a href="{{ route('tilawa.show',$item->tilawa) }}"><div class="t-card-title">{{ $item->tilawa->title }}</div></a>
          <a href="{{ route('qaris.show',$item->tilawa->qari) }}" class="t-card-qari">{{ $item->tilawa->qari->name }}</a>
          <div class="t-card-meta">
            <span><i class="fas fa-clock"></i> {{ $item->tilawa->formatted_duration }}</span>
            <span class="gold"><i class="fas fa-heart"></i> {{ number_format($item->tilawa->likes_count) }}</span>
          </div>
        </div>
      </div>
      @endforeach
    </div>
    @endif
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection
