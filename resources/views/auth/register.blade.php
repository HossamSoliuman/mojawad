@extends('layouts.auth')
@section('title', __('Register'))
@section('content')
<h2 style="font-size:1.35rem;margin-bottom:.4rem;text-align:center">{{ __('Create Account') }}</h2>
<p style="color:var(--text2);font-size:.92rem;margin-bottom:1.65rem;text-align:center">{{ __('Join the Mojawad community') }}</p>
@if($errors->any())
<div class="alert alert-error" style="flex-direction:column;align-items:stretch;gap:.35rem">
  @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
</div>
@endif
<form method="POST" action="{{ route('register') }}">
  @csrf
  <div class="form-group">
    <label class="form-label"><i class="fas fa-user"></i> {{ __('Name') }}</label>
    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required autofocus placeholder="{{ __('Your Name') }}">
  </div>
  <div class="form-group">
    <label class="form-label"><i class="fas fa-envelope"></i> {{ __('Email') }}</label>
    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="you@example.com">
  </div>
  <div class="form-group">
    <label class="form-label"><i class="fas fa-lock"></i> {{ __('Password') }}</label>
    <input type="password" name="password" class="form-control" required placeholder="{{ __('Min. 8 characters') }}">
  </div>
  <div class="form-group">
    <label class="form-label"><i class="fas fa-lock"></i> {{ __('Confirm Password') }}</label>
    <input type="password" name="password_confirmation" class="form-control" required placeholder="{{ __('Repeat password') }}">
  </div>
  <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:.4rem">
    <i class="fas fa-user-plus"></i> {{ __('Create Account') }}
  </button>
</form>
<p style="text-align:center;margin-top:1.25rem;font-size:.88rem;color:var(--text2)">
  {{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a>
</p>
@endsection
