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
    @livewireScripts
</body>

</html>
