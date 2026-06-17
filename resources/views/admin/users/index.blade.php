@extends('layouts.admin')
@section('title', __('Users'))
@section('page-title', __('Users'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Users') }}@endsection
@section('topbar-actions')
<a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('Add User') }}</a>
@endsection
@section('content')

<form method="GET" class="filter-bar">
  <div class="f-search">
    <i class="fas fa-magnifying-glass"></i>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search users…') }}">
  </div>
  <select name="role" class="f-select" onchange="this.form.submit()">
    <option value="">{{ __('Role') }}: {{ __('All') }}</option>
    @foreach($roles as $r)
    <option value="{{ $r->name }}" {{ request('role') === $r->name ? 'selected' : '' }}>{{ __(ucfirst($r->name)) }}</option>
    @endforeach
  </select>
  @if(request('search') || request('role'))
  <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-xmark"></i> {{ __('Clear') }}</a>
  @endif
</form>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr>
      <th>{{ __('User') }}</th><th>{{ __('Email') }}</th><th>{{ __('Role') }}</th>
      <th>{{ __('Joined') }}</th><th>{{ __('Actions') }}</th>
    </tr></thead>
    <tbody>
      @forelse($users as $u)
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:.72rem">
            <img src="{{ $u->avatar_url }}" class="avatar" width="32" height="32" alt="">
            <span style="font-weight:600;font-size:.88rem">{{ $u->name }}</span>
          </div>
        </td>
        <td style="font-size:.84rem;color:var(--text2)">{{ $u->email }}</td>
        <td>
          <span class="badge {{ $u->hasRole('admin')?'badge-gold':($u->hasRole('creator')?'badge-green':($u->hasRole('reviewer')?'badge-blue':'badge-muted')) }}">
            {{ __(ucfirst($u->roles->first()?->name ?? 'user')) }}
          </span>
        </td>
        <td style="font-size:.8rem;color:var(--text2)">{{ $u->created_at->format('d M Y') }}</td>
        <td>
          <div style="display:flex;align-items:center;gap:.75rem">
            <form method="POST" action="{{ route('admin.users.role',$u) }}" style="display:flex;align-items:center;gap:.45rem">
              @csrf @method('PUT')
              <select name="role" class="f-select f-select-sm">
                @foreach($roles as $r)
                <option value="{{ $r->name }}" {{ $u->hasRole($r->name)?'selected':'' }}>{{ __(ucfirst($r->name)) }}</option>
                @endforeach
              </select>
              <button type="submit" class="btn-icon" title="{{ __('Save role') }}"><i class="fas fa-check"></i></button>
            </form>
            @if($u->id !== auth()->id())
            <form method="POST" action="{{ route('admin.users.destroy',$u) }}" onsubmit="return confirm(@json(__('Delete this user?')))">
              @csrf @method('DELETE')
              <button type="submit" class="btn-icon" style="color:var(--red)" title="{{ __('Delete') }}"><i class="fas fa-trash"></i></button>
            </form>
            @endif
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;padding:3rem;color:var(--text2)">{{ __('No users found.') }}</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div>{{ $users->links('vendor.pagination.custom') }}</div>
@endsection
