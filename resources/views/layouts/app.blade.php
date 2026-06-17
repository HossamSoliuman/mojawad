<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mojawad') — {{ __('Qur\'an Recitations') }}</title>
    <meta name="description" content="@yield('meta_desc', __('Premium Tajweed recitations.'))">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="Mojawad">
    <meta property="og:title" content="@yield('title', 'Mojawad') — {{ __('Qur\'an Recitations') }}">
    <meta property="og:description" content="@yield('meta_desc', __('Premium Tajweed recitations.'))">
    <meta property="og:image" content="@yield('og_image', asset('images/default-cover.jpg'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body>
    <div class="bg-dots"></div>

    {{-- FLOATING GLASS ISLAND NAV --}}
    <nav class="nav-island z1" x-data="navIsland()" :class="search && 'searching'" @click.outside="closeAll()"
        @open-global-search.window="openSearch()">

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
            <a href="{{ route('likes') }}" wire:navigate
                class="ni-link {{ request()->routeIs('likes') ? 'active' : '' }}" title="{{ __('Likes') }}">
                <i class="fas fa-heart"></i><span>{{ __('Likes') }}</span>
            </a>
        </div>

        {{-- GLOBAL SEARCH --}}
        <div class="ni-search">
            <input x-ref="sq" type="text" class="ni-search-input" x-model="q"
                @input.debounce.300ms="go()" @keydown.escape="closeSearch()"
                placeholder="{{ __('Search qaris & tilawat…') }}" aria-label="{{ __('Search') }}">
            <button type="button" class="ni-icon-btn ni-search-trigger" @click="openSearch()"
                title="{{ __('Search') }}" aria-label="{{ __('Search') }}">
                <i class="fas fa-magnifying-glass"></i>
            </button>
            <button type="button" class="ni-search-close" x-show="search" style="display:none"
                @click="closeSearch()" aria-label="{{ __('Close') }}">
                <i class="fas fa-xmark"></i>
            </button>

            <div class="ni-search-results" x-show="search && q.trim().length >= 2" x-transition.opacity
                style="display:none">
                <template x-if="results.qaris.length">
                    <div>
                        <div class="search-sec-hd">{{ __('Qaris') }}</div>
                        <template x-for="r in results.qaris" :key="'q' + r.id">
                            <a class="search-item" :href="r.url" @click.prevent="goTo(r.url)">
                                <img :src="r.image" class="avatar" width="30" height="30" alt="">
                                <span x-text="r.name"></span>
                            </a>
                        </template>
                    </div>
                </template>
                <template x-if="results.tilawat.length">
                    <div>
                        <div class="search-sec-hd">{{ __('Tilawat') }}</div>
                        <template x-for="r in results.tilawat" :key="'t' + r.id">
                            <a class="search-item" :href="r.url" @click.prevent="goTo(r.url)">
                                <img :src="r.cover" width="30" height="30" style="border-radius:6px;object-fit:cover" alt="">
                                <span style="min-width:0">
                                    <span style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" x-text="r.title"></span>
                                    <span class="gold" style="font-size:.72rem" x-text="r.qari"></span>
                                </span>
                            </a>
                        </template>
                    </div>
                </template>
                <div x-show="!busy && !hasResults" class="search-sec-hd" style="padding:.8rem">
                    {{ __('No results found.') }}
                </div>
                <div x-show="busy" style="text-align:center;padding:.6rem">
                    <i class="fas fa-circle-notch fa-spin gold"></i>
                </div>
            </div>
        </div>

        <div class="ni-actions" :style="user ? 'overflow:visible' : ''">
            {{-- USER --}}
            @auth
                <div class="ni-menu" style="position:relative" @click.outside="user=false">
                    <button type="button" class="ni-avatar-btn" @click="user=!user"
                        title="{{ auth()->user()->name }}">
                        <img src="{{ auth()->user()->avatar_url }}" class="avatar" width="30" height="30" alt="">
                    </button>
                    <div class="dropdown" x-show="user" x-transition style="display:none">
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
            <button class="p-btn" :class="repeat !== 'off' && 'liked'" @click="cycleRepeat()"
                :title="'{{ __('Repeat') }}: ' + repeat">
                <i class="fas fa-repeat"></i><sup x-show="repeat === 'one'" style="font-size:.55rem">1</sup>
            </button>
            <button class="p-btn" @click="prev()" :disabled="!hasPrev() && cur <= 3"
                title="{{ __('Previous') }}"><i class="fas fa-backward-step"></i></button>
            <button class="p-btn" @click="seek(-10)" title="–10s"><i class="fas fa-rotate-left"></i></button>
            <button class="p-btn play" @click="toggle()" :title="playing ? '{{ __('Pause') }}' : '{{ __('Play') }}'">
                <i class="fas" :class="playing ? 'fa-pause' : 'fa-play'"></i>
            </button>
            <button class="p-btn" @click="seek(10)" title="+10s"><i class="fas fa-rotate-right"></i></button>
            <button class="p-btn" @click="next()" :disabled="!hasNext()"
                title="{{ __('Next') }}"><i class="fas fa-forward-step"></i></button>
        </div>
        <div class="p-progress">
            <span class="p-time" x-text="fmt(cur)">0:00</span>
            <div class="p-bar" @click="scrub($event)">
                <div class="p-fill" :style="'width:' + pct + '%'"></div>
            </div>
            <span class="p-time" x-text="fmt(dur)">0:00</span>
        </div>
        <div class="p-vol">
            <button class="p-btn p-rate" @click="cycleRate()" title="{{ __('Playback speed') }}"
                x-text="rate + '×'">1×</button>
            <a href="#" class="p-btn" id="pDownload" title="{{ __('Download') }}"
                style="display:none; color:var(--text2); text-decoration:none;"><i class="fas fa-download"></i></a>
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

        // ── Likes store ──────────────────────────────────────────────────────
        // One Set of liked tilawa ids drives every heart on the page.
        // Guests: persisted in localStorage and merged into the account on login.
        // Signed-in: fetched once per page load from the server.
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

        window._likedIds = window._likeEnabled ? new Set() : window._getGuestLikes();
        window.isTilawaLiked = function (id) {
            return window._likedIds.has(Number(id));
        };

        window._likedIdsReady = (async function () {
            if (!window._likeEnabled) return;
            try {
                const r = await fetch('/api/likes/ids', { headers: { 'Accept': 'application/json' } });
                if (r.ok) {
                    (await r.json()).ids.forEach((id) => window._likedIds.add(Number(id)));
                }
            } catch (e) {}
            window._paintLikeButtons();
        })();

        // Paint every [data-like-btn] heart from the store.
        window._paintLikeButtons = function () {
            document.querySelectorAll('[data-like-btn]').forEach((el) => {
                el.classList.toggle('liked', window.isTilawaLiked(el.dataset.likeBtn));
            });
        };

        // Single source of truth for toggling a like. Broadcasts the new state so
        // every like surface (player heart, buttons, counts) stays in sync.
        window.toggleTilawaLike = async function (id) {
            id = Number(id);
            if (!id) return null;

            const broadcast = (detail) => {
                window.dispatchEvent(new CustomEvent('tilawa-like-changed', { detail }));
                if (window.Livewire) {
                    window.Livewire.dispatch('tilawa-like-changed', { id: detail.id, liked: detail.liked });
                }
            };

            // Not signed in → keep the like locally until they authenticate.
            if (!window._likeEnabled) {
                const liked = !window._likedIds.has(id);
                liked ? window._likedIds.add(id) : window._likedIds.delete(id);
                window._setGuestLikes(window._likedIds);
                const detail = { id, liked, count: null };
                broadcast(detail);
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
                d.liked ? window._likedIds.add(id) : window._likedIds.delete(id);
                broadcast({ id, liked: d.liked, count: d.count });
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
                    likes.forEach((id) => window._likedIds.add(Number(id)));
                    localStorage.removeItem(window._guestLikeKey);
                    window._paintLikeButtons();
                }
            } catch (e) {}
        };
        document.addEventListener('livewire:init', () => {
            window._syncGuestLikes();
            // Repaint hearts + now-playing rows after Livewire swaps list DOM.
            if (window.Livewire && window.Livewire.hook) {
                window.Livewire.hook('morphed', () => {
                    window._paintLikeButtons();
                    window._paintNowPlaying();
                });
            }
        });

        // Keep every visible like-count and heart in sync, app-wide.
        window.addEventListener('tilawa-like-changed', (e) => {
            const { id, count } = e.detail;
            window._paintLikeButtons();
            if (typeof count !== 'number') return;
            document.querySelectorAll(`[data-like-count="${id}"]`).forEach((el) => {
                el.textContent = count.toLocaleString();
            });
        });

        // ── Now-playing highlight ────────────────────────────────────────────
        window._paintNowPlaying = function () {
            const np = window._nowPlaying || {};
            document.querySelectorAll('[data-track-id]').forEach((el) => {
                const match = Number(el.dataset.trackId) === Number(np.id);
                el.classList.toggle('is-current', match);
                el.classList.toggle('is-playing', match && !!np.playing);
            });
        };
        window.addEventListener('tilawa-now-playing', window._paintNowPlaying);
        document.addEventListener('livewire:navigated', () => {
            window._paintNowPlaying();
            window._paintLikeButtons();
        });

        // ── Watch history ────────────────────────────────────────────────────
        // Mirrors the likes store: guests keep history in localStorage; on login
        // it is synced to the account. window._watchHistory is the in-memory
        // source of truth (most-recent first) the "Continue listening" rail reads.
        window._historyKey = 'tilawa_history';
        window._watchHistory = [];

        window._getLocalHistory = function () {
            try {
                return JSON.parse(localStorage.getItem(window._historyKey) || '[]') || [];
            } catch (e) {
                return [];
            }
        };
        window._setLocalHistory = function (arr) {
            localStorage.setItem(window._historyKey, JSON.stringify(arr.slice(0, 50)));
        };

        // Move/insert an entry at the front, deduped by id.
        window._upsertWatch = function (entry) {
            window._watchHistory = [entry, ...window._watchHistory.filter((e) => Number(e.id) !== Number(entry.id))].slice(0, 50);
        };

        window._historyChanged = function () {
            window.dispatchEvent(new CustomEvent('tilawa-history-changed'));
        };

        // Per-track resume positions shared with the audio player.
        window._readPositions = function () {
            try { return JSON.parse(localStorage.getItem('tilawa_positions') || '{}'); } catch (e) { return {}; }
        };

        let _lastHistorySave = 0; // gates the signed-in network POST (15s)
        let _lastHistoryTouch = 0; // gates in-memory/local churn (5s)
        // Single entry point called by the player as playback progresses.
        window._recordWatch = function (track, position, duration, completed, forced) {
            if (!track || !track.id) return;
            position = Math.floor(position || 0);
            duration = Math.floor(duration || track.duration || 0);
            if (!completed && position <= 5) return; // ignore accidental taps

            const now = Date.now();
            if (!forced && !completed && now - _lastHistoryTouch < 5000) return;
            _lastHistoryTouch = now;

            const entry = {
                id: track.id, title: track.title, qari: track.qari, cover: track.cover,
                url: track.url, download: track.download || '', duration,
                position, completed: !!completed, updated_at: Date.now()
            };
            window._upsertWatch(entry);
            window._historyChanged();

            if (!window._likeEnabled) {
                window._setLocalHistory(window._watchHistory);
                return;
            }

            if (!forced && !completed && now - _lastHistorySave < 15000) return;
            _lastHistorySave = now;

            // keepalive lets the request finish even if the page is unloading,
            // while still carrying the CSRF header (unlike navigator.sendBeacon).
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            fetch('/api/history', {
                method: 'POST',
                keepalive: true,
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: entry.id, position, duration, completed: entry.completed })
            }).catch(() => {});
        };

        window._dismissWatch = function (id) {
            id = Number(id);
            window._watchHistory = window._watchHistory.filter((e) => Number(e.id) !== id);
            window._historyChanged();
            if (!window._likeEnabled) {
                window._setLocalHistory(window._watchHistory);
                return;
            }
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            fetch(`/api/history/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).catch(() => {});
        };

        // Load history into memory: signed-in → server; guests → localStorage.
        // Server positions are merged into tilawa_positions so resume works on a
        // fresh device.
        window._refreshWatchHistory = async function () {
            if (!window._likeEnabled) {
                window._watchHistory = window._getLocalHistory();
                window._historyChanged();
                return;
            }
            try {
                const r = await fetch('/api/history', { headers: { 'Accept': 'application/json' } });
                if (r.ok) {
                    const data = await r.json();
                    window._watchHistory = data.items || [];
                    const map = window._readPositions();
                    window._watchHistory.forEach((e) => {
                        if (e.position > 10) map[e.id] = e.position;
                    });
                    localStorage.setItem('tilawa_positions', JSON.stringify(map));
                }
            } catch (e) {}
            window._historyChanged();
        };
        window._watchHistoryReady = window._refreshWatchHistory();
        document.addEventListener('livewire:navigated', () => window._refreshWatchHistory());

        // Push locally-stored guest history into the account once authenticated.
        window._syncGuestHistory = async function () {
            if (!window._likeEnabled) return;
            const items = window._getLocalHistory();
            if (!items.length) return;
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            try {
                const r = await fetch('/api/history/sync', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items })
                });
                if (r.ok) {
                    localStorage.removeItem(window._historyKey);
                    window._refreshWatchHistory();
                }
            } catch (e) {}
        };
        document.addEventListener('livewire:init', () => window._syncGuestHistory());

        // ── Play delegation ──────────────────────────────────────────────────
        // Any element with data-track is playable; clicking it queues every
        // sibling [data-track] inside the nearest [data-queue] container.
        window._parseTrack = function (el) {
            try { return JSON.parse(el.dataset.track); } catch (e) { return null; }
        };
        document.addEventListener('click', (e) => {
            const el = e.target.closest('[data-track]');
            if (!el) return;
            if (e.target.closest('a, [data-like-btn], [data-no-play]')) return;
            e.preventDefault();

            let queue = [window._parseTrack(el)];
            let idx = 0;
            const container = el.closest('[data-queue]');
            if (container) {
                const els = [...container.querySelectorAll('[data-track]')];
                queue = els.map(window._parseTrack).filter(Boolean);
                idx = queue.findIndex((t) => t && t.id === window._parseTrack(el)?.id);
                if (idx < 0) idx = 0;
            }
            if (queue[idx]) window._playerLoad(queue[idx], queue, idx);
        });

        // Legacy entry point (kept for any straggler callers).
        function playTilawa(id, url, title, qari, cover, duration, downloadUrl) {
            window._playerLoad({ id, url, title, qari, cover, duration, download: downloadUrl });
        }

        // ── Keyboard shortcuts ───────────────────────────────────────────────
        if (!window._kbInit) {
            window._kbInit = true;
            document.addEventListener('keydown', (e) => {
                if (e.target.matches('input, textarea, select, [contenteditable]')) return;
                if (e.ctrlKey || e.metaKey || e.altKey) return;
                if (e.key === '/') {
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('open-global-search'));
                    return;
                }
                if (!window.globalAudio || !window.globalAudio.src) return;
                if (e.code === 'Space') {
                    e.preventDefault();
                    window.globalAudio.paused ? window.globalAudio.play() : window.globalAudio.pause();
                } else if (e.key === 'ArrowRight') {
                    window.globalAudio.currentTime = Math.min(window.globalAudio.currentTime + 10, window.globalAudio.duration || 0);
                } else if (e.key === 'ArrowLeft') {
                    window.globalAudio.currentTime = Math.max(window.globalAudio.currentTime - 10, 0);
                }
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
                rate: 1,
                rates: [1, 1.25, 1.5, 1.75, 2, 0.75],
                repeat: 'off',
                queue: [],
                qIdx: -1,
                currentId: null,
                currentTrack: null,
                liked: false,
                likePop: false,
                likeLoading: false,
                _lastSave: 0,
                init() {
                    if (!window.globalAudio) {
                        window.globalAudio = new Audio();
                    }
                    this.audio = window.globalAudio;

                    window._playerLoad = (d, queue, idx) => this.load(d, queue, idx);

                    // ── Restore saved prefs (volume / mute / speed / repeat) ──
                    try {
                        const prefs = JSON.parse(localStorage.getItem('tilawa_prefs') || '{}');
                        if (typeof prefs.vol === 'number') this.vol = prefs.vol;
                        if (typeof prefs.muted === 'boolean') this.muted = prefs.muted;
                        if (typeof prefs.rate === 'number' && this.rates.includes(prefs.rate)) this.rate = prefs.rate;
                        if (['off', 'all', 'one'].includes(prefs.repeat)) this.repeat = prefs.repeat;
                    } catch (e) {}
                    this.audio.volume = this.vol;
                    this.audio.muted = this.muted;
                    this.audio.playbackRate = this.rate;

                    this.$watch('vol', (v) => { this.audio.volume = v; this.savePrefs(); });
                    this.$watch('muted', (m) => { this.audio.muted = m; this.savePrefs(); });

                    this.audio.addEventListener('timeupdate', () => {
                        this.cur = this.audio.currentTime;
                        this.dur = this.audio.duration || this.dur;
                        this.pct = this.dur ? (this.cur / this.dur) * 100 : 0;
                        this.saveProgress();
                    });

                    this.audio.addEventListener('ended', () => this.onEnded());
                    this.audio.addEventListener('play',  () => { this.playing = true;  this.saveProgress(true); this.announce(); });
                    this.audio.addEventListener('pause', () => { this.playing = false; this.saveProgress(true); this.announce(); });
                    window.addEventListener('beforeunload', () => this.saveProgress(true));

                    this.setupMediaSessionHandlers();

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
                        this.announce();
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
                            this.currentTrack = {
                                id: d.id, title: d.title, qari: d.qari, cover: d.cover,
                                url: d.src, download: d.downloadUrl || '', duration: d.duration || 0
                            };
                            this.queue = Array.isArray(d.queue) ? d.queue : [];
                            this.qIdx = typeof d.qIdx === 'number' ? d.qIdx : -1;
                            this.fetchLikeStatus();
                            this.announce();

                            this.audio.src = d.src;
                            this.dur = d.duration || 0;

                            this.audio.addEventListener('loadedmetadata', () => {
                                if (d.time) this.audio.currentTime = d.time;
                            }, { once: true });
                        }
                    }
                },
                savePrefs() {
                    localStorage.setItem('tilawa_prefs', JSON.stringify({
                        vol: this.vol, muted: this.muted, rate: this.rate, repeat: this.repeat
                    }));
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
                        downloadUrl: document.getElementById('pDownload').href || '',
                        queue: this.queue.slice(0, 100),
                        qIdx: this.qIdx
                    }));
                    this.savePosition();
                    this.updatePositionState();
                    if (this.currentTrack) {
                        window._recordWatch(this.currentTrack, this.cur, this.dur, false, force);
                    }
                },
                // ── Per-track resume ──
                _positions() {
                    try { return JSON.parse(localStorage.getItem('tilawa_positions') || '{}'); } catch (e) { return {}; }
                },
                savePosition() {
                    if (!this.currentId || !this.dur) return;
                    const map = this._positions();
                    if (this.cur > 10 && this.cur < this.dur - 15) {
                        map[this.currentId] = Math.floor(this.cur);
                    } else {
                        delete map[this.currentId];
                    }
                    const keys = Object.keys(map);
                    if (keys.length > 80) delete map[keys[0]];
                    localStorage.setItem('tilawa_positions', JSON.stringify(map));
                },
                clearPosition(id) {
                    const map = this._positions();
                    delete map[id];
                    localStorage.setItem('tilawa_positions', JSON.stringify(map));
                },
                load(d, queue = null, idx = null) {
                    if (!d) return;
                    if (Array.isArray(queue) && queue.length) {
                        this.queue = queue;
                        this.qIdx = idx ?? queue.findIndex((t) => t.id === d.id);
                    } else {
                        const found = this.queue.findIndex((t) => t.id === d.id);
                        if (found >= 0) { this.qIdx = found; } else { this.queue = [d]; this.qIdx = 0; }
                    }

                    document.getElementById('pCover').src = d.cover;
                    document.getElementById('pTitle').textContent = d.title;
                    document.getElementById('pQari').textContent = d.qari;
                    this.currentId = d.id || null;
                    this.currentTrack = d;
                    this.fetchLikeStatus();
                    const dl = d.download || d.downloadUrl;
                    if (dl) {
                        document.getElementById('pDownload').href = dl;
                        document.getElementById('pDownload').style.display = 'inline-block';
                    }
                    document.getElementById('playerBar').classList.remove('hidden');

                    this.audio.pause();

                    const resume = this._positions()[d.id] || 0;
                    if (this.audio.src !== d.url) {
                        this.audio.src = d.url;
                        this.audio.load();
                        if (resume > 10) {
                            this.audio.addEventListener('loadedmetadata', () => {
                                if (Number(this.currentId) === Number(d.id)) this.audio.currentTime = resume;
                            }, { once: true });
                        }
                    } else if (resume > 10) {
                        this.audio.currentTime = resume;
                    } else {
                        this.audio.currentTime = 0;
                    }

                    this.audio.volume = this.vol;
                    this.audio.muted = this.muted;
                    this.audio.playbackRate = this.rate;
                    this.dur = d.duration || 0;
                    this.audio.play().catch(() => {});
                    this.setMediaMetadata(d);
                    this.announce();
                    this.saveProgress(true);
                },
                announce() {
                    window._nowPlaying = { id: this.currentId, playing: this.playing };
                    window.dispatchEvent(new CustomEvent('tilawa-now-playing', { detail: window._nowPlaying }));
                },
                // ── Queue ──
                hasNext() {
                    return this.qIdx < this.queue.length - 1 || (this.repeat === 'all' && this.queue.length > 1);
                },
                hasPrev() {
                    return this.qIdx > 0 || (this.repeat === 'all' && this.queue.length > 1);
                },
                next() {
                    if (!this.queue.length) return;
                    let i = this.qIdx + 1;
                    if (i >= this.queue.length) {
                        if (this.repeat !== 'all') return;
                        i = 0;
                    }
                    this.load(this.queue[i], this.queue, i);
                },
                prev() {
                    if (this.audio.currentTime > 3 || !this.queue.length) {
                        this.audio.currentTime = 0;
                        return;
                    }
                    let i = this.qIdx - 1;
                    if (i < 0) {
                        if (this.repeat !== 'all') { this.audio.currentTime = 0; return; }
                        i = this.queue.length - 1;
                    }
                    this.load(this.queue[i], this.queue, i);
                },
                onEnded() {
                    this.playing = false;
                    if (this.currentTrack) {
                        window._recordWatch(this.currentTrack, this.dur, this.dur, true, true);
                    }
                    if (this.currentId) this.clearPosition(this.currentId);
                    if (this.repeat === 'one') {
                        this.audio.currentTime = 0;
                        this.audio.play().catch(() => {});
                        return;
                    }
                    if (this.hasNext()) {
                        this.next();
                        return;
                    }
                    this.saveProgress(true);
                    this.announce();
                },
                cycleRepeat() {
                    this.repeat = { off: 'all', all: 'one', one: 'off' }[this.repeat];
                    this.savePrefs();
                },
                cycleRate() {
                    const i = this.rates.indexOf(this.rate);
                    this.rate = this.rates[(i + 1) % this.rates.length];
                    this.audio.playbackRate = this.rate;
                    this.savePrefs();
                },
                // ── Media Session (lock screen / hardware keys) ──
                setMediaMetadata(d) {
                    if (!('mediaSession' in navigator)) return;
                    try {
                        navigator.mediaSession.metadata = new MediaMetadata({
                            title: d.title || '',
                            artist: d.qari || '',
                            album: 'Mojawad',
                            artwork: d.cover ? [{ src: d.cover, sizes: '512x512', type: 'image/jpeg' }] : []
                        });
                    } catch (e) {}
                },
                setupMediaSessionHandlers() {
                    if (!('mediaSession' in navigator)) return;
                    const set = (action, fn) => {
                        try { navigator.mediaSession.setActionHandler(action, fn); } catch (e) {}
                    };
                    set('play', () => this.audio.play());
                    set('pause', () => this.audio.pause());
                    set('previoustrack', () => this.prev());
                    set('nexttrack', () => this.next());
                    set('seekbackward', () => this.seek(-10));
                    set('seekforward', () => this.seek(10));
                    set('seekto', (e) => { if (typeof e.seekTime === 'number') this.audio.currentTime = e.seekTime; });
                },
                updatePositionState() {
                    if (!('mediaSession' in navigator) || !navigator.mediaSession.setPositionState) return;
                    if (!this.audio.duration || isNaN(this.audio.duration)) return;
                    try {
                        navigator.mediaSession.setPositionState({
                            duration: this.audio.duration,
                            playbackRate: this.audio.playbackRate,
                            position: Math.min(this.audio.currentTime, this.audio.duration)
                        });
                    } catch (e) {}
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
                    let ratio = (e.clientX - r.left) / r.width;
                    if (document.documentElement.dir === 'rtl') ratio = 1 - ratio;
                    this.audio.currentTime = Math.max(0, Math.min(1, ratio)) * this.dur;
                },
                async fetchLikeStatus() {
                    this.liked = false;
                    if (!this.currentId) return;
                    await window._likedIdsReady;
                    this.liked = window.isTilawaLiked(this.currentId);
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
                search: false,
                q: '',
                busy: false,
                results: { qaris: [], tilawat: [] },
                get hasResults() {
                    return this.results.qaris.length || this.results.tilawat.length;
                },
                openSearch() {
                    this.search = true;
                    this.$nextTick(() => this.$refs.sq.focus());
                },
                closeSearch() {
                    this.search = false;
                    this.q = '';
                    this.results = { qaris: [], tilawat: [] };
                },
                closeAll() {
                    this.user = false;
                    this.closeSearch();
                },
                async go() {
                    const q = this.q.trim();
                    if (q.length < 2) {
                        this.results = { qaris: [], tilawat: [] };
                        return;
                    }
                    this.busy = true;
                    try {
                        const r = await fetch('/api/search?q=' + encodeURIComponent(q), {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (r.ok) this.results = await r.json();
                    } catch (e) {} finally {
                        this.busy = false;
                    }
                },
                goTo(url) {
                    this.closeSearch();
                    window.Livewire ? window.Livewire.navigate(url) : (location.href = url);
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

            // ── "Continue listening" rail (home) ──────────────────────────────
            // Reads window._watchHistory (localStorage for guests, server for
            // signed-in) and shows in-progress tracks, Spotify-style.
            Alpine.data('continueListening', () => ({
                items: [],
                load() {
                    this.items = (window._watchHistory || []).filter((i) =>
                        !i.completed && i.position > 5 && (!i.duration || i.position < i.duration - 15)
                    );
                    this.$nextTick(() => window._paintNowPlaying());
                },
                init() {
                    // Refreshes are driven by the `tilawa-history-changed` window
                    // event (bound in markup with .window so Alpine auto-cleans it),
                    // which the global livewire:navigated refresh re-fires.
                    window._watchHistoryReady.then(() => this.load());
                    this.load();
                },
                pct(i) {
                    return i.duration ? Math.min(100, Math.round(i.position / i.duration * 100)) : 0;
                },
                play(idx) {
                    const q = this.items;
                    if (q[idx]) window._playerLoad(q[idx], q, idx);
                },
                dismiss(id) {
                    window._dismissWatch(id);
                },
            }));
        });
    </script>
    @stack('scripts')
    @livewireScripts
</body>

</html>