@extends('layouts.guest')
@section('title', 'Confirm Password')
@section('content')

<h2 class="text-xl font-bold text-gray-900 mb-2 text-center">Confirm your password</h2>
<p class="text-sm text-gray-500 text-center mb-6">
This is a secure area. Please confirm your password before continuing.
</p>

<form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
@csrf

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
<input type="password" name="password" required autocomplete="current-password"
class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('password') border-red-400 @enderror">
@error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<button type="submit"
class="w-full bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors">
Confirm
</button>
</form>

@endsection
