@extends('layouts.admin')
@section('title', __('Production'))
@section('page-title', __('Production'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Production') }}@endsection
@section('content')

<div style="margin-bottom:1.6rem;max-width:820px">
  <h2 style="font-size:1.1rem;font-weight:700;margin:0 0 .25rem">{{ __('Production pipeline') }}</h2>
  <p style="color:var(--text2);font-size:.86rem;margin:0">{{ __('Move recitations through four stages: Selection → Preparation (design & render the video) → Publishing (compose each platform’s title & description and upload) → Published.') }}</p>
</div>

<livewire:admin.card-social-settings />

<livewire:admin.production-queue />

@endsection
