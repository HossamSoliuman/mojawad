@extends('layouts.app')
@section('title', __('Qaris'))
@section('content')
<div class="page-hd" style="background:linear-gradient(to bottom,var(--surface) 0%,transparent 100%)">
  <div class="wrap">
    <h1><i class="fas fa-microphone-lines gold"></i> {{ __('Qaris') }}</h1>
    <p>{{ __('Discover world-renowned Qur\'an reciters') }}</p>
  </div>
</div>
<div class="wrap" style="padding-bottom:3rem">
  <livewire:qari-list />
</div>
@endsection
