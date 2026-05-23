@extends('layouts.app')
@section('title', __('Library'))
@section('content')
<div class="page-hd">
  <div class="wrap"><h1><i class="fas fa-bookmark gold"></i> {{ __('Library') }}</h1><p>{{ __('Your saved and liked tilawat') }}</p></div>
</div>
<div class="wrap" style="padding-bottom:3rem">
  <livewire:library-list />
</div>
@endsection
