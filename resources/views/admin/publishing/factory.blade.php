@extends('layouts.admin')
@section('title', __('Factory'))
@section('page-title', __('Factory'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Factory') }}@endsection
@section('content')

@include('admin.publishing._tabs')

<div style="max-width:820px;margin-bottom:2.2rem">
  <livewire:admin.factory-import />
</div>

<livewire:admin.factory-queue />

@endsection
