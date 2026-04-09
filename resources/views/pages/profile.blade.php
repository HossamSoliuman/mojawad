@extends('layouts.app')
@section('title','Profile')
@section('content')
<div class="page-hd"><div class="wrap"><h1><i class="fas fa-user gold"></i> Profile</h1><p>Your account</p></div></div>
<div class="wrap" style="padding-bottom:3rem;max-width:620px">
  <div class="card" style="padding:1.85rem;display:flex;align-items:center;gap:1.4rem;margin-bottom:1.4rem">
    <img src="{{ auth()->user()->avatar_url }}" class="avatar" width="76" height="76" alt="">
    <div>
      <h2 style="font-size:1.35rem;margin-bottom:.22rem">{{ auth()->user()->name }}</h2>
      <div style="color:var(--text2);font-size:.9rem">{{ auth()->user()->email }}</div>
      <div style="margin-top:.45rem">
        <span class="badge badge-gold">{{ auth()->user()->roles->first()?->name ?? 'user' }}</span>
      </div>
    </div>
  </div>
  <div class="card" style="padding:1.65rem">
    <h3 style="font-size:.95rem;margin-bottom:1.15rem"><i class="fas fa-chart-simple gold"></i> Activity</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;text-align:center">
      <div>
        <div style="font-family:'Cinzel',serif;font-size:1.7rem;font-weight:700;color:var(--gold)">
          {{ auth()->user()->likes()->count() }}
        </div>
        <div style="font-size:.78rem;color:var(--text2)">Liked</div>
      </div>
      <div>
        <div style="font-family:'Cinzel',serif;font-size:1.7rem;font-weight:700;color:var(--gold)">
          {{ auth()->user()->savedTilawat()->count() }}
        </div>
        <div style="font-size:.78rem;color:var(--text2)">Saved</div>
      </div>
      <div>
        <div style="font-family:'Cinzel',serif;font-size:1.7rem;font-weight:700;color:var(--gold)">
          {{ \App\Models\DownloadLog::where('user_id',auth()->id())->count() }}
        </div>
        <div style="font-size:.78rem;color:var(--text2)">Downloads</div>
      </div>
    </div>
  </div>
</div>
@endsection
