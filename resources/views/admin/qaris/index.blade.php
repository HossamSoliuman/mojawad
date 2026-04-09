@extends('layouts.admin')
@section('title','Qaris')
@section('page-title','Qaris')
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">Dashboard</a> › Qaris@endsection
@section('topbar-actions')
<a href="{{ route('admin.qaris.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Qari</a>
@endsection
@section('content')

<form method="GET" style="display:flex;align-items:center;gap:.65rem;margin-bottom:1.4rem;flex-wrap:wrap">
  <div style="position:relative">
    <i class="fas fa-magnifying-glass" style="position:absolute;left:.82rem;top:50%;transform:translateY(-50%);color:var(--text3);font-size:.78rem;pointer-events:none"></i>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search qaris…"
      style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--r);color:var(--text);padding:.58rem 1rem .58rem 2.2rem;font-size:.86rem;width:235px;outline:none;font-family:'Crimson Pro',serif"
      onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='var(--border)'">
  </div>
  <select name="status" onchange="this.form.submit()"
    style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--r);color:var(--text);padding:.58rem .88rem;font-size:.86rem;cursor:pointer">
    <option value="">All Status</option>
    <option value="active"   {{ request('status')==='active'   ? 'selected':'' }}>Active</option>
    <option value="pending"  {{ request('status')==='pending'  ? 'selected':'' }}>Pending</option>
    <option value="inactive" {{ request('status')==='inactive' ? 'selected':'' }}>Inactive</option>
  </select>
  @if(request('search') || request('status'))
  <a href="{{ route('admin.qaris.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-xmark"></i> Clear</a>
  @endif
</form>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>Qari</th><th>Tilawat</th><th>Status</th><th>Featured</th><th>Added</th><th>Actions</th></tr></thead>
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
            <select name="status" onchange="this.form.submit()"
              style="background:var(--surface2);border:1px solid var(--border);border-radius:7px;padding:.26rem .55rem;color:var(--text);font-size:.8rem;cursor:pointer">
              <option value="active"   {{ $q->status==='active'  ?'selected':'' }}>Active</option>
              <option value="pending"  {{ $q->status==='pending' ?'selected':'' }}>Pending</option>
              <option value="inactive" {{ $q->status==='inactive'?'selected':'' }}>Inactive</option>
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
            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:1.05rem;color:{{ $q->is_featured?'var(--gold)':'var(--text3)' }}" title="Toggle featured">
              <i class="fas fa-star"></i>
            </button>
          </form>
        </td>
        <td style="font-size:.8rem;color:var(--text2)">{{ $q->created_at->format('d M Y') }}</td>
        <td>
          <div style="display:flex;gap:.18rem">
            <a href="{{ route('qaris.show',$q) }}" target="_blank" class="btn-icon" title="View"><i class="fas fa-arrow-up-right-from-square"></i></a>
            <a href="{{ route('admin.qaris.edit',$q) }}" class="btn-icon" title="Edit"><i class="fas fa-pen-to-square"></i></a>
            <form method="POST" action="{{ route('admin.qaris.destroy',$q) }}" onsubmit="return confirm('Delete {{ addslashes($q->name) }}? This also deletes all tilawat.')">
              @csrf @method('DELETE')
              <button type="submit" class="btn-icon" style="color:var(--red)" title="Delete"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text2)">
        <i class="fas fa-magnifying-glass" style="font-size:1.5rem;display:block;margin-bottom:.5rem"></i> No qaris found.
      </td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div>{{ $qaris->links('vendor.pagination.custom') }}</div>
@endsection
