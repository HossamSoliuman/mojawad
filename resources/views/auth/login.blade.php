@extends('layouts.guest')
@section('title', 'Login')
@section('content')

<h2 class="text-xl font-bold text-gray-900 mb-6 text-center">Welcome back</h2>

@if(session('status'))
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm">
{{ session('status') }}
</div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-4">
@csrf

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
<input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('email') border-red-400 @enderror">
@error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div>
<div class="flex items-center justify-between mb-1">
<label class="block text-sm font-medium text-gray-700">Password</label>
@if(Route::has('password.request'))
<a href="{{ route('password.request') }}" class="text-xs text-emerald-600 hover:underline">Forgot password?</a>
@endif
</div>
<input type="password" name="password" required autocomplete="current-password"
class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('password') border-red-400 @enderror">
@error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div class="flex items-center gap-2">
<input type="checkbox" name="remember" id="remember" class="rounded text-emerald-600">
<label for="remember" class="text-sm text-gray-600">Remember me</label>
</div>

<button type="submit"
class="w-full bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors">
Login
</button>

<p class="text-center text-sm text-gray-500">
Don&apos;t have an account?
<a href="{{ route('register') }}" class="text-emerald-600 hover:underline font-medium">Register</a>
</p>
</form>

@endsection
