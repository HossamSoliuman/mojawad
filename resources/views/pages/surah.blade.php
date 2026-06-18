@extends('layouts.app')
@section('title', __('Surah :name', ['name' => $name]))
@section('content')
<div class="page-hd">
  <div class="wrap">
    <a href="{{ route('home') }}" wire:navigate class="back-link"
      style="font-size:.8rem;color:var(--text2);display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1rem">
      <i class="fas fa-arrow-left"></i> {{ __('Home') }}
    </a>
    <h1><i class="fas fa-book-open gold"></i> {{ __('Surah :name', ['name' => $name]) }}</h1>
    <p>{{ __('All recitations of this surah') }}</p>
  </div>
</div>
<div class="wrap" style="padding-bottom:3rem">
  <livewire:surah-tilawat :surah="$number" />
</div>
@endsection
