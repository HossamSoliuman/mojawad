@extends('layouts.admin')
@section('title','Dashboard')
@section('page-title','Dashboard')
@section('topbar-actions')
<a href="{{ route('admin.qaris.create') }}" class="btn btn-ghost btn-xs"><i class="fas fa-plus"></i> Qari</a>
<a href="{{ route('admin.tilawat.create') }}" class="btn btn-primary btn-xs"><i class="fas fa-plus"></i> Tilawa</a>
@endsection
@section('content')

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-microphone-lines"></i></div>
    <div>
      <div class="stat-val">{{ number_format($stats['total_qaris']) }}</div>
      <div class="stat-lbl">Qaris</div>
      @if($stats['pending_qaris']>0)
      <div style="margin-top:.38rem"><span class="badge badge-amber"><i class="fas fa-clock"></i> {{ $stats['pending_qaris'] }} pending</span></div>
      @endif
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-music"></i></div>
    <div>
      <div class="stat-val">{{ number_format($stats['total_tilawat']) }}</div>
      <div class="stat-lbl">Tilawat</div>
      @if($stats['pending_tilawat']>0)
      <div style="margin-top:.38rem"><span class="badge badge-amber"><i class="fas fa-clock"></i> {{ $stats['pending_tilawat'] }} pending</span></div>
      @endif
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-users"></i></div>
    <div><div class="stat-val">{{ number_format($stats['total_users']) }}</div><div class="stat-lbl">Users</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-download"></i></div>
    <div><div class="stat-val">{{ number_format($stats['total_downloads']) }}</div><div class="stat-lbl">Downloads</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-heart"></i></div>
    <div><div class="stat-val">{{ number_format($stats['total_likes']) }}</div><div class="stat-lbl">Likes</div></div>
  </div>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.05rem">
  <div class="sec-title" style="margin-bottom:0"><i class="fas fa-clock-rotate-left gold"></i> Recent Tilawat</div>
  <a href="{{ route('admin.tilawat.index') }}" class="btn btn-ghost btn-xs">View All</a>
</div>
<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>Title</th><th>Qari</th><th>By</th><th>Status</th><th>Date</th><th></th></tr></thead>
    <tbody>
      @foreach($recent_tilawat as $t)
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:.68rem">
            <img src="{{ $t->cover_url }}" style="width:32px;height:32px;border-radius:6px;object-fit:cover;flex-shrink:0" alt="">
            <span style="font-weight:600;font-size:.86rem">{{ $t->title }}</span>
          </div>
        </td>
        <td style="color:var(--gold);font-size:.85rem">{{ $t->qari->name }}</td>
        <td style="font-size:.82rem;color:var(--text2)">{{ $t->uploader->name }}</td>
        <td>
          <span class="badge {{ $t->status==='active'?'badge-green':($t->status==='pending'?'badge-amber':'badge-red') }}">
            {{ ucfirst($t->status) }}
          </span>
        </td>
        <td style="font-size:.78rem;color:var(--text2)">{{ $t->created_at->format('d M Y') }}</td>
        <td>
          <a href="{{ route('admin.tilawat.edit',$t) }}" class="btn-icon" title="Edit"><i class="fas fa-pen-to-square"></i></a>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
