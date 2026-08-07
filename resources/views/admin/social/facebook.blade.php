@extends('layouts.admin')
@section('title', __('Facebook Posts'))
@section('page-title', __('Facebook Posts'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Facebook Posts') }}@endsection
@section('content')

<livewire:admin.facebook-campaign />

@endsection
