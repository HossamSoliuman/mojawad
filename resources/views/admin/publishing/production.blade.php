@extends('layouts.admin')
@section('title', __('Production'))
@section('page-title', __('Production'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Production') }}@endsection
@section('content')

<div style="margin-bottom:1.6rem;max-width:760px">
  <h2 style="font-size:1.1rem;font-weight:700;margin:0 0 .25rem">{{ __('Prepare & publish') }}</h2>
  <p style="color:var(--text2);font-size:.86rem;margin:0">{{ __('Design the animated video card and render the video, then publish it to YouTube, Facebook, and the podcast feed — and track every published recitation.') }}</p>
</div>

<livewire:admin.production-queue />

@endsection
