@extends('layouts.app')
@section('title', __('Profile'))
@section('content')
@php
  use Carbon\CarbonInterval;
  $allTime = $totals['all_time_seconds'];
  $week = $totals['week_seconds'];
  $listeningTime = $allTime > 0
    ? CarbonInterval::seconds($allTime)->cascade()->forHumans(['short' => true, 'parts' => 2])
    : '—';
  $weekTime = $week > 0
    ? CarbonInterval::seconds($week)->cascade()->forHumans(['short' => true, 'parts' => 2])
    : '—';
  $hasHours = collect($hourHistogram)->sum() > 0;
  $hasTrend = collect($dailyTrend)->sum('seconds') > 0;
@endphp
<div class="page-hd"><div class="wrap"><h1><i class="fas fa-user gold"></i> {{ __('Profile') }}</h1><p>{{ __('Your account') }}</p></div></div>
<div class="wrap" style="padding-bottom:3rem;max-width:820px">

  <div class="card profile-id" style="padding:1.85rem;margin-bottom:1.4rem">
    <img src="{{ auth()->user()->avatar_url }}" class="avatar" width="76" height="76" alt="">
    <div style="flex:1;min-width:0">
      <h2 style="font-size:1.35rem;margin-bottom:.22rem">{{ auth()->user()->name }}</h2>
      <div style="color:var(--text2);font-size:.9rem">{{ auth()->user()->email }}</div>
      <div style="margin-top:.45rem">
        <span class="badge badge-gold">{{ auth()->user()->roles->first()?->name ?? 'user' }}</span>
      </div>
    </div>
    <livewire:profile-edit />
  </div>

  {{-- Stat tiles --}}
  <div class="profile-stats" style="margin-bottom:1.4rem">
    <div class="card" style="padding:1.25rem;text-align:center">
      <div style="font-family:var(--font-display);font-size:1.45rem;font-weight:700;color:var(--gold);line-height:1.2">{{ $listeningTime }}</div>
      <div style="font-size:.76rem;color:var(--text2);margin-top:.3rem">{{ __('Total listening time') }}</div>
    </div>
    <div class="card" style="padding:1.25rem;text-align:center">
      <div style="font-family:var(--font-display);font-size:1.45rem;font-weight:700;color:var(--gold);line-height:1.2">{{ $weekTime }}</div>
      <div style="font-size:.76rem;color:var(--text2);margin-top:.3rem">{{ __('This week') }}</div>
    </div>
    <div class="card" style="padding:1.25rem;text-align:center">
      <div style="font-family:var(--font-display);font-size:1.7rem;font-weight:700;color:var(--gold)">{{ number_format($streak) }}</div>
      <div style="font-size:.76rem;color:var(--text2);margin-top:.3rem">{{ __('Day streak') }}</div>
    </div>
    <div class="card" style="padding:1.25rem;text-align:center">
      <div style="font-family:var(--font-display);font-size:1.7rem;font-weight:700;color:var(--gold)">{{ number_format($totals['completed']) }}</div>
      <div style="font-size:.76rem;color:var(--text2);margin-top:.3rem">{{ __('Completed') }}</div>
    </div>
  </div>

  {{-- Quick links --}}
  <div class="profile-links" style="margin-bottom:1.4rem">
    <a href="{{ route('likes') }}" wire:navigate class="card" style="padding:1rem;text-align:center;color:inherit">
      <div style="font-family:var(--font-display);font-size:1.35rem;font-weight:700;color:var(--gold)">{{ number_format($totals['liked']) }}</div>
      <div style="font-size:.74rem;color:var(--text2)">{{ __('Liked') }}</div>
    </a>
    <a href="{{ route('saved') }}" wire:navigate class="card" style="padding:1rem;text-align:center;color:inherit">
      <div style="font-family:var(--font-display);font-size:1.35rem;font-weight:700;color:var(--gold)">{{ number_format($totals['saved']) }}</div>
      <div style="font-size:.74rem;color:var(--text2)">{{ __('Saved') }}</div>
    </a>
    <div class="card" style="padding:1rem;text-align:center">
      <div style="font-family:var(--font-display);font-size:1.35rem;font-weight:700;color:var(--gold)">{{ number_format($totals['downloaded']) }}</div>
      <div style="font-size:.74rem;color:var(--text2)">{{ __('Downloads') }}</div>
    </div>
  </div>

  {{-- Charts --}}
  <div class="profile-charts" style="margin-bottom:1.4rem">
    <div class="card" style="padding:1.5rem">
      <h3 style="font-size:.95rem;margin-bottom:1.15rem"><i class="fas fa-clock gold"></i> {{ __('Best time of day') }}</h3>
      @if($hasHours)
        <canvas data-chart="hours" data-values="{{ json_encode(array_values($hourHistogram)) }}" height="200"></canvas>
      @else
        <p style="color:var(--text2);font-size:.85rem;text-align:center;padding:2rem 0">{{ __('Not enough data yet.') }}</p>
      @endif
    </div>
    <div class="card" style="padding:1.5rem">
      <h3 style="font-size:.95rem;margin-bottom:1.15rem"><i class="fas fa-chart-line gold"></i> {{ __('Activity trend') }}</h3>
      @if($hasTrend)
        <canvas
          data-chart="trend"
          data-labels="{{ json_encode(collect($dailyTrend)->pluck('date')) }}"
          data-values="{{ json_encode(collect($dailyTrend)->pluck('seconds')) }}"
          height="200"></canvas>
      @else
        <p style="color:var(--text2);font-size:.85rem;text-align:center;padding:2rem 0">{{ __('Not enough data yet.') }}</p>
      @endif
    </div>
  </div>

  {{-- Favorite reciters --}}
  @if(count($topQaris))
  <div class="card" style="padding:1.65rem;margin-bottom:1.4rem">
    <h3 style="font-size:.95rem;margin-bottom:1.15rem"><i class="fas fa-microphone-lines gold"></i> {{ __('Favorite reciters') }}</h3>
    <div style="display:grid;gap:.85rem">
      @foreach($topQaris as $row)
      @php($qari = $row['qari'])
      <a href="{{ route('qaris.show', $qari) }}" wire:navigate
        style="display:flex;align-items:center;gap:.85rem;padding:.7rem .85rem;background:var(--gold-faint);border-radius:var(--r);color:inherit">
        <img src="{{ $qari->image_url }}" class="avatar" width="44" height="44" alt="">
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $qari->name }}</div>
          <div style="font-size:.76rem;color:var(--text2)">
            {{ CarbonInterval::seconds($row['seconds'])->cascade()->forHumans(['short' => true, 'parts' => 2]) }}
          </div>
        </div>
        <i class="fas fa-chevron-left gold" style="opacity:.6"></i>
      </a>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Most listened --}}
  @if(count($topTilawat))
  <div class="card" style="padding:1.65rem;margin-bottom:1.4rem">
    <h3 style="font-size:.95rem;margin-bottom:1.15rem"><i class="fas fa-headphones gold"></i> {{ __('Most listened') }}</h3>
    <div class="grid-tilawat" data-queue>
      @foreach($topTilawat as $row)
      @php($t = $row['tilawa'])
      <div class="t-card" data-track-id="{{ $t->id }}">
        <div class="t-card-img">
          <img src="{{ $t->cover_url }}" alt="{{ $t->title }}" loading="lazy">
          <button class="t-play-btn" data-track="{{ json_encode($t->playerPayload()) }}">
            <i class="fas fa-play"></i>
          </button>
        </div>
        <div class="t-card-body">
          <a href="{{ route('tilawa.show', $t) }}" wire:navigate>
            <div class="t-card-title">{{ $t->title }}</div>
          </a>
          <a href="{{ route('qaris.show', $t->qari) }}" wire:navigate class="t-card-qari">
            {{ $t->qari->name }}
          </a>
          <div class="t-card-meta">
            <span><i class="fas fa-headphones"></i> {{ CarbonInterval::seconds($row['seconds'])->cascade()->forHumans(['short' => true, 'parts' => 1]) }}</span>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  <div class="card" style="padding:1.65rem">
    <h3 style="font-size:.95rem;margin-bottom:1.15rem"><i class="fas fa-clock-rotate-left gold"></i> {{ __('Continue listening') }}</h3>
    <livewire:watch-history-list />
  </div>
</div>
@endsection
