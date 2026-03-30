@extends('layouts.app')
@section('title', 'Home')
@section('content')

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Mojawad</h1>
        <p class="text-gray-500 mt-1 text-sm">Discover and share Quran recitations</p>
    </div>

    @if ($qaris->isNotEmpty())
        <section class="mb-10">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Qaris</h2>
            <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach ($qaris as $qari)
                    <a href="{{ route('qaris.show', $qari) }}"
                        class="bg-white rounded-xl border border-gray-200 p-4 text-center hover:shadow-md transition-shadow">
                        <div
                            class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-2 overflow-hidden">
                            @if ($qari->photo)
                                <img src="{{ Storage::url($qari->photo) }}" class="w-full h-full object-cover">
                            @else
                                <span
                                    class="text-emerald-700 font-bold text-lg">{{ mb_substr($qari->name_en, 0, 1) }}</span>
                            @endif
                        </div>
                        <div class="text-xs font-medium text-gray-800 truncate">{{ $qari->name_en }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $qari->name_ar }}</div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section>
        <h2 class="text-base font-semibold text-gray-800 mb-4">Top Recitations</h2>
        @if ($videos->isEmpty())
            <div class="text-center py-20 text-gray-400">No recitations yet. Be the first to upload.</div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach ($videos as $video)
                    <x-video-card :video="$video" />
                @endforeach
            </div>
            <div class="mt-8">{{ $videos->links() }}</div>
        @endif
    </section>

    @auth
        @if (auth()->user()->isUser() && !auth()->user()->creatorApplication?->isPending())
            <div class="mt-12 border-t border-gray-100 pt-8 text-center">
                <p class="text-sm text-gray-500">Want to upload recitations directly?</p>
                <a href="{{ route('creator.apply.create') }}"
                    class="inline-block mt-2 text-sm text-emerald-600 hover:underline font-medium">Apply to become a Creator</a>
            </div>
        @endif
    @endauth

@endsection
