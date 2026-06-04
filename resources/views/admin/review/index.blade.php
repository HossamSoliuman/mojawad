@extends('layouts.admin')
@section('title','Review Queue')
@section('page-title', __('Review Queue'))
@section('breadcrumb')<a href="{{ route('admin.review.index') }}">{{ __('Review') }}</a> › {{ __('Queue') }} @endsection
@section('topbar-actions')
<a href="{{ route('admin.review.history') }}" class="btn btn-ghost btn-sm"><i class="fas fa-clock-rotate-left"></i> {{ __('History') }}</a>
@endsection
@section('content')

<livewire:reviewer.review-queue />

@endsection
