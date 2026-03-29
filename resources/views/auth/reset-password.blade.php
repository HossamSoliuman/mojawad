@extends('layouts.guest')
@section('title', 'Reset Password')
@section('content')

<h2 class="text-xl font-bold text-gray-900 mb-6 text-center">Set new password</h2>

<form method="POST" action="{{ route('password.store') }}" class="space-y-4">
@csrf
<input type="hidden" name="token" value="{{ $request->route('token') }}">

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
<input type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus
class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('email') border-red-400 @enderror">
@error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">New password</label>
<input type="password" name="password" required autocomplete="new-password"
class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('password') border-red-400 @enderror">
@error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
<input type="password" name="password_confirmation" required autocomplete="new-password"
class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
</div>

<button type="submit"
class="w-full bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors">
Reset password
</button>
</form>

@endsection
