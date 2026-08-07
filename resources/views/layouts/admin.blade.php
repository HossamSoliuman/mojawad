<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', __('Dashboard')) — {{ config('app.name') }} Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
@vite(['resources/css/app.css','resources/css/admin.css','resources/js/app.js','resources/js/filepond.js'])
@livewireStyles
@stack('styles')
</head>
<body class="adm" x-data="{ sideOpen: false }">

@php($pendingReviewBadge = auth()->user()->hasAnyRole(['admin', 'reviewer']) ? \App\Models\Tilawa::pendingReview()->count() : 0)

<div class="admin-wrap z1">
  {{-- MOBILE BACKDROP --}}
  <div class="side-backdrop" x-show="sideOpen" @click="sideOpen=false" x-transition.opacity></div>

  {{-- =========================================================
       SIDEBAR
  ========================================================= --}}
  <aside class="admin-sidebar" :class="{ open: sideOpen }" id="sidebar">

    {{-- Logo --}}
    <div class="sidebar-logo">
      <a href="{{ route('home') }}" target="_blank">
        <x-brand.mark :size="30" />
        <span>{{ config('app.name') }}</span>
      </a>
    </div>

    {{-- Navigation --}}
    <nav class="sidebar-nav">

      @role('admin')
      <div class="nav-sec-lbl">{{ __('Overview') }}</div>
      <a href="{{ route('admin.dashboard') }}" class="s-link {{ request()->routeIs('admin.dashboard') ? 'active':'' }}">
        <i class="fas fa-gauge-high"></i> <span>{{ __('Dashboard') }}</span>
      </a>
      <a href="{{ route('admin.reports') }}" class="s-link {{ request()->routeIs('admin.reports') ? 'active':'' }}">
        <i class="fas fa-chart-line"></i> <span>{{ __('Reports') }}</span>
      </a>
      @endrole

      @role('creator')
      <div class="adm-nav-divider"></div>
      <div class="nav-sec-lbl">{{ __('Publish') }}</div>
      <a href="{{ route('admin.upload') }}" class="s-link {{ request()->routeIs('admin.upload') ? 'active':'' }}">
        <i class="fas fa-cloud-arrow-up"></i> <span>{{ __('Upload Tilawat') }}</span>
      </a>
      @endrole

      @hasanyrole('admin|creator')
      <div class="adm-nav-divider"></div>
      <div class="nav-sec-lbl">{{ __('Content') }}</div>
      <a href="{{ route('admin.qaris.index') }}" class="s-link {{ request()->routeIs('admin.qaris.*') ? 'active':'' }}">
        <i class="fas fa-microphone-lines"></i> <span>{{ __('Qaris') }}</span>
      </a>
      <a href="{{ route('admin.shorts.index') }}" class="s-link {{ request()->routeIs('admin.shorts.*') ? 'active':'' }}">
        <i class="fas fa-clapperboard"></i> <span>{{ __('Shorts') }}</span>
      </a>
      <a href="{{ route('admin.studio.index') }}" class="s-link {{ request()->routeIs('admin.studio.*') ? 'active':'' }}">
        <i class="fab fa-youtube"></i> <span>{{ __('Studio') }}</span>
      </a>
      <a href="{{ route('admin.clips.index') }}" class="s-link {{ request()->routeIs('admin.clips.*') ? 'active':'' }}">
        <i class="fas fa-film"></i> <span>{{ __('Short videos') }}</span>
      </a>
      <a href="{{ route('admin.tilawat.index') }}" class="s-link {{ request()->routeIs('admin.tilawat.*') ? 'active':'' }}">
        <i class="fas fa-music"></i>
        <span>{{ __('Tilawat') }}</span>
        @role('admin')
        @if($pendingReviewBadge > 0)
        <span class="s-badge">{{ $pendingReviewBadge }}</span>
        @endif
        @endrole
      </a>

      <div class="adm-nav-divider"></div>
      <div class="nav-sec-lbl">{{ __('Publishing') }}</div>
      <a href="{{ route('admin.publishing.factory') }}" class="s-link {{ request()->routeIs('admin.publishing.factory') ? 'active':'' }}">
        <i class="fas fa-industry"></i> <span>{{ __('Factory') }}</span>
      </a>
      <a href="{{ route('admin.publishing.production') }}" class="s-link {{ request()->routeIs('admin.publishing.production') ? 'active':'' }}">
        <i class="fas fa-tower-broadcast"></i> <span>{{ __('Production') }}</span>
      </a>
      <a href="{{ route('admin.publishing.card-lab') }}" class="s-link {{ request()->routeIs('admin.publishing.card-lab') ? 'active':'' }}">
        <i class="fas fa-flask"></i> <span>{{ __('Card Lab') }}</span>
      </a>
      <a href="{{ route('admin.social.facebook') }}" class="s-link {{ request()->routeIs('admin.social.facebook') ? 'active':'' }}">
        <i class="fab fa-facebook"></i> <span>{{ __('Facebook Posts') }}</span>
      </a>
      @endhasanyrole

      @role('reviewer')
      <div class="adm-nav-divider"></div>
      <div class="nav-sec-lbl">{{ __('Review') }}</div>
      <a href="{{ route('admin.review.index') }}" class="s-link {{ request()->routeIs('admin.review.index') ? 'active':'' }}">
        <i class="fas fa-clipboard-check"></i>
        <span>{{ __('Review Queue') }}</span>
        @if($pendingReviewBadge > 0)
        <span class="s-badge">{{ $pendingReviewBadge }}</span>
        @endif
      </a>
      <a href="{{ route('admin.review.history') }}" class="s-link {{ request()->routeIs('admin.review.history') ? 'active':'' }}">
        <i class="fas fa-clock-rotate-left"></i> <span>{{ __('History') }}</span>
      </a>
      @endrole

      @role('admin')
      <div class="adm-nav-divider"></div>
      <div class="nav-sec-lbl">{{ __('Community') }}</div>
      <a href="{{ route('admin.users.index') }}" class="s-link {{ request()->routeIs('admin.users.*') ? 'active':'' }}">
        <i class="fas fa-users"></i> <span>{{ __('Users') }}</span>
      </a>
      @endrole

      <div class="adm-nav-divider"></div>
      <div class="nav-sec-lbl">{{ __('Site') }}</div>
      <a href="{{ route('home') }}" class="s-link" target="_blank">
        <i class="fas fa-arrow-up-right-from-square"></i> <span>{{ __('View Site') }}</span>
      </a>

    </nav>

    {{-- Sidebar footer — user info + logout --}}
    <div class="sidebar-footer">
      <img src="{{ auth()->user()->avatar_url }}" class="avatar" width="32" height="32" alt="">
      <div style="flex:1;min-width:0">
        <div class="sf-name" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
        <div class="sf-role">{{ __(ucfirst(auth()->user()->roles->first()?->name ?? 'user')) }}</div>
      </div>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button type="submit" class="btn-icon" title="{{ __('Logout') }}">
          <i class="fas fa-arrow-right-from-bracket flip-rtl"></i>
        </button>
      </form>
    </div>

  </aside>

  {{-- =========================================================
       MAIN
  ========================================================= --}}
  <div class="admin-main">

    {{-- TOPBAR --}}
    <div class="admin-topbar">

      {{-- Left: mobile toggle + breadcrumb --}}
      <div style="display:flex;align-items:center;gap:.75rem;min-width:0">
        <button class="btn-icon topbar-menu" @click="sideOpen=!sideOpen" aria-label="{{ __('Menu') }}">
          <i class="fas fa-bars"></i>
        </button>
        <div style="min-width:0">
          <h1>@yield('page-title', __('Dashboard'))</h1>
          @hasSection('breadcrumb')
          <div class="topbar-crumb">
            <i class="fas fa-house" style="font-size:.65rem"></i>
            &nbsp;@yield('breadcrumb')
          </div>
          @endif
        </div>
      </div>

      {{-- Right: actions --}}
      <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0">

        {{-- Language switcher --}}
        <div x-data="{ open: false }" style="position:relative">
          <button class="user-pill" @click="open=!open" @click.away="open=false" style="gap:.4rem">
            <i class="fas fa-globe" style="font-size:.78rem"></i>
            <span class="user-pill-name">{{ strtoupper(app()->getLocale()) }}</span>
            <i class="fas fa-chevron-down" style="font-size:.6rem;opacity:.5"></i>
          </button>
          <div class="dropdown" x-show="open" x-transition style="min-width:120px">
            <a href="{{ route('lang', 'en') }}">
              <i class="fas fa-check" style="font-size:.7rem;opacity:{{ app()->getLocale() === 'en' ? 1 : 0 }}"></i>
              English
            </a>
            <a href="{{ route('lang', 'ar') }}">
              <i class="fas fa-check" style="font-size:.7rem;opacity:{{ app()->getLocale() === 'ar' ? 1 : 0 }}"></i>
              العربية
            </a>
          </div>
        </div>

        {{-- Page-level action buttons injected by child views --}}
        @yield('topbar-actions')

      </div>
    </div>

    {{-- PAGE CONTENT --}}
    <div class="admin-body">
      @if(session('success'))
      <div class="alert alert-success" style="margin-bottom:1.35rem">
        <i class="fas fa-circle-check"></i> {{ session('success') }}
      </div>
      @endif
      @if(session('error'))
      <div class="alert alert-error" style="margin-bottom:1.35rem">
        <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
      </div>
      @endif
      @yield('content')
    </div>

  </div>{{-- /.admin-main --}}
</div>{{-- /.admin-wrap --}}

{{-- Mini audio player — hidden in admin via CSS but JS hooks remain available --}}
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
