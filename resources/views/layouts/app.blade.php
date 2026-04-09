<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tilawa') — Qur'an Recitations</title>
    <meta name="description" content="@yield('meta_desc', 'Premium Tajweed recitations.')">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body x-data>
    <div class="bg-dots"></div>

    {{-- NAVBAR --}}
    <nav class="navbar z1">
        <a href="{{ route('home') }}" class="navbar-brand">
            <i class="fas fa-book-open-reader"></i> Tilawa
        </a>
        <div class="nav-links">
            <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"><i
                    class="fas fa-house"></i> Home</a>
            <a href="{{ route('qaris.index') }}" class="nav-link {{ request()->routeIs('qaris.*') ? 'active' : '' }}"><i
                    class="fas fa-microphone"></i> Qaris</a>
            @auth
                <a href="{{ route('library') }}" class="nav-link {{ request()->routeIs('library') ? 'active' : '' }}"><i
                        class="fas fa-bookmark"></i> Library</a>
            @endauth
        </div>
        <div class="navbar-right">

            {{-- SEARCH --}}
            <div class="search-wrap" x-data="searchBar()">
                <i class="fas fa-magnifying-glass search-icon"></i>
                <input class="search-input" type="text" placeholder="Search…" x-model="q"
                    @input.debounce.300ms="search()" @keydown.escape="close()" @click.away="close()">
                <template x-if="q">
                    <button class="search-clear" @click="q='';close()"><i class="fas fa-xmark"></i></button>
                </template>
                <div class="search-dropdown" x-show="open" x-transition>
                    <template x-if="results.qaris && results.qaris.length">
                        <div>
                            <div class="search-sec-hd"><i class="fas fa-microphone"></i> Qaris</div>
                            <template x-for="r in results.qaris" :key="r.id">
                                <a :href="r.url" class="search-item" @click="close()">
                                    <i class="fas fa-microphone-lines"
                                        style="color:var(--gold);width:15px;flex-shrink:0"></i>
                                    <span x-text="r.name"></span>
                                </a>
                            </template>
                        </div>
                    </template>
                    <template x-if="results.tilawat && results.tilawat.length">
                        <div>
                            <div class="search-sec-hd" style="border-top:1px solid var(--border)"><i
                                    class="fas fa-music"></i> Tilawat</div>
                            <template x-for="r in results.tilawat" :key="r.id">
                                <a :href="r.url" class="search-item" @click="close()">
                                    <i class="fas fa-music" style="color:var(--gold);width:15px;flex-shrink:0"></i>
                                    <div>
                                        <div x-text="r.title"></div>
                                        <div style="font-size:.72rem;color:var(--text2)" x-text="r.qari"></div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- USER --}}
            @auth
                <div x-data="{ open: false }" style="position:relative">
                    <button class="user-pill" @click="open=!open" @click.away="open=false">
                        <img src="{{ auth()->user()->avatar_url }}" class="avatar" width="23" height="23"
                            alt="">
                        <span class="user-pill-name">{{ Str::limit(auth()->user()->name, 13) }}</span>
                        <i class="fas fa-chevron-down" style="font-size:.58rem;color:var(--text3)"></i>
                    </button>
                    <div class="dropdown" x-show="open" x-transition>
                        @if (auth()->user()->hasAnyRole(['admin', 'creator']))
                            <a href="{{ route('admin.dashboard') }}"><i class="fas fa-gauge"
                                    style="color:var(--gold);width:15px"></i> Dashboard</a>
                        @endif
                        <a href="{{ route('profile') }}"><i class="fas fa-user" style="width:15px"></i> Profile</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button type="submit"><i class="fas fa-arrow-right-from-bracket"
                                    style="color:var(--red);width:15px"></i> Logout</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-right-to-bracket"></i>
                    Login</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Join</a>
            @endauth
        </div>
    </nav>

    {{-- FLASH --}}
    @if (session('success') || session('error'))
        <div class="wrap z1" style="padding-top:.85rem">
            @if (session('success'))
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> {{ session('error') }}</div>
            @endif
        </div>
    @endif

    <main class="z1">@yield('content')</main>

    {{-- AUDIO PLAYER --}}
    <div class="player-bar hidden z1" id="playerBar" x-data="audioPlayer()">
        <audio id="audioEl" preload="metadata"></audio>
        <div class="p-info">
            <img class="p-cover" id="pCover" src="" alt="">
            <div style="min-width:0">
                <div class="p-title" id="pTitle"></div>
                <div class="p-qari" id="pQari"></div>
            </div>
        </div>
        <div class="p-controls">
            <button class="p-btn" @click="seek(-10)" title="–10s"><i class="fas fa-rotate-left"></i></button>
            <button class="p-btn play" @click="toggle()" :title="playing ? 'Pause' : 'Play'">
                <i class="fas" :class="playing ? 'fa-pause' : 'fa-play'"></i>
            </button>
            <button class="p-btn" @click="seek(10)" title="+10s"><i class="fas fa-rotate-right"></i></button>
        </div>
        <div class="p-progress">
            <span class="p-time" x-text="fmt(cur)">0:00</span>
            <div class="p-bar" @click="scrub($event)">
                <div class="p-fill" :style="'width:' + pct + '%'"></div>
            </div>
            <span class="p-time" x-text="fmt(dur)">0:00</span>
        </div>
        <div class="p-vol">
            <a href="#" class="p-btn" id="pDownload" title="Download" style="display:none; color:var(--text2); text-decoration:none;"><i class="fas fa-download"></i></a>
            <button class="p-btn" @click="muted=!muted;audio.muted=muted">
                <i class="fas"
                    :class="muted ? 'fa-volume-xmark' : (vol > 0.5 ? 'fa-volume-high' : 'fa-volume-low')"></i>
            </button>
            <input type="range" class="vol-slider" min="0" max="1" step="0.02" x-model="vol"
                @input="audio.volume=vol">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function playTilawa(id, url, title, qari, cover, duration, downloadUrl) {
            window._playerLoad({
                id,
                url,
                title,
                qari,
                cover,
                duration,
                downloadUrl
            });
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('audioPlayer', () => ({
                audio: null,
                playing: false,
                cur: 0,
                dur: 0,
                pct: 0,
                vol: 1,
                muted: false,
                init() {
                    if (!window.globalAudio) {
                        window.globalAudio = new Audio();
                    }
                    this.audio = window.globalAudio;

                    window._playerLoad = (d) => this.load(d);

                    this.audio.addEventListener('timeupdate', () => {
                        this.cur = this.audio.currentTime;
                        this.dur = this.audio.duration || this.dur;
                        this.pct = this.dur ? (this.cur / this.dur) * 100 : 0;

                        localStorage.setItem('tilawa_player', JSON.stringify({
                            src: this.audio.src,
                            time: this.audio.currentTime,
                            playing: !this.audio.paused,
                            title: document.getElementById('pTitle').textContent,
                            qari: document.getElementById('pQari').textContent,
                            cover: document.getElementById('pCover').src,
                            duration: this.dur,
                            downloadUrl: document.getElementById('pDownload').href || ''
                        }));
                    });

                    this.audio.addEventListener('ended', () => this.playing = false);
                    this.audio.addEventListener('play', () => this.playing = true);
                    this.audio.addEventListener('pause', () => this.playing = false);

                    const saved = localStorage.getItem('tilawa_player');
                    if (saved) {
                        const d = JSON.parse(saved);
                        if (d.src) {
                            document.getElementById('pCover').src = d.cover || '';
                            document.getElementById('pTitle').textContent = d.title || '';
                            document.getElementById('pQari').textContent = d.qari || '';
                            if (d.downloadUrl) {
                                document.getElementById('pDownload').href = d.downloadUrl;
                                document.getElementById('pDownload').style.display = 'inline-block';
                            }
                            document.getElementById('playerBar').classList.remove('hidden');

                            this.audio.src = d.src;
                            this.dur = d.duration || 0;

                            this.audio.addEventListener('loadedmetadata', () => {
                                if (d.time) this.audio.currentTime = d.time;
                            });

                            if (d.playing) {
                                this.audio.play().catch(() => {});
                            }
                        }
                    }
                },
                load(d) {
                    document.getElementById('pCover').src = d.cover;
                    document.getElementById('pTitle').textContent = d.title;
                    document.getElementById('pQari').textContent = d.qari;
                    if (d.downloadUrl) {
                        document.getElementById('pDownload').href = d.downloadUrl;
                        document.getElementById('pDownload').style.display = 'inline-block';
                    }
                    document.getElementById('playerBar').classList.remove('hidden');

                    if (this.audio.src !== d.url) {
                        this.audio.src = d.url;
                        this.audio.load();
                    }

                    this.dur = d.duration || 0;
                    this.audio.play().catch(() => {});
                },
                toggle() {
                    this.playing ? this.audio.pause() : this.audio.play()
                },
                seek(s) {
                    if (this.audio) this.audio.currentTime = Math.max(0, Math.min(this.audio
                        .currentTime + s, this.audio.duration || 0))
                },
                scrub(e) {
                    if (!this.dur) return;
                    const r = e.currentTarget.getBoundingClientRect();
                    this.audio.currentTime = ((e.clientX - r.left) / r.width) * this.dur
                },
                fmt(s) {
                    if (!s || isNaN(s)) return '0:00';
                    return Math.floor(s / 60) + ':' + (Math.floor(s % 60) < 10 ? '0' : '') + Math.floor(
                        s % 60)
                },
            }));

            Alpine.data('searchBar', () => ({
                q: '',
                open: false,
                results: {},
                async search() {
                    if (this.q.length < 2) {
                        this.close();
                        return;
                    }
                    const r = await fetch(`/api/search?q=${encodeURIComponent(this.q)}`);
                    this.results = await r.json();
                    this.open = (this.results.qaris?.length || this.results.tilawat?.length) > 0;
                },
                close() {
                    this.open = false;
                },
            }));
        });
    </script>
    @stack('scripts')
</body>

</html>
