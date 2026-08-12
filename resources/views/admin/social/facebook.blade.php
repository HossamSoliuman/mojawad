@extends('layouts.admin')
@section('title', __('Facebook Post Repository'))
@section('page-title', __('Facebook Post Repository'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Campaign Repository') }}@endsection
@section('content')

<livewire:admin.facebook-campaign />

@endsection
