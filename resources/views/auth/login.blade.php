@extends('layouts.auth')
@section('title', __('Login'))
@section('content')
<h2 style="font-size:1.35rem;margin-bottom:.4rem;text-align:center">{{ __('Welcome Back') }}</h2>
<p style="color:var(--text2);font-size:.92rem;margin-bottom:1.65rem;text-align:center">{{ __('Sign in to your Mojawad account') }}</p>
@if($errors->any())
<div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <span>{{ $errors->first() }}</span></div>
@endif
<form method="POST" action="{{ route('login') }}">
  @csrf
  <div class="form-group">
    <label class="form-label"><i class="fas fa-envelope"></i> {{ __('Email') }}</label>
    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
  </div>
  <div class="form-group">
    <label class="form-label"><i class="fas fa-lock"></i> {{ __('Password') }}</label>
    <input type="password" name="password" class="form-control" required placeholder="••••••••">
  </div>
  <label style="display:flex;align-items:center;gap:.5rem;margin-bottom:1.35rem;cursor:pointer;font-size:.88rem;color:var(--text2)">
    <input type="checkbox" name="remember" style="accent-color:var(--gold)"> {{ __('Remember me') }}
  </label>
  <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
    <i class="fas fa-arrow-right-to-bracket"></i> {{ __('Sign In') }}
  </button>
</form>
<p style="text-align:center;margin-top:1.25rem;font-size:.88rem;color:var(--text2)">
  {{ __('No account?') }} <a href="{{ route('register') }}">{{ __('Create one') }}</a>
</p>
@endsection
