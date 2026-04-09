@extends('layouts.app')
@section('title','Qaris')
@section('content')
<div class="page-hd" style="background:linear-gradient(to bottom,var(--surface) 0%,transparent 100%)">
  <div class="wrap">
    <h1><i class="fas fa-microphone-lines gold"></i> Qaris</h1>
    <p>Discover world-renowned Qur'an reciters</p>
  </div>
</div>
<div class="wrap" style="padding-bottom:3rem">
  @if($qaris->isEmpty())
  <div style="text-align:center;padding:5rem 1rem;color:var(--text2)">
    <i class="fas fa-microphone-slash" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
    No qaris have been added yet.
  </div>
  @else
  <div class="grid-qaris">
    @foreach($qaris as $q)
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
  {{ $qaris->links('vendor.pagination.custom') }}
  @endif
</div>
@endsection
