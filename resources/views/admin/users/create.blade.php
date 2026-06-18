@extends('layouts.admin')
@section('title', __('Add User'))
@section('page-title', __('Add New User'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › <a href="{{ route('admin.users.index') }}">{{ __('Users') }}</a> › {{ __('Create') }} @endsection
@section('content')

<div style="max-width:500px">
  <form method="POST" action="{{ route('admin.users.store') }}">
    @csrf

    @if($errors->any())
    <div class="alert alert-error" style="margin-bottom:1.25rem">
      @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
    </div>
    @endif

    <div class="form-group">
      <label class="form-label"><i class="fas fa-user"></i> {{ __('Name') }} <span style="color:var(--red)">*</span></label>
      <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="{{ __('User Name') }}" autofocus>
      @error('name')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fas fa-envelope"></i> {{ __('Email Address') }} <span style="color:var(--red)">*</span></label>
      <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="email@example.com">
      @error('email')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fas fa-key"></i> {{ __('Password') }} <span style="color:var(--red)">*</span></label>
      <input type="password" name="password" class="form-control" required placeholder="••••••••">
      @error('password')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fas fa-circle-check"></i> {{ __('Assign Role') }} <span style="color:var(--red)">*</span></label>
      <select name="role" class="form-control" required>
        <option value="" disabled selected>{{ __('Select Role') }}</option>
        @foreach($roles as $r)
        <option value="{{ $r->name }}" {{ old('role') === $r->name ? 'selected' : '' }}>{{ ucfirst($r->name) }}</option>
        @endforeach
      </select>
      @error('role')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div style="display:flex;gap:.65rem;margin-top:1.4rem">
      <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> {{ __('Create User') }}</button>
      <a href="{{ route('admin.users.index') }}" class="btn btn-ghost"><i class="fas fa-xmark"></i> {{ __('Cancel') }}</a>
    </div>
  </form>
</div>
@endsection
