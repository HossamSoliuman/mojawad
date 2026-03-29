<div>
<input
type="text"
wire:model.live.debounce.400ms="query"
placeholder="Search by Qari or Surah..."
class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 mb-6"
>

@if(strlen($query) >= 2)

@if($qaris->isNotEmpty())
<div class="mb-6">
<h3 class="text-sm font-semibold text-gray-700 mb-3">Qaris</h3>
<div class="flex flex-wrap gap-2">
@foreach($qaris as $qari)
<a href="{{ route('qaris.show', $qari) }}"
class="bg-emerald-50 text-emerald-700 text-sm px-3 py-1 rounded-full border border-emerald-200 hover:bg-emerald-100">
{{ $qari->name_en }}
</a>
@endforeach
</div>
</div>
@endif

@if($videos->count())
<h3 class="text-sm font-semibold text-gray-700 mb-3">Recitations</h3>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
@foreach($videos as $video)
<a href="{{ route('videos.show', $video) }}"
class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow">
<p class="font-medium text-sm text-gray-900">{{ $video->surah->name_en }} &mdash; {{ $video->surah->name_ar }}</p>
<p class="text-xs text-gray-500 mt-1">{{ $video->qari->name_en }}</p>
<p class="text-xs text-gray-400 mt-2">{{ number_format($video->views) }} views</p>
</a>
@endforeach
</div>
@else
<div class="text-center py-10 text-gray-400 text-sm">No results for &ldquo;{{ $query }}&rdquo;</div>
@endif

@else
<div class="text-center py-16 text-gray-400 text-sm">Type at least 2 characters to search</div>
@endif
</div>
