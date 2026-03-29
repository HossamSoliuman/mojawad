@extends('layouts.app')
@section('title', 'Upload Recitation')
@section('content')

<div class="max-w-xl mx-auto">
<h1 class="text-2xl font-bold text-gray-900 mb-6">Upload Recitation</h1>

<form method="POST" action="{{ route('videos.store') }}" enctype="multipart/form-data"
class="bg-white rounded-xl border border-gray-200 p-6 space-y-5"
x-data="{ type: 'linked' }">
@csrf

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Qari</label>
<select name="qari_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
<option value="">Select Qari</option>
@foreach($qaris as $qari)
<option value="{{ $qari->id }}" @selected(old('qari_id') == $qari->id)>
{{ $qari->name_en }} &mdash; {{ $qari->name_ar }}
</option>
@endforeach
</select>
@error('qari_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Surah</label>
<select name="surah_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
<option value="">Select Surah</option>
@foreach($surahs as $surah)
<option value="{{ $surah->id }}" @selected(old('surah_id') == $surah->id)>
{{ $surah->number }}. {{ $surah->name_en }} &mdash; {{ $surah->name_ar }}
</option>
@endforeach
</select>
@error('surah_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div>
<label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
<div class="flex gap-6">
<label class="flex items-center gap-2 text-sm cursor-pointer">
<input type="radio" x-model="type" value="linked" class="text-emerald-600"> External link
</label>
<label class="flex items-center gap-2 text-sm cursor-pointer">
<input type="radio" x-model="type" value="hosted" class="text-emerald-600"> Upload file
</label>
</div>
</div>

<div x-show="type === 'linked'" x-cloak>
<label class="block text-sm font-medium text-gray-700 mb-1">URL</label>
<input type="url" name="external_url" value="{{ old('external_url') }}" placeholder="https://youtube.com/watch?v=..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
@error('external_url')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div x-show="type === 'hosted'" x-cloak>
<label class="block text-sm font-medium text-gray-700 mb-1">File (mp4, webm, mkv &mdash; max 200MB)</label>
<input type="file" name="file" accept="video/*" class="w-full text-sm text-gray-500">
@error('file')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

@auth
@if(!auth()->user()->canModerate())
<p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
Your upload will be reviewed by a Creator before going live.
</p>
@endif
@endauth

<button type="submit" class="w-full bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700">
Submit
</button>
</form>
</div>

@endsection
