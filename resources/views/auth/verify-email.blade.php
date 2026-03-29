@extends('layouts.guest')
@section('title', 'Verify Email')
@section('content')

<h2 class="text-xl font-bold text-gray-900 mb-2 text-center">Verify your email</h2>
<p class="text-sm text-gray-500 text-center mb-6">
We sent a verification link to your email address. Click it to continue.
</p>

@if(session('status') == 'verification-link-sent')
<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm text-center">
A new verification link has been sent.
</div>
@endif

<form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
@csrf
<button type="submit"
class="w-full bg-emerald-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors">
Resend verification email
</button>
</form>

<form method="POST" action="{{ route('logout') }}" class="mt-3">
@csrf
<button type="submit" class="w-full text-sm text-gray-400 hover:text-gray-600">Logout</button>
</form>

@endsection
