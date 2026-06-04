@extends('layouts.admin')
@section('title','Review History')
@section('page-title', __('Review History'))
@section('breadcrumb')<a href="{{ route('admin.review.index') }}">{{ __('Review') }}</a> › {{ __('History') }} @endsection
@section('topbar-actions')
<a href="{{ route('admin.review.index') }}" class="btn btn-primary btn-sm"><i class="fas fa-clipboard-check"></i> {{ __('Queue') }}</a>
@endsection
@section('content')

<livewire:reviewer.review-history />

@endsection
