@extends('layouts.app')
@section('title', $qari->name_en)
@section('content')

<div class="flex items-start gap-6 mb-10">
<div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden">
@if($qari->photo)
<img src="{{ Storage::url($qari->photo) }}" class="w-full h-full object-cover">
@else
<span class="text-emerald-700 font-bold text-2xl">{{ mb_substr($qari->name_en, 0, 1) }}</span>
@endif
</div>
<div class="flex-1">
<h1 class="text-2xl font-bold text-gray-900">{{ $qari->name_en }}</h1>
<p class="text-gray-500 mt-0.5">{{ $qari->name_ar }}</p>
@if($qari->bio)
<p class="text-gray-600 mt-3 text-sm leading-relaxed max-w-2xl">{{ $qari->bio }}</p>
@endif
</div>
@auth
@if(auth()->user()->canModerate())
<a href="{{ route('qaris.edit', $qari) }}" class="text-sm text-emerald-600 hover:underline flex-shrink-0">Edit</a>
@endif
@endauth
</div>

<h2 class="text-base font-semibold text-gray-800 mb-4">Recitations</h2>
@if($videos->isEmpty())
<div class="text-center py-16 text-gray-400">No approved recitations for this Qari yet.</div>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
@foreach($videos as $video)
<x-video-card :video="$video" />
@endforeach
</div>
<div class="mt-8">{{ $videos->links() }}</div>
@endif

@endsection
