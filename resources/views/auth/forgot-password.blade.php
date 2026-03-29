@extends('layouts.guest')
@section('title', 'Forgot Password')
@section('content')

<h2 class="text-xl font-bold text-gray-900 mb-2 text-center">Forgot your password?</h2>
<p class="text-sm text-gray-500 text-center mb-6">Enter your email and we will send you a reset link.</p>

@if(session('status'))
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm">
{{ session('status') }}
</div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
@csrf

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
<input type="email" name="email" value="{{ old('email') }}" required autofocus
class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('email') border-red-400 @enderror">
@error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>

<button type="submit"
class="w-full bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors">
Send reset link
</button>

<p class="text-center text-sm text-gray-500">
<a href="{{ route('login') }}" class="text-emerald-600 hover:underline">Back to login</a>
</p>
</form>

@endsection
