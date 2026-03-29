@extends('layouts.app')
@section('title', 'My Library')
@section('content')

<h1 class="text-2xl font-bold text-gray-900 mb-6">My Library</h1>

@if($videos->isEmpty())
<div class="text-center py-20 text-gray-400">
<p>No saved recitations yet.</p>
<a href="{{ route('home') }}" class="text-emerald-600 hover:underline text-sm mt-2 inline-block">Browse recitations</a>
</div>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
@foreach($videos as $video)
<x-video-card :video="$video" />
@endforeach
</div>
<div class="mt-8">{{ $videos->links() }}</div>
@endif

@endsection
