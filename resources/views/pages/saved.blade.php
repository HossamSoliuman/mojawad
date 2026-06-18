@extends('layouts.app')
@section('title', __('Saved'))
@section('content')
<div class="page-hd">
  <div class="wrap"><h1><i class="fas fa-bookmark gold"></i> {{ __('Saved') }}</h1><p>{{ __('Your saved tilawat') }}</p></div>
</div>
<div class="wrap" style="padding-bottom:3rem">
  <livewire:saves-list />
</div>
@endsection
