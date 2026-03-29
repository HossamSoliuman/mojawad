@extends('layouts.app')
@section('title', $video->surah->name_en . ' — ' . $video->qari->name_en)
@section('content')

    <div class="max-w-4xl mx-auto">

        <div class="bg-black rounded-xl overflow-hidden">
            @if ($video->isHosted() && $video->file_path)
                <video controls class="w-full aspect-video" src="{{ Storage::url($video->file_path) }}"></video>
            @elseif($video->isLinked() && $video->external_url)
                @php
                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $video->external_url, $m);
                    $ytId = $m[1] ?? null;
                @endphp
                @if ($ytId)
                    <div class="aspect-video">
                        <iframe class="w-full h-full" src="https://www.youtube.com/embed/{{ $ytId }}"
                            allowfullscreen></iframe>
                    </div>
                @else
                    <div class="aspect-video flex items-center justify-center bg-gray-900">
                        <a href="{{ $video->external_url }}" target="_blank"
                            class="bg-emerald-600 text-white px-6 py-3 rounded-lg text-sm hover:bg-emerald-700">Open
                            Recording</a>
                    </div>
                @endif
            @endif
        </div>

        <div class="mt-5 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">
                    {{ $video->surah->name_en }}
                    <span class="text-gray-400 font-normal text-base ml-2">{{ $video->surah->name_ar }}</span>
                </h1>
                <a href="{{ route('qaris.show', $video->qari) }}"
                    class="text-emerald-600 hover:underline text-sm mt-1 inline-block">
                    {{ $video->qari->name_en }} &middot; {{ $video->qari->name_ar }}
                </a>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <livewire:like-button :video="$video" />
                <livewire:save-button :video="$video" />
                @auth
                    @if (auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('admin.videos.destroy', $video) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Remove this video from the platform?')"
                                class="text-xs text-red-500 hover:text-red-700 ml-2">Remove</button>
                        </form>
                    @endif
                @endauth
            </div>
        </div>

        <div class="flex items-center gap-6 mt-3 text-xs text-gray-400">
            <span>{{ number_format($video->views) }} views</span>
            <span>Score {{ $video->score }}</span>
        </div>

    </div>

@endsection
