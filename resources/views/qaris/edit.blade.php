@extends('layouts.app')
@section('title', 'Edit ' . $qari->name_en)
@section('content')

    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Qari</h1>
        <form method="POST" action="{{ route('qaris.update', $qari) }}" enctype="multipart/form-data"
            class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name (English)</label>
                <input type="text" name="name_en" value="{{ old('name_en', $qari->name_en) }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                @error('name_en')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name (Arabic)</label>
                <input type="text" name="name_ar" value="{{ old('name_ar', $qari->name_ar) }}" dir="rtl"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                @error('name_ar')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Biography</label>
                <textarea name="bio" rows="4"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">{{ old('bio', $qari->bio) }}</textarea>
                @error('bio')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                @if ($qari->photo)
                    <img src="{{ Storage::url($qari->photo) }}" class="w-14 h-14 rounded-full object-cover mb-2">
                @endif
                <input type="file" name="photo" accept="image/*" class="w-full text-sm text-gray-500">
                @error('photo')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                class="w-full bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700">Update
                Qari</button>
        </form>
    </div>

@endsection
