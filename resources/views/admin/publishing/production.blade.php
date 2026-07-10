@extends('layouts.admin')
@section('title', __('Production'))
@section('page-title', __('Production'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Production') }}@endsection
@section('content')

@include('admin.publishing._tabs')

<div style="margin-bottom:1.6rem;max-width:760px">
  <h2 style="font-size:1.1rem;font-weight:700;margin:0 0 .25rem">{{ __('Prepare & publish') }}</h2>
  <p style="color:var(--text2);font-size:.86rem;margin:0">{{ __('Master the audio, build the branded cover and video, then publish to YouTube, Facebook, and the podcast feed for Spotify & Anghami.') }}</p>
</div>

<livewire:admin.production-queue />

@endsection
