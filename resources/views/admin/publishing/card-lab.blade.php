@extends('layouts.admin')
@section('title', __('Card Lab'))
@section('page-title', __('Card Lab'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Card Lab') }}@endsection
@section('content')

<div style="margin-bottom:1.6rem;max-width:760px">
  <h2 style="font-size:1.1rem;font-weight:700;margin:0 0 .25rem">{{ __('Card Lab') }} <span class="badge badge-amber">{{ __('Experimental') }}</span></h2>
  <p style="color:var(--text2);font-size:.86rem;margin:0">{{ __('Experiment with the card-to-video flow: design an HTML card, capture it as an image, then combine it with the recitation audio into a sample video.') }}</p>
</div>

<livewire:admin.card-lab />

@endsection
