@extends('layouts.admin')
@section('title','Users')
@section('page-title','Users')
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">Dashboard</a> › Users @endsection
@section('content')

<form method="GET" style="margin-bottom:1.4rem">
  <div style="position:relative;display:inline-block">
    <i class="fas fa-magnifying-glass" style="position:absolute;left:.82rem;top:50%;transform:translateY(-50%);color:var(--text3);font-size:.78rem;pointer-events:none"></i>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search users…"
      style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--r);color:var(--text);padding:.58rem 1rem .58rem 2.2rem;font-size:.86rem;width:275px;outline:none;font-family:'Crimson Pro',serif"
      onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='var(--border)'"
      oninput="this.form.submit()">
  </div>
</form>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
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
          <span class="badge {{ $u->hasRole('admin')?'badge-gold':($u->hasRole('creator')?'badge-green':'badge-muted') }}">
            {{ $u->roles->first()?->name ?? 'user' }}
          </span>
        </td>
        <td style="font-size:.8rem;color:var(--text2)">{{ $u->created_at->format('d M Y') }}</td>
        <td>
          <form method="POST" action="{{ route('admin.users.role',$u) }}" style="display:flex;align-items:center;gap:.45rem">
            @csrf @method('PUT')
            <select name="role" style="background:var(--surface2);border:1px solid var(--border);border-radius:7px;padding:.26rem .55rem;color:var(--text);font-size:.8rem;cursor:pointer">
              @foreach($roles as $r)
              <option value="{{ $r->name }}" {{ $u->hasRole($r->name)?'selected':'' }}>{{ ucfirst($r->name) }}</option>
              @endforeach
            </select>
            <button type="submit" class="btn-icon" title="Save role"><i class="fas fa-check"></i></button>
          </form>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;padding:3rem;color:var(--text2)">No users found.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div>{{ $users->links('vendor.pagination.custom') }}</div>
@endsection
