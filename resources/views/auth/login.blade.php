@extends('layouts.auth')
@section('title','Login')
@section('content')
<h2 style="font-size:1.3rem;margin-bottom:.4rem">Welcome Back</h2>
<p style="color:var(--text2);font-size:.9rem;margin-bottom:1.65rem">Sign in to your Tilawa account</p>
@if($errors->any())
<div class="alert alert-error" style="margin-bottom:1.1rem"><i class="fas fa-circle-exclamation"></i> {{ $errors->first() }}</div>
@endif
<form method="POST" action="{{ route('login') }}">
  @csrf
  <div class="form-group">
    <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
  </div>
  <div class="form-group">
    <label class="form-label"><i class="fas fa-lock"></i> Password</label>
    <input type="password" name="password" class="form-control" required placeholder="••••••••">
  </div>
  <label style="display:flex;align-items:center;gap:.5rem;margin-bottom:1.35rem;cursor:pointer;font-size:.88rem;color:var(--text2)">
    <input type="checkbox" name="remember" style="accent-color:var(--gold)"> Remember me
  </label>
  <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
    <i class="fas fa-arrow-right-to-bracket"></i> Sign In
  </button>
</form>
<p style="text-align:center;margin-top:1.25rem;font-size:.88rem;color:var(--text2)">
  No account? <a href="{{ route('register') }}">Create one</a>
</p>
@endsection
