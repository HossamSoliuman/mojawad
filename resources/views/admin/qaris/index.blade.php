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

<div class="qari-cards">
  @forelse($qaris as $q)
  <div class="qari-card">
    {{-- Featured toggle --}}
    <form method="POST" action="{{ route('admin.qaris.update',$q) }}">
      @csrf @method('PUT')
      <input type="hidden" name="name_ar" value="{{ $q->name_ar }}">
      <input type="hidden" name="name_en" value="{{ $q->name_en }}">
      <input type="hidden" name="biography_ar" value="{{ $q->biography_ar }}">
      <input type="hidden" name="biography_en" value="{{ $q->biography_en }}">
      <input type="hidden" name="status" value="{{ $q->status }}">
      <input type="hidden" name="is_featured" value="{{ $q->is_featured ? '0':'1' }}">
      <button type="submit" class="qari-card-fav" style="color:{{ $q->is_featured?'var(--gold)':'var(--text3)' }}" title="{{ __('Toggle featured') }}">
        <i class="fas fa-star"></i>
      </button>
    </form>

    <img class="qari-card-img" src="{{ $q->image_url }}" alt="{{ $q->name }}">
    <div class="qari-card-name">{{ $q->name }}</div>
    <div class="qari-card-slug">/qari/{{ $q->slug }}</div>

    <div class="qari-card-meta">
      <span class="badge badge-muted"><i class="fas fa-music"></i> {{ $q->tilawat_count }} {{ __('tilawat') }}</span>
    </div>

    {{-- Status --}}
    <form method="POST" action="{{ route('admin.qaris.update',$q) }}" style="width:100%">
      @csrf @method('PUT')
      <input type="hidden" name="name_ar" value="{{ $q->name_ar }}">
      <input type="hidden" name="name_en" value="{{ $q->name_en }}">
      <input type="hidden" name="biography_ar" value="{{ $q->biography_ar }}">
      <input type="hidden" name="biography_en" value="{{ $q->biography_en }}">
      <input type="hidden" name="is_featured" value="{{ $q->is_featured ? '1':'0' }}">
      <select name="status" onchange="this.form.submit()" class="f-select f-select-sm" style="width:100%">
        <option value="active"   {{ $q->status==='active'  ?'selected':'' }}>{{ __('Active') }}</option>
        <option value="pending"  {{ $q->status==='pending' ?'selected':'' }}>{{ __('Pending') }}</option>
        <option value="inactive" {{ $q->status==='inactive'?'selected':'' }}>{{ __('Inactive') }}</option>
      </select>
    </form>

    <div class="qari-card-date">{{ __('Added') }} {{ $q->created_at->format('d M Y') }}</div>

    <div class="qari-card-actions">
      <a href="{{ route('qaris.show',$q) }}" target="_blank" class="btn-icon" title="{{ __('View Site') }}"><i class="fas fa-arrow-up-right-from-square"></i></a>
      <a href="{{ route('admin.qaris.edit',$q) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="fas fa-pen-to-square"></i></a>
      <form method="POST" action="{{ route('admin.qaris.destroy',$q) }}" onsubmit="return confirm(@json(__('Delete this qari? This also deletes all their tilawat.')))">
        @csrf @method('DELETE')
        <button type="submit" class="btn-icon" style="color:var(--red)" title="{{ __('Delete') }}"><i class="fas fa-trash"></i></button>
      </form>
    </div>
  </div>
  @empty
  <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text2)">
    <i class="fas fa-magnifying-glass" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.4"></i> {{ __('No qaris found.') }}
  </div>
  @endforelse
</div>
<div>{{ $qaris->links('vendor.pagination.custom') }}</div>
@endsection
