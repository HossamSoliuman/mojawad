@extends('layouts.app')
@section('title', __('Likes'))
@section('content')
<div class="page-hd">
  <div class="wrap"><h1><i class="fas fa-heart gold"></i> {{ __('Likes') }}</h1><p>{{ __('Your liked tilawat') }}</p></div>
</div>
<div class="wrap" style="padding-bottom:3rem">
  <livewire:likes-list />
</div>
@endsection
