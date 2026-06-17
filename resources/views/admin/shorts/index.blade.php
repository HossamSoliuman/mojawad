@extends('layouts.admin')
@section('title', __('Shorts'))
@section('page-title', __('Shorts'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Shorts') }}@endsection
@section('topbar-actions')
<a href="{{ route('admin.shorts.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('Add Short') }}</a>
@endsection
@section('content')

<form method="GET" class="filter-bar">
  <div class="f-search">
    <i class="fas fa-magnifying-glass"></i>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search shorts…') }}">
  </div>
  <select name="type" class="f-select" onchange="this.form.submit()">
    <option value="">{{ __('Type') }}: {{ __('All') }}</option>
    <option value="video" {{ request('type')==='video' ? 'selected':'' }}>{{ __('Video') }}</option>
    <option value="audio" {{ request('type')==='audio' ? 'selected':'' }}>{{ __('Audio') }}</option>
  </select>
  <select name="status" class="f-select" onchange="this.form.submit()">
    <option value="">{{ __('Status') }}: {{ __('All') }}</option>
    <option value="active"   {{ request('status')==='active'   ? 'selected':'' }}>{{ __('Active') }}</option>
    <option value="inactive" {{ request('status')==='inactive' ? 'selected':'' }}>{{ __('Inactive') }}</option>
  </select>
  @if(request('search') || request('status') || request('type'))
  <a href="{{ route('admin.shorts.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-xmark"></i> {{ __('Clear') }}</a>
  @endif
</form>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr>
      <th>{{ __('Short') }}</th><th>{{ __('Type') }}</th><th>{{ __('Order') }}</th>
      <th>{{ __('Status') }}</th><th>{{ __('Added') }}</th><th>{{ __('Actions') }}</th>
    </tr></thead>
    <tbody>
      @forelse($shorts as $s)
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:.72rem">
            <span style="width:36px;height:48px;border-radius:8px;background:var(--surface2,var(--surface));display:grid;place-items:center;border:1px solid var(--border2);flex-shrink:0;overflow:hidden">
              @if($s->poster_url)
              <img src="{{ $s->poster_url }}" style="width:100%;height:100%;object-fit:cover" alt="">
              @else
              <i class="fas {{ $s->type === 'video' ? 'fa-clapperboard' : 'fa-music' }}" style="color:var(--gold)"></i>
              @endif
            </span>
            <div style="font-weight:600;font-size:.88rem">{{ $s->title }}</div>
          </div>
        </td>
        <td>
          <span class="badge badge-muted">
            <i class="fas {{ $s->type === 'video' ? 'fa-video' : 'fa-volume-high' }}"></i>
            {{ $s->type === 'video' ? __('Video') : __('Audio') }}
          </span>
        </td>
        <td><span class="badge badge-muted">{{ $s->sort_order }}</span></td>
        <td>
          <span class="badge {{ $s->status === 'active' ? 'badge-success' : 'badge-muted' }}">
            {{ $s->status === 'active' ? __('Active') : __('Inactive') }}
          </span>
        </td>
        <td style="font-size:.8rem;color:var(--text2)">{{ $s->created_at->format('d M Y') }}</td>
        <td>
          <div style="display:flex;gap:.18rem">
            <a href="{{ route('admin.shorts.edit',$s) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="fas fa-pen-to-square"></i></a>
            <form method="POST" action="{{ route('admin.shorts.destroy',$s) }}" onsubmit="return confirm(@json(__('Delete this short?')))">
              @csrf @method('DELETE')
              <button type="submit" class="btn-icon" style="color:var(--red)" title="{{ __('Delete') }}"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text2)">
        <i class="fas fa-clapperboard" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.4"></i> {{ __('No shorts found.') }}
      </td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div>{{ $shorts->links('vendor.pagination.custom') }}</div>
@endsection
