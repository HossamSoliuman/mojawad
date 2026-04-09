@extends('layouts.auth')
@section('title','Register')
@section('content')
<h2 style="font-size:1.3rem;margin-bottom:.4rem">Create Account</h2>
<p style="color:var(--text2);font-size:.9rem;margin-bottom:1.65rem">Join the Tilawa community</p>
@if($errors->any())
<div class="alert alert-error" style="margin-bottom:1.1rem">
  @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
</div>
@endif
<form method="POST" action="{{ route('register') }}">
  @csrf
  <div class="form-group">
    <label class="form-label"><i class="fas fa-user"></i> Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required autofocus placeholder="Your Name">
  </div>
  <div class="form-group">
    <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="you@example.com">
  </div>
  <div class="form-group">
    <label class="form-label"><i class="fas fa-lock"></i> Password</label>
    <input type="password" name="password" class="form-control" required placeholder="Min. 8 characters">
  </div>
  <div class="form-group">
    <label class="form-label"><i class="fas fa-lock"></i> Confirm Password</label>
    <input type="password" name="password_confirmation" class="form-control" required placeholder="Repeat password">
  </div>
  <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:.4rem">
    <i class="fas fa-user-plus"></i> Create Account
  </button>
</form>
<p style="text-align:center;margin-top:1.25rem;font-size:.88rem;color:var(--text2)">
  Already have an account? <a href="{{ route('login') }}">Sign in</a>
</p>
@endsection
