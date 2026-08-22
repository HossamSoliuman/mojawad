@extends('layouts.app')
@section('title', __('Collections'))
@section('content')
<div class="page-hd">
  <div class="wrap">
    <h1><i class="fas fa-layer-group gold"></i> {{ __('Collections') }}</h1>
    <p>{{ __('Curated sets of recitations') }}</p>
  </div>
</div>
<div class="wrap" style="padding-bottom:3rem">
  @if($collections->isEmpty())
  <div style="text-align:center;padding:4rem 1rem;color:var(--text2)">
    <i class="fas fa-layer-group" style="font-size:2.5rem;display:block;margin-bottom:.65rem;opacity:.5"></i>
    {{ __('No collections yet.') }}
  </div>
  @else
  <div class="grid-collections">
    @foreach($collections as $collection)
    <a href="{{ route('collections.show', $collection) }}" wire:navigate class="collection-card">
      <div class="collection-card-img">
        <img src="{{ $collection->cover_url }}" alt="{{ $collection->title }}" loading="lazy">
        @if($collection->isAuto())
        <span class="collection-card-auto" title="{{ __('Updates automatically') }}"><i class="fas {{ $collection->icon_class }}"></i></span>
        @endif
        <div class="collection-card-overlay">
          <div class="collection-card-title">{{ $collection->title }}</div>
          <div class="collection-card-count">{{ $collection->itemsCount() }} {{ __('tilawat') }}</div>
        </div>
      </div>
    </a>
    @endforeach
  </div>
  @endif
</div>
@endsection
