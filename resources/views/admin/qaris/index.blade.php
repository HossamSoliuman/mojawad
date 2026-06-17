@extends('layouts.admin')
@section('title', __('Qaris'))
@section('page-title', __('Qaris'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Qaris') }}@endsection
@section('topbar-actions')
<a href="{{ route('admin.qaris.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('Add Qari') }}</a>
@endsection
@section('content')

<form method="GET" class="filter-bar">
  <div class="f-search">
    <i class="fas fa-magnifying-glass"></i>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search qaris…') }}">
  </div>
  <select name="status" class="f-select" onchange="this.form.submit()">
    <option value="">{{ __('Status') }}: {{ __('All') }}</option>
    <option value="active"   {{ request('status')==='active'   ? 'selected':'' }}>{{ __('Active') }}</option>
    <option value="pending"  {{ request('status')==='pending'  ? 'selected':'' }}>{{ __('Pending') }}</option>
    <option value="inactive" {{ request('status')==='inactive' ? 'selected':'' }}>{{ __('Inactive') }}</option>
  </select>
  @if(request('search') || request('status'))
  <a href="{{ route('admin.qaris.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-xmark"></i> {{ __('Clear') }}</a>
  @endif
</form>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr>
      <th>{{ __('Qari') }}</th><th>{{ __('Tilawat') }}</th><th>{{ __('Status') }}</th>
      <th>{{ __('Featured') }}</th><th>{{ __('Added') }}</th><th>{{ __('Actions') }}</th>
    </tr></thead>
    <tbody>
      @forelse($qaris as $q)
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:.72rem">
            <img src="{{ $q->image_url }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--border2);flex-shrink:0" alt="">
            <div>
              <div style="font-weight:600;font-size:.88rem">{{ $q->name }}</div>
              <div style="font-size:.72rem;color:var(--text3)">/qari/{{ $q->slug }}</div>
            </div>
          </div>
        </td>
        <td><span class="badge badge-muted">{{ $q->tilawat_count }}</span></td>
        <td>
          <form method="POST" action="{{ route('admin.qaris.update',$q) }}" style="display:inline">
            @csrf @method('PUT')
            <input type="hidden" name="name" value="{{ $q->name }}">
            <input type="hidden" name="biography" value="{{ $q->biography }}">
            <input type="hidden" name="is_featured" value="{{ $q->is_featured ? '1':'0' }}">
            <select name="status" onchange="this.form.submit()" class="f-select f-select-sm">
              <option value="active"   {{ $q->status==='active'  ?'selected':'' }}>{{ __('Active') }}</option>
              <option value="pending"  {{ $q->status==='pending' ?'selected':'' }}>{{ __('Pending') }}</option>
              <option value="inactive" {{ $q->status==='inactive'?'selected':'' }}>{{ __('Inactive') }}</option>
            </select>
          </form>
        </td>
        <td>
          <form method="POST" action="{{ route('admin.qaris.update',$q) }}">
            @csrf @method('PUT')
            <input type="hidden" name="name" value="{{ $q->name }}">
            <input type="hidden" name="biography" value="{{ $q->biography }}">
            <input type="hidden" name="status" value="{{ $q->status }}">
            <input type="hidden" name="is_featured" value="{{ $q->is_featured ? '0':'1' }}">
            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:1.05rem;color:{{ $q->is_featured?'var(--gold)':'var(--text3)' }}" title="{{ __('Toggle featured') }}">
              <i class="fas fa-star"></i>
            </button>
          </form>
        </td>
        <td style="font-size:.8rem;color:var(--text2)">{{ $q->created_at->format('d M Y') }}</td>
        <td>
          <div style="display:flex;gap:.18rem">
            <a href="{{ route('qaris.show',$q) }}" target="_blank" class="btn-icon" title="{{ __('View Site') }}"><i class="fas fa-arrow-up-right-from-square"></i></a>
            <a href="{{ route('admin.qaris.edit',$q) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="fas fa-pen-to-square"></i></a>
            <form method="POST" action="{{ route('admin.qaris.destroy',$q) }}" onsubmit="return confirm(@json(__('Delete this qari? This also deletes all their tilawat.')))">
              @csrf @method('DELETE')
              <button type="submit" class="btn-icon" style="color:var(--red)" title="{{ __('Delete') }}"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text2)">
        <i class="fas fa-magnifying-glass" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.4"></i> {{ __('No qaris found.') }}
      </td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div>{{ $qaris->links('vendor.pagination.custom') }}</div>
@endsection
