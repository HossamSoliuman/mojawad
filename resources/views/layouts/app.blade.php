<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mojawad') — {{ __('Qur\'an Recitations') }}</title>
    <meta name="description" content="@yield('meta_desc', 'Premium Tajweed recitations.')">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body>
    <div class="bg-dots"></div>

    {{-- FLOATING GLASS ISLAND NAV --}}
    <nav class="nav-island z1" x-data="navIsland()" @click.outside="closeAll()">

        <a href="{{ route('home') }}" wire:navigate class="ni-brand" title="Mojawad">
            <i class="fas fa-book-open-reader"></i>
            <span class="ni-brand-name">Mojawad</span>
        </a>

        <div class="ni-links">
            <a href="{{ route('home') }}" wire:navigate
                class="ni-link {{ request()->routeIs('home') ? 'active' : '' }}" title="{{ __('Home') }}">
                <i class="fas fa-house"></i><span>{{ __('Home') }}</span>
            </a>
            <a href="{{ route('qaris.index') }}" wire:navigate
                class="ni-link {{ request()->routeIs('qaris.*') ? 'active' : '' }}" title="{{ __('Qaris') }}">
                <i class="fas fa-microphone-lines"></i><span>{{ __('Qaris') }}</span>
            </a>
            @auth
                <a href="{{ route('likes') }}" wire:navigate
                    class="ni-link {{ request()->routeIs('likes') ? 'active' : '' }}" title="{{ __('Likes') }}">
                    <i class="fas fa-heart"></i><span>{{ __('Likes') }}</span>
                </a>
            @endauth
        </div>

        <div class="ni-actions">
            {{-- USER --}}
            @auth
                <div class="ni-menu" style="position:relative">
                    <button type="button" class="ni-avatar-btn" @click="user=!user"
                        title="{{ auth()->user()->name }}">
                        <img src="{{ auth()->user()->avatar_url }}" class="avatar" width="30" height="30" alt="">
                    </button>
                    <div class="dropdown" x-show="user" x-transition @click.outside="user=false" style="display:none">
                        <div class="ni-user-hd">{{ Str::limit(auth()->user()->name, 18) }}</div>
                        @if (auth()->user()->hasAnyRole(['admin', 'creator']))
                            <a href="{{ route('admin.dashboard') }}"><i class="fas fa-gauge"
                                    style="color:var(--gold);width:15px"></i> {{ __('Dashboard') }}</a>
                        @endif
                        <a href="{{ route('profile') }}" wire:navigate><i class="fas fa-user"
                                style="width:15px"></i> {{ __('Profile') }}</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button type="submit"><i class="fas fa-arrow-right-from-bracket"
                                    style="color:var(--red);width:15px"></i> {{ __('Logout') }}</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="ni-icon-btn" title="{{ __('Login') }}"
                    aria-label="{{ __('Login') }}"><i class="fas fa-arrow-right-to-bracket"></i></a>
                <a href="{{ route('register') }}" class="ni-join">{{ __('Join') }}</a>
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

    <main class="z1 @unless (request()->routeIs('home')) main-nav-pad @endunless">@yield('content')</main>

    {{-- AUDIO PLAYER — persisted across wire:navigate so audio never stops --}}
    @persist('player')
    <div class="player-bar hidden z1" id="playerBar" x-data="audioPlayer()">
        <audio id="audioEl" preload="metadata"></audio>
        <div class="p-info">
            <img class="p-cover" id="pCover" src="" alt="">
            <div style="min-width:0">
                <div class="p-title" id="pTitle"></div>
                <div class="p-qari" id="pQari"></div>
            </div>
            <button class="p-btn p-like" :class="liked ? 'liked' : ''" @click="toggleLike()" x-show="currentId"
                :title="liked ? '{{ __('Liked') }}' : '{{ __('Like') }}'" :aria-pressed="liked">
                <i :class="(liked ? 'fas' : 'far') + ' fa-heart' + (likePop ? ' like-pop' : '')"></i>
            </button>
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
            <button class="p-btn" @click="muted=!muted">
                <i class="fas"
                    :class="muted ? 'fa-volume-xmark' : (vol > 0.5 ? 'fa-volume-high' : 'fa-volume-low')"></i>
            </button>
            <input type="range" class="vol-slider" min="0" max="1" step="0.02" x-model.number="vol">
        </div>
    </div>
    @endpersist

    <script>
        window._likeEnabled = @json(auth()->check());

        // ── Guest likes ──────────────────────────────────────────────────────
        // Visitors can like before signing in; we remember their picks locally
        // and merge them into their account the next time they're authenticated.
        window._guestLikeKey = 'tilawa_guest_likes';
        window._getGuestLikes = function () {
            try {
                return new Set((JSON.parse(localStorage.getItem(window._guestLikeKey) || '[]') || []).map(Number));
            } catch (e) {
                return new Set();
            }
        };
        window._setGuestLikes = function (set) {
            localStorage.setItem(window._guestLikeKey, JSON.stringify([...set]));
        };
        window.isTilawaLiked = function (id) {
            return window._getGuestLikes().has(Number(id));
        };

        // Single source of truth for toggling a like. Broadcasts the new state so
        // every like surface (player heart, track-page button, counts) stays in sync.
        window.toggleTilawaLike = async function (id) {
            id = Number(id);
            if (!id) return null;

            // Not signed in → keep the like locally until they authenticate.
            if (!window._likeEnabled) {
                const likes = window._getGuestLikes();
                const liked = !likes.has(id);
                liked ? likes.add(id) : likes.delete(id);
                window._setGuestLikes(likes);
                const detail = { id, liked, count: null };
                window.dispatchEvent(new CustomEvent('tilawa-like-changed', { detail }));
                if (window.Livewire) {
                    window.Livewire.dispatch('tilawa-like-changed', { id, liked });
                }
                return detail;
            }

            const csrf = document.querySelector('meta[name=csrf-token]').content;
            try {
                const r = await fetch(`/api/like/${id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                });
                if (!r.ok) return null;
                const d = await r.json();
                window.dispatchEvent(new CustomEvent('tilawa-like-changed', {
                    detail: { id, liked: d.liked, count: d.count }
                }));
                if (window.Livewire) {
                    window.Livewire.dispatch('tilawa-like-changed', { id, liked: d.liked });
                }
                return d;
            } catch (e) {
                return null;
            }
        };

        // Push locally-stored guest likes into the account once authenticated.
        window._syncGuestLikes = async function () {
            if (!window._likeEnabled) return;
            const likes = window._getGuestLikes();
            if (!likes.size) return;
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            try {
                const r = await fetch('/api/likes/sync', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ ids: [...likes] })
                });
                if (r.ok) {
                    localStorage.removeItem(window._guestLikeKey);
                }
            } catch (e) {}
        };
        document.addEventListener('livewire:init', () => window._syncGuestLikes());

        // Keep every visible like-count in sync, app-wide.
        window.addEventListener('tilawa-like-changed', (e) => {
            const { id, count } = e.detail;
            if (typeof count !== 'number') return;
            document.querySelectorAll(`[data-like-count="${id}"]`).forEach((el) => {
                el.textContent = count.toLocaleString();
            });
        });

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
                currentId: null,
                liked: false,
                likePop: false,
                likeLoading: false,
                _lastSave: 0,
                init() {
                    if (!window.globalAudio) {
                        window.globalAudio = new Audio();
                    }
                    this.audio = window.globalAudio;

                    window._playerLoad = (d) => this.load(d);

                    // ── Restore saved sound level / mute (kept across sessions,
                    //    independent of which track is loaded) ──
                    try {
                        const prefs = JSON.parse(localStorage.getItem('tilawa_prefs') || '{}');
                        if (typeof prefs.vol === 'number') this.vol = prefs.vol;
                        if (typeof prefs.muted === 'boolean') this.muted = prefs.muted;
                    } catch (e) {}
                    this.audio.volume = this.vol;
                    this.audio.muted = this.muted;

                    // Persist sound level / mute the instant they change
                    this.$watch('vol', (v) => { this.audio.volume = v; this.savePrefs(); });
                    this.$watch('muted', (m) => { this.audio.muted = m; this.savePrefs(); });

                    this.audio.addEventListener('timeupdate', () => {
                        this.cur = this.audio.currentTime;
                        this.dur = this.audio.duration || this.dur;
                        this.pct = this.dur ? (this.cur / this.dur) * 100 : 0;
                        this.saveProgress();
                    });

                    this.audio.addEventListener('ended', () => { this.playing = false; this.saveProgress(true); });
                    this.audio.addEventListener('play',  () => { this.playing = true;  this.saveProgress(true); });
                    this.audio.addEventListener('pause', () => { this.playing = false; this.saveProgress(true); });
                    window.addEventListener('beforeunload', () => this.saveProgress(true));

                    // Reflect like changes made anywhere else for the current track.
                    window.addEventListener('tilawa-like-changed', (e) => {
                        if (Number(e.detail.id) !== Number(this.currentId)) return;
                        const wasLiked = this.liked;
                        this.liked = e.detail.liked;
                        if (this.liked && !wasLiked) {
                            this.likePop = true;
                            setTimeout(() => { this.likePop = false; }, 450);
                        }
                    });

                    // ── Restore now-playing track ──
                    // During SPA navigation (wire:navigate) window.globalAudio stays
                    // alive, so this only does real work after a hard reload / new tab.
                    if (this.audio.src) {
                        this.cur = this.audio.currentTime;
                        this.dur = this.audio.duration || this.dur;
                        this.pct = this.dur ? (this.cur / this.dur) * 100 : 0;
                        this.playing = !this.audio.paused;
                    } else {
                        let d = null;
                        try { d = JSON.parse(localStorage.getItem('tilawa_player') || 'null'); } catch (e) {}
                        if (d && d.src) {
                            document.getElementById('pCover').src = d.cover || '';
                            document.getElementById('pTitle').textContent = d.title || '';
                            document.getElementById('pQari').textContent = d.qari || '';
                            if (d.downloadUrl) {
                                document.getElementById('pDownload').href = d.downloadUrl;
                                document.getElementById('pDownload').style.display = 'inline-block';
                            }
                            document.getElementById('playerBar').classList.remove('hidden');

                            this.currentId = d.id || null;
                            this.fetchLikeStatus();

                            this.audio.src = d.src;
                            this.dur = d.duration || 0;

                            this.audio.addEventListener('loadedmetadata', () => {
                                if (d.time) this.audio.currentTime = d.time;
                            }, { once: true });
                        }
                    }
                },
                savePrefs() {
                    localStorage.setItem('tilawa_prefs', JSON.stringify({ vol: this.vol, muted: this.muted }));
                },
                saveProgress(force = false) {
                    const now = Date.now();
                    if (!force && now - this._lastSave < 1000) return;
                    this._lastSave = now;
                    if (!this.audio.src) return;
                    localStorage.setItem('tilawa_player', JSON.stringify({
                        id: this.currentId,
                        src: this.audio.src,
                        time: this.audio.currentTime,
                        playing: !this.audio.paused,
                        title: document.getElementById('pTitle').textContent,
                        qari: document.getElementById('pQari').textContent,
                        cover: document.getElementById('pCover').src,
                        duration: this.dur,
                        downloadUrl: document.getElementById('pDownload').href || ''
                    }));
                },
                load(d) {
                    document.getElementById('pCover').src = d.cover;
                    document.getElementById('pTitle').textContent = d.title;
                    document.getElementById('pQari').textContent = d.qari;
                    this.currentId = d.id || null;
                    this.fetchLikeStatus();
                    if (d.downloadUrl) {
                        document.getElementById('pDownload').href = d.downloadUrl;
                        document.getElementById('pDownload').style.display = 'inline-block';
                    }
                    document.getElementById('playerBar').classList.remove('hidden');

                    this.audio.pause();

                    if (this.audio.src !== d.url) {
                        this.audio.src = d.url;
                        this.audio.load();
                    }

                    this.audio.volume = this.vol;
                    this.audio.muted = this.muted;
                    this.dur = d.duration || 0;
                    this.audio.play().catch(() => {});
                    this.saveProgress(true);
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
                async fetchLikeStatus() {
                    this.liked = false;
                    if (!this.currentId) return;
                    if (!window._likeEnabled) {
                        this.liked = window.isTilawaLiked(this.currentId);
                        return;
                    }
                    try {
                        const r = await fetch(`/api/like/${this.currentId}`, { headers: { 'Accept': 'application/json' } });
                        if (r.ok) this.liked = (await r.json()).liked;
                    } catch (e) {}
                },
                async toggleLike() {
                    if (!this.currentId || this.likeLoading) return;
                    this.likeLoading = true;
                    await window.toggleTilawaLike(this.currentId);
                    this.likeLoading = false;
                },
                fmt(s) {
                    if (!s || isNaN(s)) return '0:00';
                    return Math.floor(s / 60) + ':' + (Math.floor(s % 60) < 10 ? '0' : '') + Math.floor(
                        s % 60)
                },
            }));

            Alpine.data('navIsland', () => ({
                user: false,
                closeAll() {
                    this.user = false;
                },
            }));

            Alpine.data('quranRadio', () => ({
                audio: null,
                playing: false,
                loading: false,
                stream: 'https://n0e.radiojar.com/8s5u5tpdtwzuv',
                init() {
                    if (!window.quranRadioAudio) {
                        // Keep one Audio element alive for the whole session (survives wire:navigate).
                        // Pre-buffer the live stream up-front so the first click starts instantly.
                        const a = new Audio();
                        a.preload = 'auto';
                        a.src = this.stream;
                        a.load();
                        window.quranRadioAudio = a;
                    }
                    this.audio = window.quranRadioAudio;
                    this.playing = !this.audio.paused && !!this.audio.src;
                    this.audio.addEventListener('playing', () => { this.playing = true; this.loading = false; });
                    this.audio.addEventListener('pause', () => { this.playing = false; });
                    this.audio.addEventListener('waiting', () => { this.loading = true; });
                    this.audio.addEventListener('error', () => { this.playing = false; this.loading = false; });
                },
                toggle() {
                    if (this.playing) {
                        this.audio.pause();
                        return;
                    }
                    // Optimistic UI: flip to loading the instant the user clicks.
                    this.loading = true;
                    if (window.globalAudio && !window.globalAudio.paused) {
                        window.globalAudio.pause();
                    }
                    if (!this.audio.src) {
                        this.audio.src = this.stream;
                    }
                    this.audio.play().catch(() => { this.loading = false; });
                },
            }));
        });
    </script>
    @stack('scripts')
    @livewireScripts
</body>

</html>
