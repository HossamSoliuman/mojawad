<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','Admin') — Tilawa</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body x-data="{ sideOpen: false }">
<div class="bg-dots"></div>

<div class="admin-wrap z1">
  {{-- SIDEBAR --}}
  <aside class="admin-sidebar" :class="{ open: sideOpen }" id="sidebar">
    <div class="sidebar-logo">
      <a href="{{ route('home') }}"><i class="fas fa-book-open-reader"></i> Tilawa</a>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-sec-lbl">Overview</div>
      <a href="{{ route('admin.dashboard') }}" class="s-link {{ request()->routeIs('admin.dashboard') ? 'active':'' }}">
        <i class="fas fa-gauge-high"></i> Dashboard
      </a>
      <div class="nav-sec-lbl" style="margin-top:.35rem">Content</div>
      <a href="{{ route('admin.qaris.index') }}" class="s-link {{ request()->routeIs('admin.qaris.*') ? 'active':'' }}">
        <i class="fas fa-microphone-lines"></i> Qaris
      </a>
      <a href="{{ route('admin.qaris.create') }}" class="s-link" style="padding-left:2.1rem;font-size:.78rem">
        <i class="fas fa-plus"></i> Add Qari
      </a>
      <a href="{{ route('admin.tilawat.index') }}" class="s-link {{ request()->routeIs('admin.tilawat.*') ? 'active':'' }}">
        <i class="fas fa-music"></i> Tilawat
      </a>
      <a href="{{ route('admin.tilawat.create') }}" class="s-link" style="padding-left:2.1rem;font-size:.78rem">
        <i class="fas fa-plus"></i> Add Tilawa
      </a>
      @role('admin')
      <div class="nav-sec-lbl" style="margin-top:.35rem">Community</div>
      <a href="{{ route('admin.users.index') }}" class="s-link {{ request()->routeIs('admin.users.*') ? 'active':'' }}">
        <i class="fas fa-users"></i> Users
      </a>
      @endrole
      <div class="nav-sec-lbl" style="margin-top:.35rem">Site</div>
      <a href="{{ route('home') }}" class="s-link" target="_blank">
        <i class="fas fa-arrow-up-right-from-square"></i> View Site
      </a>
    </nav>
    <div class="sidebar-footer">
      <img src="{{ auth()->user()->avatar_url }}" class="avatar" width="30" height="30" alt="">
      <div style="flex:1;min-width:0">
        <div class="sf-name">{{ Str::limit(auth()->user()->name,16) }}</div>
        <div class="sf-role">{{ auth()->user()->roles->first()?->name ?? 'user' }}</div>
      </div>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button type="submit" class="btn-icon" style="color:var(--red)" title="Logout">
          <i class="fas fa-arrow-right-from-bracket"></i>
        </button>
      </form>
    </div>
  </aside>

  {{-- MAIN --}}
  <div class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:.8rem">
        <button class="btn-icon" @click="sideOpen=!sideOpen" id="menuBtn" style="display:none">
          <i class="fas fa-bars"></i>
        </button>
        <div>
          <h1>@yield('page-title','Dashboard')</h1>
          @hasSection('breadcrumb')
          <div style="font-size:.76rem;color:var(--text2);margin-top:.16rem">@yield('breadcrumb')</div>
          @endif
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:.6rem">
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

{{-- Mini audio player in admin too --}}
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

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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
if(window.innerWidth<=1024) document.getElementById('menuBtn').style.display='flex';
window.addEventListener('resize', () => { document.getElementById('menuBtn').style.display = window.innerWidth<=1024?'flex':'none'; });
</script>
@stack('scripts')
</body>
</html>
