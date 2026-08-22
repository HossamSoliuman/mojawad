@extends('layouts.admin')
@section('title', __('Collections'))
@section('page-title', __('Collections'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Collections') }}@endsection
@section('content')

<div style="margin-bottom:1.6rem;max-width:760px">
  <p style="color:var(--text2);font-size:.86rem;margin:0">
    {{ __('Curated sets shown on the home page and the collections page. Hand-pick recitations yourself, or let a rule fill the set from live stats.') }}
  </p>
</div>

<livewire:admin.collection-manager />

@endsection
