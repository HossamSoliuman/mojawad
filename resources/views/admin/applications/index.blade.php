@extends('layouts.app')
@section('title', 'Creator Applications')
@section('content')

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Creator Applications</h1>

    @if ($applications->isEmpty())
        <div class="text-center py-20 text-gray-400">No pending applications.</div>
    @else
        <div class="space-y-4 max-w-3xl">
            @foreach ($applications as $application)
                <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="font-medium text-gray-900">{{ $application->user->name }}</p>
                        <p class="text-sm text-gray-500">{{ $application->user->email }}</p>
                        <p class="text-sm text-gray-700 mt-3 leading-relaxed">{{ $application->reason }}</p>
                        <p class="text-xs text-gray-400 mt-2">Applied {{ $application->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex flex-col gap-2 flex-shrink-0">
                        <form method="POST" action="{{ route('admin.applications.approve', $application) }}">
                            @csrf
                            <button type="submit"
                                class="w-full bg-emerald-600 text-white text-xs px-4 py-2 rounded-lg hover:bg-emerald-700">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.applications.reject', $application) }}">
                            @csrf
                            <button type="submit"
                                class="w-full bg-red-50 text-red-600 text-xs px-4 py-2 rounded-lg border border-red-200 hover:bg-red-100">Reject</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6 max-w-3xl">{{ $applications->links() }}</div>
    @endif

@endsection
