@extends('layouts.admin')
@section('title','Tilawat')
@section('page-title','Tilawat')
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">Dashboard</a> › Tilawat@endsection
@section('topbar-actions')
<a href="{{ route('admin.tilawat.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Tilawa</a>
@endsection
@section('content')

<form method="GET" style="display:flex;align-items:center;gap:.65rem;margin-bottom:1.4rem;flex-wrap:wrap">
  <div style="position:relative">
    <i class="fas fa-magnifying-glass" style="position:absolute;left:.82rem;top:50%;transform:translateY(-50%);color:var(--text3);font-size:.78rem;pointer-events:none"></i>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tilawat…"
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
  <a href="{{ route('admin.tilawat.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-xmark"></i> Clear</a>
  @endif
</form>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>Tilawa</th><th>Qari</th><th>Duration</th><th>Status</th><th>Featured</th><th>Stats</th><th>Actions</th></tr></thead>
    <tbody>
      @forelse($tilawat as $t)
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:.72rem">
            <img src="{{ $t->cover_url }}" style="width:36px;height:36px;border-radius:7px;object-fit:cover;flex-shrink:0" alt="">
            <span style="font-weight:600;font-size:.88rem">{{ $t->title }}</span>
          </div>
        </td>
        <td style="color:var(--gold);font-size:.85rem">{{ $t->qari->name }}</td>
        <td style="font-size:.8rem;color:var(--text2);font-family:monospace">{{ $t->formatted_duration }}</td>
        <td>
          <form method="POST" action="{{ route('admin.tilawat.update',$t) }}" style="display:inline">
            @csrf @method('PUT')
            <input type="hidden" name="qari_id" value="{{ $t->qari_id }}">
            <input type="hidden" name="title" value="{{ $t->title }}">
            <input type="hidden" name="is_featured" value="{{ $t->is_featured ? '1':'0' }}">
            <select name="status" onchange="this.form.submit()"
              style="background:var(--surface2);border:1px solid var(--border);border-radius:7px;padding:.26rem .55rem;color:var(--text);font-size:.8rem;cursor:pointer">
              <option value="active"   {{ $t->status==='active'  ?'selected':'' }}>Active</option>
              <option value="pending"  {{ $t->status==='pending' ?'selected':'' }}>Pending</option>
              <option value="inactive" {{ $t->status==='inactive'?'selected':'' }}>Inactive</option>
            </select>
          </form>
        </td>
        <td>
          <form method="POST" action="{{ route('admin.tilawat.update',$t) }}">
            @csrf @method('PUT')
            <input type="hidden" name="qari_id" value="{{ $t->qari_id }}">
            <input type="hidden" name="title" value="{{ $t->title }}">
            <input type="hidden" name="status" value="{{ $t->status }}">
            <input type="hidden" name="is_featured" value="{{ $t->is_featured ? '0':'1' }}">
            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:1.05rem;color:{{ $t->is_featured?'var(--gold)':'var(--text3)' }}" title="Toggle featured">
              <i class="fas fa-star"></i>
            </button>
          </form>
        </td>
        <td>
          <div style="font-size:.76rem;color:var(--text2);display:flex;flex-direction:column;gap:.12rem">
            <span><i class="fas fa-heart" style="color:var(--gold)"></i> {{ number_format($t->likes_count) }}</span>
            <span><i class="fas fa-download"></i> {{ number_format($t->downloads_count) }}</span>
          </div>
        </td>
        <td>
          <div style="display:flex;gap:.18rem">
            <button class="btn-icon" title="Preview"
              onclick="playTilawa({{ $t->id }},'{{ $t->audio_url }}',{{ json_encode($t->title) }},{{ json_encode($t->qari->name) }},'{{ $t->cover_url }}',{{ $t->duration }})">
              <i class="fas fa-play"></i>
            </button>
            <a href="{{ route('admin.tilawat.edit',$t) }}" class="btn-icon" title="Edit"><i class="fas fa-pen-to-square"></i></a>
            <form method="POST" action="{{ route('admin.tilawat.destroy',$t) }}" onsubmit="return confirm('Delete {{ addslashes($t->title) }}?')">
              @csrf @method('DELETE')
              <button type="submit" class="btn-icon" style="color:var(--red)" title="Delete"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text2)">
        <i class="fas fa-music-slash" style="font-size:1.5rem;display:block;margin-bottom:.5rem"></i> No tilawat found.
      </td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div>{{ $tilawat->links('vendor.pagination.custom') }}</div>
@endsection
