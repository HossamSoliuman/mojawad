@extends('layouts.app')
@section('title', __('Profile'))
@section('content')
<div class="page-hd"><div class="wrap"><h1><i class="fas fa-user gold"></i> {{ __('Profile') }}</h1><p>{{ __('Your account') }}</p></div></div>
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
    <h3 style="font-size:.95rem;margin-bottom:1.15rem"><i class="fas fa-chart-simple gold"></i> {{ __('Activity') }}</h3>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;text-align:center">
      <a href="{{ route('likes') }}" wire:navigate style="color:inherit">
        <div style="font-family:'Cinzel',serif;font-size:1.7rem;font-weight:700;color:var(--gold)">
          {{ number_format($likedCount) }}
        </div>
        <div style="font-size:.78rem;color:var(--text2)">{{ __('Liked') }}</div>
      </a>
      <a href="{{ route('saved') }}" wire:navigate style="color:inherit">
        <div style="font-family:'Cinzel',serif;font-size:1.7rem;font-weight:700;color:var(--gold)">
          {{ number_format($savedCount) }}
        </div>
        <div style="font-size:.78rem;color:var(--text2)">{{ __('Saved') }}</div>
      </a>
      <div>
        <div style="font-family:'Cinzel',serif;font-size:1.7rem;font-weight:700;color:var(--gold)">
          {{ number_format($watchedCount) }}
        </div>
        <div style="font-size:.78rem;color:var(--text2)">{{ __('Watched') }}</div>
      </div>
      <div>
        <div style="font-family:'Cinzel',serif;font-size:1.7rem;font-weight:700;color:var(--gold)">
          {{ number_format($downloadsCount) }}
        </div>
        <div style="font-size:.78rem;color:var(--text2)">{{ __('Downloads') }}</div>
      </div>
    </div>

    @if($listeningTime || $topQari)
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1.5rem">
      @if($listeningTime)
      <div style="display:flex;align-items:center;gap:.75rem;padding:.85rem 1rem;background:var(--gold-faint);border-radius:var(--r)">
        <i class="fas fa-headphones gold" style="font-size:1.2rem"></i>
        <div>
          <div style="font-size:.72rem;color:var(--text2)">{{ __('Listening time') }}</div>
          <div style="font-weight:600">{{ $listeningTime }}</div>
        </div>
      </div>
      @endif
      @if($topQari)
      <a href="{{ route('qaris.show', $topQari) }}" wire:navigate
        style="display:flex;align-items:center;gap:.75rem;padding:.85rem 1rem;background:var(--gold-faint);border-radius:var(--r);color:inherit">
        <img src="{{ $topQari->image_url }}" class="avatar" width="38" height="38" alt="">
        <div style="min-width:0">
          <div style="font-size:.72rem;color:var(--text2)">{{ __('Most played reciter') }}</div>
          <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $topQari->name }}</div>
        </div>
      </a>
      @endif
    </div>
    @endif
  </div>

  <div class="card" style="padding:1.65rem;margin-top:1.4rem">
    <h3 style="font-size:.95rem;margin-bottom:1.15rem"><i class="fas fa-clock-rotate-left gold"></i> {{ __('Listening history') }}</h3>
    <livewire:watch-history-list />
  </div>
</div>
@endsection
