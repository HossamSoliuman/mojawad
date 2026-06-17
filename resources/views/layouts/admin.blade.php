<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', __('Dashboard')) — Mojawad</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
@vite(['resources/css/app.css','resources/js/app.js','resources/js/filepond.js'])
@livewireStyles
</head>
<body x-data="{ sideOpen: false }">
<div class="bg-dots"></div>

@php($pendingReviewBadge = auth()->user()->hasAnyRole(['admin', 'reviewer']) ? \App\Models\Tilawa::pendingReview()->count() : 0)

<div class="admin-wrap z1">
  {{-- MOBILE BACKDROP --}}
  <div class="side-backdrop" x-show="sideOpen" @click="sideOpen=false" x-transition.opacity></div>

  {{-- SIDEBAR --}}
  <aside class="admin-sidebar" :class="{ open: sideOpen }" id="sidebar">
    <div class="sidebar-logo">
      <a href="{{ route('home') }}">
        <span class="logo-glyph"><i class="fas fa-book-open-reader"></i></span>
        <span>Mojawad</span>
      </a>
    </div>
    <nav class="sidebar-nav">
      @role('admin')
      <div class="nav-sec-lbl">{{ __('Overview') }}</div>
      <a href="{{ route('admin.dashboard') }}" class="s-link {{ request()->routeIs('admin.dashboard') ? 'active':'' }}">
        <i class="fas fa-gauge-high"></i> {{ __('Dashboard') }}
      </a>
      <a href="{{ route('admin.reports') }}" class="s-link {{ request()->routeIs('admin.reports') ? 'active':'' }}">
        <i class="fas fa-chart-line"></i> {{ __('Reports') }}
      </a>
      @endrole

      @role('creator')
      <div class="nav-sec-lbl">{{ __('Publish') }}</div>
      <a href="{{ route('admin.upload') }}" class="s-link {{ request()->routeIs('admin.upload') ? 'active':'' }}">
        <i class="fas fa-cloud-arrow-up"></i> {{ __('Upload Tilawat') }}
      </a>
      @endrole

      @hasanyrole('admin|creator')
      <div class="nav-sec-lbl">{{ __('Content') }}</div>
      <a href="{{ route('admin.qaris.index') }}" class="s-link {{ request()->routeIs('admin.qaris.*') ? 'active':'' }}">
        <i class="fas fa-microphone-lines"></i> {{ __('Qaris') }}
      </a>
      <a href="{{ route('admin.tilawat.index') }}" class="s-link {{ request()->routeIs('admin.tilawat.*') ? 'active':'' }}">
        <i class="fas fa-music"></i> {{ __('Tilawat') }}
        @role('admin')
        @if($pendingReviewBadge > 0)
        <span class="s-badge">{{ $pendingReviewBadge }}</span>
        @endif
        @endrole
      </a>
      @endhasanyrole

      @role('reviewer')
      <div class="nav-sec-lbl">{{ __('Review') }}</div>
      <a href="{{ route('admin.review.index') }}" class="s-link {{ request()->routeIs('admin.review.index') ? 'active':'' }}">
        <i class="fas fa-clipboard-check"></i> {{ __('Review Queue') }}
        @if($pendingReviewBadge > 0)
        <span class="s-badge">{{ $pendingReviewBadge }}</span>
        @endif
      </a>
      <a href="{{ route('admin.review.history') }}" class="s-link {{ request()->routeIs('admin.review.history') ? 'active':'' }}">
        <i class="fas fa-clock-rotate-left"></i> {{ __('History') }}
      </a>
      @endrole

      @role('admin')
      <div class="nav-sec-lbl">{{ __('Community') }}</div>
      <a href="{{ route('admin.users.index') }}" class="s-link {{ request()->routeIs('admin.users.*') ? 'active':'' }}">
        <i class="fas fa-users"></i> {{ __('Users') }}
      </a>
      @endrole

      <div class="nav-sec-lbl">{{ __('Site') }}</div>
      <a href="{{ route('home') }}" class="s-link" target="_blank">
        <i class="fas fa-arrow-up-right-from-square"></i> {{ __('View Site') }}
      </a>
    </nav>
    <div class="sidebar-footer">
      <img src="{{ auth()->user()->avatar_url }}" class="avatar" width="30" height="30" alt="">
      <div style="flex:1;min-width:0">
        <div class="sf-name">{{ Str::limit(auth()->user()->name, 16) }}</div>
        <div class="sf-role">{{ __(ucfirst(auth()->user()->roles->first()?->name ?? 'user')) }}</div>
      </div>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button type="submit" class="btn-icon" style="color:var(--red)" title="{{ __('Logout') }}">
          <i class="fas fa-arrow-right-from-bracket"></i>
        </button>
      </form>
    </div>
  </aside>

  {{-- MAIN --}}
  <div class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:.8rem;min-width:0">
        <button class="btn-icon topbar-menu" @click="sideOpen=!sideOpen">
          <i class="fas fa-bars"></i>
        </button>
        <div style="min-width:0">
          <h1>@yield('page-title', __('Dashboard'))</h1>
          @hasSection('breadcrumb')
          <div class="topbar-crumb">@yield('breadcrumb')</div>
          @endif
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:.6rem;flex-shrink:0">
        <div x-data="{ open: false }" style="position:relative">
            <button class="user-pill" @click="open=!open" @click.away="open=false">
                <i class="fas fa-globe" style="font-size:.9rem;color:var(--gold)"></i>
                <span class="user-pill-name" style="color:var(--text1)">{{ app()->getLocale() == 'ar' ? 'العربية' : 'English' }}</span>
            </button>
            <div class="dropdown" x-show="open" x-transition>
                <a href="{{ route('lang', 'en') }}">English</a>
                <a href="{{ route('lang', 'ar') }}">العربية</a>
            </div>
        </div>
        @yield('topbar-actions')
      </div>
    </div>
    <div class="admin-body">
      @if(session('success'))
      <div class="alert alert-success" style="margin-bottom:1.4rem"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
      @endif
      @if(session('error'))
      <div class="alert alert-error" style="margin-bottom:1.4rem"><i class="fas fa-circle-exclamation"></i> {{ session('error') }}</div>
      @endif
      @yield('content')
    </div>
  </div>
</div>

{{-- Mini audio player for previewing tilawat --}}
<div class="player-bar hidden z1" id="playerBar" x-data="audioPlayer()">
  <audio id="audioEl" preload="metadata"></audio>
  <div class="p-info">
    <img class="p-cover" id="pCover" src="" alt="">
    <div style="min-width:0">
      <div class="p-title" id="pTitle"></div>
      <div class="p-qari"  id="pQari"></div>
    </div>
  </div>
  <div class="p-controls">
    <button class="p-btn" @click="seek(-10)"><i class="fas fa-rotate-left"></i></button>
    <button class="p-btn play" @click="toggle()"><i class="fas" :class="playing?'fa-pause':'fa-play'"></i></button>
    <button class="p-btn" @click="seek(10)"><i class="fas fa-rotate-right"></i></button>
  </div>
  <div class="p-progress">
    <span class="p-time" x-text="fmt(cur)">0:00</span>
    <div class="p-bar" @click="scrub($event)"><div class="p-fill" :style="'width:'+pct+'%'"></div></div>
    <span class="p-time" x-text="fmt(dur)">0:00</span>
  </div>
  <div class="p-vol">
    <button class="p-btn" @click="muted=!muted;audio.muted=muted">
      <i class="fas" :class="muted?'fa-volume-xmark':'fa-volume-high'"></i>
    </button>
    <input type="range" class="vol-slider" min="0" max="1" step="0.02" x-model="vol" @input="audio.volume=vol">
  </div>
</div>

<script>
function playTilawa(id, url, title, qari, cover, duration) {
  window._playerLoad({ id, url, title, qari, cover, duration });
}
document.addEventListener('alpine:init', () => {
  Alpine.data('audioPlayer', () => ({
    audio:null, playing:false, cur:0, dur:0, pct:0, vol:1, muted:false,
    init() {
      this.audio = document.getElementById('audioEl');
      window._playerLoad = (d) => this.load(d);
      this.audio.addEventListener('timeupdate', () => { this.cur=this.audio.currentTime; this.dur=this.audio.duration||this.dur; this.pct=this.dur?(this.cur/this.dur)*100:0; });
      this.audio.addEventListener('ended', () => this.playing=false);
      this.audio.addEventListener('play',  () => this.playing=true);
      this.audio.addEventListener('pause', () => this.playing=false);
    },
    load(d) {
      document.getElementById('pCover').src = d.cover;
      document.getElementById('pTitle').textContent = d.title;
      document.getElementById('pQari').textContent  = d.qari;
      document.getElementById('playerBar').classList.remove('hidden');
      this.audio.src = d.url; this.dur = d.duration||0; this.audio.load();
      this.audio.play().catch(()=>{});
    },
    toggle() { this.playing ? this.audio.pause() : this.audio.play() },
    seek(s)  { if(this.audio) this.audio.currentTime = Math.max(0, Math.min(this.audio.currentTime+s, this.audio.duration||0)) },
    scrub(e) { if(!this.dur) return; const r=e.currentTarget.getBoundingClientRect(); this.audio.currentTime=((e.clientX-r.left)/r.width)*this.dur },
    fmt(s)   { if(!s||isNaN(s)) return '0:00'; return Math.floor(s/60)+':'+(Math.floor(s%60)<10?'0':'')+Math.floor(s%60) },
  }));
});
</script>
@stack('scripts')
@livewireScripts
</body>
</html>
