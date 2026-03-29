@extends('layouts.app')
@section('title', 'Notifications')
@section('content')

<h1 class="text-2xl font-bold text-gray-900 mb-6">Notifications</h1>

@if($notifications->isEmpty())
<div class="text-center py-20 text-gray-400">No notifications yet.</div>
@else
<div class="space-y-3 max-w-2xl">
@foreach($notifications as $notification)
<div class="bg-white border border-gray-200 rounded-xl p-4 flex items-start justify-between gap-4 {{ $notification->read_at ? 'opacity-60' : '' }}">
<div class="flex items-start gap-3">
@if(!$notification->read_at)
<span class="w-2 h-2 bg-emerald-500 rounded-full mt-1.5 flex-shrink-0"></span>
@else
<span class="w-2 h-2 flex-shrink-0"></span>
@endif
<div>
<p class="text-sm text-gray-800">{{ $notification->data['message'] ?? 'Notification' }}</p>
<p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
</div>
</div>
@if(!$notification->read_at)
<form method="POST" action="{{ route('notifications.read', $notification->id) }}">
@csrf
<button type="submit" class="text-xs text-emerald-600 hover:underline flex-shrink-0">Mark read</button>
</form>
@endif
</div>
@endforeach
</div>
<div class="mt-6 max-w-2xl">{{ $notifications->links() }}</div>
@endif

@endsection
