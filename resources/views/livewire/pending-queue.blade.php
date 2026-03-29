<div>
@if(session('success'))
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm">
{{ session('success') }}
</div>
@endif

@if($videos->isEmpty())
<div class="text-center py-20 text-gray-400">No pending videos. Queue is clear.</div>
@else
<div class="space-y-4 max-w-3xl">
@foreach($videos as $video)
<div class="bg-white border border-gray-200 rounded-xl p-5 flex items-start justify-between gap-4">
<div>
<p class="font-medium text-gray-900">
{{ $video->surah->name_en }}
<span class="text-gray-400 font-normal">{{ $video->surah->name_ar }}</span>
</p>
<p class="text-sm text-gray-600 mt-1">{{ $video->qari->name_en }}</p>
<p class="text-xs text-gray-400 mt-2">
Submitted by {{ $video->uploader->name }} &middot; {{ $video->created_at->diffForHumans() }}
</p>
<div class="mt-2">
@if($video->isLinked())
<a href="{{ $video->external_url }}" target="_blank" class="text-xs text-emerald-600 hover:underline">Preview link</a>
@else
<span class="text-xs text-gray-400">Hosted file</span>
@endif
</div>
</div>
<div class="flex flex-col gap-2 flex-shrink-0">
<button wire:click="approve({{ $video->id }})" class="bg-emerald-600 text-white text-xs px-4 py-2 rounded-lg hover:bg-emerald-700">
Approve
</button>
<button wire:click="reject({{ $video->id }})" class="bg-red-50 text-red-600 text-xs px-4 py-2 rounded-lg border border-red-200 hover:bg-red-100">
Reject
</button>
</div>
</div>
@endforeach
</div>
<div class="mt-6 max-w-3xl">{{ $videos->links() }}</div>
@endif
</div>
