@extends('layouts.app')
@section('title', 'Apply to be a Creator')
@section('content')

    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Apply to be a Creator</h1>
        <p class="text-gray-500 text-sm mb-6">Creators can upload recitations directly and review submissions from other
            users.</p>

        <form method="POST" action="{{ route('creator.apply.store') }}"
            class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Why do you want to become a Creator?</label>
                <p class="text-xs text-gray-400 mb-2">Minimum 50 characters.</p>
                <textarea name="reason" rows="6"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    placeholder="I want to contribute to the platform because...">{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                class="w-full bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700">Submit
                Application</button>
        </form>
    </div>

@endsection
