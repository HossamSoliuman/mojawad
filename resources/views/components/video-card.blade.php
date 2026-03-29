@props(['video'])
<a href="{{ route('videos.show', $video) }}" class="block bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow group">
<div class="bg-emerald-50 h-36 flex flex-col items-center justify-center group-hover:bg-emerald-100 transition-colors">
<div class="text-emerald-700 text-2xl font-bold">{{ $video->surah->name_ar }}</div>
<div class="text-emerald-600 text-xs mt-1">{{ $video->surah->name_en }}</div>
</div>
<div class="p-4">
<div class="font-medium text-gray-900 text-sm truncate">{{ $video->qari->name_en }}</div>
<div class="text-gray-400 text-xs mt-0.5">{{ $video->qari->name_ar }}</div>
<div class="flex items-center gap-3 mt-3 text-xs text-gray-400">
<span>{{ number_format($video->views) }} views</span>
<span>{{ number_format($video->likes) }} likes</span>
<span>{{ number_format($video->saves) }} saves</span>
</div>
</div>
</a>
