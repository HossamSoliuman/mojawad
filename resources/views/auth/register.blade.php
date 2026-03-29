@extends('layouts.guest')
@section('title', 'Register')
@section('content')

<h2 class="text-xl font-bold text-gray-900 mb-6 text-center">Create an account</h2>

<form method="POST" action="{{ route('register') }}" class="space-y-4">
@csrf

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
<input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('name') border-red-400 @enderror">
@error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
<input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('email') border-red-400 @enderror">
@error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
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
Create account
</button>

<p class="text-center text-sm text-gray-500">
Already have an account?
<a href="{{ route('login') }}" class="text-emerald-600 hover:underline font-medium">Login</a>
</p>
</form>

@endsection
