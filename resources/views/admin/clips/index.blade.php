@extends('layouts.admin')
@section('title', __('Create short video'))
@section('page-title', __('Create short video'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Short videos') }}@endsection
@section('content')

<style>
.clip-ed .ce-card{background:var(--surface,#fff);border:1px solid var(--border2,#e5e7eb);border-radius:14px;padding:1.05rem 1.15rem;margin-bottom:1.1rem;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.clip-ed .ce-card h3{font-size:.92rem;font-weight:700;margin:0;display:flex;align-items:center;gap:.5rem;color:var(--text)}
.clip-ed .ce-num{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:var(--gold,#2563eb);color:#fff;font-size:.72rem;font-weight:700;flex-shrink:0}
.clip-ed .ce-hint{color:var(--text2);font-size:.78rem;margin:.35rem 0 .9rem;line-height:1.55}
.clip-ed .ce-track{position:relative;height:96px;border-radius:10px;background:#0b0f14;overflow:hidden;cursor:crosshair;user-select:none;touch-action:none}
.clip-ed .ce-wave{position:absolute;inset:0;width:100%;height:100%;display:block}
.clip-ed .ce-region{position:absolute;top:0;bottom:0;background:rgba(16,185,129,.20);border-left:2px solid #10b981;border-right:2px solid #10b981;box-sizing:border-box;cursor:grab}
.clip-ed .ce-region:active{cursor:grabbing}
.clip-ed .ce-handle{position:absolute;top:0;bottom:0;width:16px;display:flex;align-items:center;justify-content:center;background:#10b981;color:#04221a;cursor:ew-resize;font-size:.6rem}
.clip-ed .ce-handle.l{left:-2px;border-radius:9px 0 0 9px}
.clip-ed .ce-handle.r{right:-2px;border-radius:0 9px 9px 0}
.clip-ed .ce-playhead{position:absolute;top:0;bottom:0;width:2px;background:#fff;pointer-events:none;box-shadow:0 0 8px rgba(255,255,255,.7);z-index:3}
.clip-ed .ce-playhead::after{content:'';position:absolute;top:-1px;left:-4px;width:10px;height:10px;border-radius:50%;background:#fff;box-shadow:0 0 6px rgba(255,255,255,.7)}
.clip-ed .ce-wave-load{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;gap:.5rem;color:rgba(255,255,255,.75);font-size:.78rem;background:rgba(11,15,20,.45)}
.clip-ed .ce-ruler{display:flex;justify-content:space-between;font-size:.72rem;color:var(--text3);margin-top:.4rem;font-variant-numeric:tabular-nums}
.clip-ed .ce-step{display:inline-flex;align-items:center;border:1px solid var(--border2,#d1d5db);border-radius:8px;overflow:hidden;background:#fff}
.clip-ed .ce-step button{width:30px;height:30px;border:0;background:#fff;cursor:pointer;font-size:1.05rem;line-height:1;color:#374151}
.clip-ed .ce-step button:hover{background:#f1f5f9}
.clip-ed .ce-step span{min-width:54px;text-align:center;font-size:.82rem;font-weight:600;font-variant-numeric:tabular-nums;color:var(--text)}
.clip-ed .ce-seg{display:inline-flex;border:1px solid var(--border2,#d1d5db);border-radius:8px;overflow:hidden}
.clip-ed .ce-seg button{padding:.38rem .8rem;background:#fff;border:0;border-inline-start:1px solid var(--border2,#e5e7eb);font-size:.78rem;cursor:pointer;color:#374151;font-weight:500}
.clip-ed .ce-seg button:first-child{border-inline-start:0}
.clip-ed .ce-seg button.on{background:var(--gold,#2563eb);color:#fff}
.clip-ed .ce-swatch{width:28px;height:28px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 1px var(--border2,#d1d5db);cursor:pointer;padding:0}
.clip-ed .ce-swatch.on{box-shadow:0 0 0 2px var(--gold,#2563eb)}
.clip-ed .ce-toggle{display:inline-flex;align-items:center;gap:.55rem;cursor:pointer;font-size:.82rem;color:var(--text);font-weight:500}
.clip-ed .ce-toggle input{width:34px;height:20px;appearance:none;background:#cbd5e1;border-radius:20px;position:relative;cursor:pointer;transition:background .15s;flex-shrink:0}
.clip-ed .ce-toggle input:checked{background:var(--gold,#2563eb)}
.clip-ed .ce-toggle input::after{content:'';position:absolute;top:2px;left:2px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform .15s}
.clip-ed .ce-toggle input:checked::after{transform:translateX(14px)}
.clip-ed .ce-sugg{position:absolute;z-index:20;inset-inline:0;background:var(--surface,#fff);border:1px solid var(--border2,#e5e7eb);border-radius:10px;margin-top:.3rem;max-height:330px;overflow:auto;box-shadow:0 12px 30px rgba(0,0,0,.14)}
.clip-ed .ce-sugg-row{display:flex;gap:.6rem;align-items:center;width:100%;padding:.5rem .65rem;background:none;border:0;border-bottom:1px solid var(--border,#f1f5f9);cursor:pointer;text-align:start}
.clip-ed .ce-sugg-row:hover{background:var(--hover,#f1f5f9)}
.clip-ed .ce-sugg-lbl{font-size:.62rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text3);padding:.5rem .7rem .3rem}
.clip-ed .ce-preview-col{position:sticky;top:1rem}
.clip-ed .ce-stage-frame{position:relative;width:292px;margin:0 auto}
.clip-ed .ce-play-ov{position:absolute;inset:0;border:0;background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;border-radius:14px;z-index:5}
.clip-ed .ce-play-ic{width:62px;height:62px;border-radius:50%;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.35rem;border:2px solid rgba(255,255,255,.65);transition:transform .12s}
.clip-ed .ce-play-ov:hover .ce-play-ic{transform:scale(1.08)}
.clip-ed .ce-seg-prog{position:absolute;left:8px;right:8px;bottom:8px;height:4px;border-radius:4px;background:rgba(255,255,255,.25);overflow:hidden;pointer-events:none;z-index:6}
.clip-ed .ce-seg-prog>div{height:100%;background:#34d399;border-radius:4px;width:0;transition:width .08s linear}
@media(max-width:980px){.clip-editor-grid{grid-template-columns:1fr !important}.clip-ed .ce-preview-col{position:static}}
</style>

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:1.25rem">
  @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
</div>
@endif

<div class="clip-ed" x-data="clipEditor({
        searchUrl: '{{ route('admin.clips.search') }}',
        defaultTemplate: '{{ config('clips.default_template') }}',
        presets: @js($presets),
        maxDuration: {{ (int) $maxDuration }},
        cta: @js(__('Listen to Mojawad on mojawad.org')),
    })"
     style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:2rem;align-items:start;margin-bottom:2.4rem"
     class="clip-editor-grid">

  {{-- ── Controls ────────────────────────────────────────────────── --}}
  <div>
    <div style="margin-bottom:1.2rem">
      <h2 style="font-size:1.1rem;font-weight:700;margin:0 0 .25rem">{{ __('Build a TikTok clip') }}</h2>
      <p style="color:var(--text2);font-size:.86rem;margin:0">{{ __('Pick a tilawa, trim a passage, write the on-video text, then render a ready-to-post vertical video.') }}</p>
    </div>

    {{-- Step 1: pick tilawa --}}
    <div class="ce-card">
      <h3><span class="ce-num">1</span> {{ __('Tilawa') }}</h3>
      <div class="ce-hint">{{ __('Search by surah or reciter, or pick from the latest recordings.') }}</div>

      <div style="position:relative" @click.away="suggOpen=false">
        <template x-if="!selected">
          <div>
            <div style="position:relative">
              <i class="fas fa-magnifying-glass" style="position:absolute;inset-inline-start:.7rem;top:50%;transform:translateY(-50%);color:var(--text3);font-size:.82rem;pointer-events:none"></i>
              <input type="text" class="form-control" style="padding-inline-start:2rem" x-model="query"
                     @focus="openSuggestions()" @input.debounce.300ms="search()"
                     placeholder="{{ __('Search a tilawa or qari…') }}">
            </div>
            <div x-show="suggOpen && results.length" class="ce-sugg" x-transition.opacity>
              <div class="ce-sugg-lbl" x-show="!query.trim()">{{ __('Latest tilawat') }}</div>
              <template x-for="r in results" :key="r.id">
                <button type="button" class="ce-sugg-row" @click="select(r)">
                  <img :src="r.cover" style="width:40px;height:40px;border-radius:7px;object-fit:cover;flex-shrink:0" alt="">
                  <span style="min-width:0;flex:1">
                    <span style="display:block;font-weight:600;font-size:.85rem;color:var(--text)" x-text="r.title"></span>
                    <span style="display:block;font-size:.72rem;color:var(--text3)" x-text="r.qari + (r.surah ? ' · ' + r.surah : '')"></span>
                  </span>
                  <span x-show="r.duration" class="badge badge-muted" style="flex-shrink:0" x-text="fmt(r.duration)"></span>
                </button>
              </template>
            </div>
          </div>
        </template>
        <template x-if="selected">
          <div style="display:flex;gap:.7rem;align-items:center;padding:.6rem;background:var(--surface2,#f8fafc);border:1px solid var(--border2,#e5e7eb);border-radius:10px">
            <img :src="selected.cover" style="width:46px;height:46px;border-radius:8px;object-fit:cover" alt="">
            <div style="flex:1;min-width:0">
              <div style="font-weight:600;font-size:.88rem" x-text="selected.title"></div>
              <div style="font-size:.74rem;color:var(--text3)" x-text="selected.qari + (selected.surah ? ' · ' + selected.surah : '') + ' · ' + fmt(trackDuration)"></div>
            </div>
            <button type="button" class="btn btn-ghost btn-xs" @click="clearSelection()"><i class="fas fa-rotate"></i> {{ __('Change') }}</button>
          </div>
        </template>
      </div>
    </div>

    {{-- Step 2: trim & position --}}
    <div class="ce-card" x-show="selected" style="display:none">
      <h3><span class="ce-num">2</span> {{ __('Trim & position') }}</h3>
      <div class="ce-hint">{{ __('Click the waveform to seek, press play to listen, then mark where the clip starts. Drag the green band or its edges to fine-tune.') }}</div>

      {{-- transport --}}
      <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.7rem;flex-wrap:wrap">
        <button type="button" class="btn-icon" @click="jumpToStart()" title="{{ __('Jump to clip start') }}"><i class="fas fa-backward-step"></i></button>
        <button type="button" class="btn btn-primary btn-sm" @click="togglePlay()" :title="playing ? '{{ __('Pause') }}' : '{{ __('Play') }}'"><i class="fas" :class="playing ? 'fa-pause' : 'fa-play'"></i></button>
        <button type="button" class="btn btn-ghost btn-sm" @click="playSelection()">
          <i class="fas" :class="loopingSelection ? 'fa-stop' : 'fa-repeat'"></i>
          <span x-text="loopingSelection ? '{{ __('Stop') }}' : '{{ __('Loop selection') }}'"></span>
        </button>
        <button type="button" class="btn btn-ghost btn-sm" @click="setStartToPlayhead()" title="{{ __('Set clip start to the playhead') }}"><i class="fas fa-scissors"></i> {{ __('Start clip here') }}</button>
        <span style="margin-inline-start:auto;font-size:.78rem;color:var(--text2);font-variant-numeric:tabular-nums" x-text="fmt(playhead) + ' / ' + fmt(trackDuration)"></span>
      </div>

      {{-- waveform --}}
      <div class="ce-track" x-ref="track" @pointerdown="onTrackDown($event)">
        <canvas x-ref="wave" class="ce-wave"></canvas>
        <div class="ce-region" :style="`left:${startPct}%;width:${lenPct}%`" @pointerdown.stop="beginDrag('move',$event)">
          <div class="ce-handle l" @pointerdown.stop="beginDrag('start',$event)"><i class="fas fa-grip-lines-vertical"></i></div>
          <div class="ce-handle r" @pointerdown.stop="beginDrag('end',$event)"><i class="fas fa-grip-lines-vertical"></i></div>
        </div>
        <div class="ce-playhead" :style="`left:${playheadPct}%`"></div>
        <div class="ce-wave-load" x-show="waveLoading"><i class="fas fa-spinner fa-spin"></i> {{ __('Generating waveform…') }}</div>
      </div>
      <div class="ce-ruler">
        <span>0:00</span>
        <span style="color:#10b981;font-weight:600" x-text="fmt(start) + ' → ' + fmt(selEnd)"></span>
        <span x-text="fmt(trackDuration)"></span>
      </div>

      {{-- steppers + presets --}}
      <div style="display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;margin-top:.9rem">
        <span class="form-label" style="margin:0">{{ __('Start') }}</span>
        <div class="ce-step">
          <button type="button" @click="nudgeStart(-1)">−</button>
          <span x-text="fmt(start)"></span>
          <button type="button" @click="nudgeStart(1)">+</button>
        </div>
        <span class="form-label" style="margin:0;margin-inline-start:.4rem">{{ __('Length') }}</span>
        <div class="ce-step">
          <button type="button" @click="nudgeLength(-1)">−</button>
          <span x-text="length + '{{ __('s') }}'"></span>
          <button type="button" @click="nudgeLength(1)">+</button>
        </div>
      </div>
      <div style="display:flex;gap:.4rem;margin-top:.7rem">
        <template x-for="p in presets" :key="p">
          <button type="button" class="btn btn-sm" :class="length===p ? 'btn-primary' : 'btn-ghost'" @click="setLength(p)">
            <span x-text="p"></span>{{ __('s') }}
          </button>
        </template>
      </div>
    </div>

    {{-- Step 3: text & style --}}
    <div class="ce-card" x-show="selected" style="display:none">
      <h3><span class="ce-num">3</span> {{ __('Text & style') }}</h3>

      <div class="form-group" style="margin-top:.7rem">
        <label class="form-label"><i class="fas fa-font"></i> {{ __('Subtitle text') }}</label>
        <textarea class="form-control" rows="3" x-model="subtitle" dir="rtl" placeholder="{{ __('Type the ayah or passage shown on the video…') }}"></textarea>
      </div>

      <div style="display:flex;gap:1.4rem;flex-wrap:wrap;align-items:flex-start;margin-bottom:.9rem">
        <div>
          <label class="form-label" style="display:block;margin-bottom:.35rem">{{ __('Text size') }}</label>
          <div class="ce-seg">
            <button type="button" :class="fontKey==='s'?'on':''" @click="fontKey='s'">{{ __('Small') }}</button>
            <button type="button" :class="fontKey==='m'?'on':''" @click="fontKey='m'">{{ __('Medium') }}</button>
            <button type="button" :class="fontKey==='l'?'on':''" @click="fontKey='l'">{{ __('Large') }}</button>
          </div>
        </div>
        <div>
          <label class="form-label" style="display:block;margin-bottom:.35rem">{{ __('Position') }}</label>
          <div class="ce-seg">
            <button type="button" :class="position==='top'?'on':''" @click="position='top'">{{ __('Top') }}</button>
            <button type="button" :class="position==='center'?'on':''" @click="position='center'">{{ __('Center') }}</button>
            <button type="button" :class="position==='bottom'?'on':''" @click="position='bottom'">{{ __('Bottom') }}</button>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:1.4rem;flex-wrap:wrap;align-items:center">
        <div>
          <label class="form-label" style="display:block;margin-bottom:.4rem">{{ __('Accent color') }}</label>
          <div style="display:flex;gap:.5rem">
            <template x-for="c in accents" :key="c">
              <button type="button" class="ce-swatch" :class="accent===c?'on':''" :style="`background:${c}`" @click="accent=c"></button>
            </template>
          </div>
        </div>
        <label class="ce-toggle" style="margin-top:1.1rem">
          <input type="checkbox" x-model="showBrand">
          {{ __('Show branding bar') }}
        </label>
      </div>
    </div>

    {{-- Step 4: caption + render --}}
    <div class="ce-card" x-show="selected" style="display:none">
      <h3><span class="ce-num">4</span> {{ __('TikTok caption') }}</h3>
      <textarea class="form-control" style="margin-top:.7rem" rows="2" x-model="caption" dir="rtl" placeholder="{{ __('Suggested description to paste when posting…') }}"></textarea>

      <form method="POST" action="{{ route('admin.clips.store') }}" @submit.prevent="render($event)" style="margin-top:1rem">
        @csrf
        <input type="hidden" name="tilawa_id" :value="selected?.id">
        <input type="hidden" name="template" :value="template">
        <input type="hidden" name="clip_start" :value="start">
        <input type="hidden" name="clip_duration" :value="length">
        <input type="hidden" name="caption_ar" :value="caption">
        <input type="hidden" name="overlay" x-ref="overlayInput">
        <button type="submit" class="btn btn-primary" :disabled="rendering">
          <i class="fas" :class="rendering ? 'fa-spinner fa-spin' : 'fa-clapperboard'"></i>
          <span x-text="rendering ? '{{ __('Preparing…') }}' : '{{ __('Render video') }}'"></span>
        </button>
      </form>
    </div>
  </div>

  {{-- ── Live preview ────────────────────────────────────────────── --}}
  <div class="ce-preview-col">
    <div style="font-size:.78rem;color:var(--text3);margin-bottom:.5rem;text-align:center">{{ __('Preview (9:16)') }}</div>
    <div class="ce-stage-frame">
    <div style="width:292px;height:520px;overflow:hidden;border-radius:14px;margin:0 auto;border:1px solid var(--border2,#2a2a3a)" x-ref="stageWrap">
      {{-- 1080×1920 stage scaled to fit --}}
      <div x-ref="stage" style="width:1080px;height:1920px;transform:scale(.2704);transform-origin:top left;position:relative;overflow:hidden">

        {{-- Background layer (preview only — NOT captured): sharp cover fills the frame.
             The legibility gradient lives in the captured overlay below, so the rendered
             .mp4 (cover via ffmpeg + overlay PNG) matches this preview exactly. --}}
        <div style="position:absolute;inset:0;background:#0b0f14">
          <img x-show="selected" :src="selected?.cover" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover" alt="">
        </div>

        {{-- Overlay layer (THIS is captured). Holds the top/bottom legibility gradient
             so it bakes into the PNG and the rendered video matches the preview. --}}
        <div x-ref="overlay" style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.6) 0%,rgba(0,0,0,.12) 24%,rgba(0,0,0,.12) 55%,rgba(0,0,0,.9) 100%);display:flex;flex-direction:column;padding:120px 90px">
          <div :style="`flex:1;display:flex;flex-direction:column;justify-content:${posJustify};text-align:center`">
            <div x-text="subtitle" :style="`font-size:${fontPx}px;line-height:1.55;font-weight:700;color:#fff;text-shadow:0 2px 18px rgba(0,0,0,.6)`" dir="rtl"></div>
            <div x-show="selected" style="margin-top:60px">
              <div x-text="selected?.qari" :style="`font-size:46px;font-weight:600;color:${accent}`"></div>
              <div x-text="selected?.surah" style="font-size:38px;color:rgba(255,255,255,.8);margin-top:12px"></div>
            </div>
          </div>
          {{-- branding bar --}}
          <div x-show="showBrand" style="text-align:center;padding-top:40px">
            <div style="display:inline-flex;align-items:center;gap:18px;background:rgba(0,0,0,.35);padding:24px 44px;border-radius:60px">
              <span :style="`font-size:40px;color:${accent}`"><i class="fas fa-book-open-reader"></i></span>
              <span x-text="cta" style="font-size:36px;color:#fff;font-weight:500" dir="rtl"></span>
            </div>
          </div>
        </div>

        {{-- Empty-state hint (preview only — sibling of overlay, never captured) --}}
        <div x-show="selected && !subtitle" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:0 120px;pointer-events:none">
          <span style="font-size:54px;color:rgba(255,255,255,.45);text-align:center" dir="rtl">{{ __('Your subtitle appears here') }}</span>
        </div>

      </div>
    </div>
    {{-- play overlay + segment progress (siblings of the captured stage, never captured) --}}
    <button type="button" class="ce-play-ov" x-show="selected" @click="playSelection()" :title="playing ? '{{ __('Pause') }}' : '{{ __('Play preview') }}'">
      <span class="ce-play-ic" x-show="!playing"><i class="fas fa-play" style="margin-inline-start:3px"></i></span>
    </button>
    <div class="ce-seg-prog" x-show="selected"><div :style="`width:${segProgress}%`"></div></div>
    </div>{{-- /.ce-stage-frame --}}
  </div>

</div>

<livewire:admin.clip-queue />

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function clipEditor(cfg) {
  return {
    cfg,
    cta: cfg.cta,
    presets: cfg.presets,
    accents: ['#34d399', '#fbbf24', '#38bdf8', '#fb7185', '#ffffff'],
    minLength: 3,
    defaultLength: 30,

    query: '', results: [], suggOpen: false, selected: null,
    template: cfg.defaultTemplate,
    start: 0, length: 30, playhead: 0,
    subtitle: '', caption: '',
    fontKey: 'm', position: 'center', accent: '#34d399', showBrand: true,
    audio: null, peaks: null, waveLoading: false,
    playing: false, loopingSelection: false,
    _audioDur: 0, _ticking: false,

    init() {
      this._onResize = () => { this.drawWave(); };
      window.addEventListener('resize', this._onResize);
      document.addEventListener('livewire:navigating', () => {
        this.stop();
        window.removeEventListener('resize', this._onResize);
      }, { once: true });
    },

    /* ── derived ─────────────────────────────────────────── */
    get trackDuration() { return this.selected ? (this.selected.duration || this._audioDur || 0) : 0; },
    get maxLength() { return Math.min(this.cfg.maxDuration, this.trackDuration || this.cfg.maxDuration); },
    get maxStart() { return Math.max(0, this.trackDuration - this.length); },
    get selEnd() { return Math.min(this.start + this.length, this.trackDuration || (this.start + this.length)); },
    get startPct() { const d = this.trackDuration; return d ? (this.start / d) * 100 : 0; },
    get lenPct() { const d = this.trackDuration; return d ? Math.min(100 - this.startPct, (this.length / d) * 100) : 0; },
    get playheadPct() { const d = this.trackDuration; return d ? (Math.min(this.playhead, d) / d) * 100 : 0; },
    get fontPx() { return ({ s: 58, m: 74, l: 92 })[this.fontKey] || 74; },
    get posJustify() { return ({ top: 'flex-start', center: 'center', bottom: 'flex-end' })[this.position] || 'center'; },
    get segProgress() { return this.clamp(((this.playhead - this.start) / (this.length || 1)) * 100, 0, 100); },

    clamp(v, a, b) { return Math.max(a, Math.min(b, v)); },

    /* ── tilawa picker ───────────────────────────────────── */
    openSuggestions() {
      this.suggOpen = true;
      if (!this.results.length) this.search();
    },
    async search() {
      try {
        const res = await fetch(this.cfg.searchUrl + '?q=' + encodeURIComponent(this.query));
        const data = await res.json();
        this.results = data.tilawat || [];
        this.suggOpen = true;
      } catch (e) { this.results = []; }
    },
    select(r) {
      this.stop();
      this.selected = r;
      this.results = []; this.query = ''; this.suggOpen = false;
      this._audioDur = 0; this.playhead = 0; this.start = 0;
      this.length = this.clamp(this.defaultLength, this.minLength, Math.min(this.cfg.maxDuration, r.duration || this.cfg.maxDuration));
      this.peaks = null;
      this.audio = new Audio(r.audio);
      this.audio.preload = 'auto';
      this.audio.addEventListener('play', () => this.onPlay());
      this.audio.addEventListener('pause', () => this.onPause());
      this.audio.addEventListener('ended', () => this.onPause());
      this.audio.addEventListener('loadedmetadata', () => {
        if (!r.duration && this.audio.duration) this._audioDur = Math.floor(this.audio.duration);
      });
      this.$nextTick(() => { this.drawWave(); this.buildWaveform(r.audio); });
    },
    clearSelection() {
      this.stop();
      this.selected = null; this.audio = null; this.peaks = null; this.suggOpen = false;
    },

    /* ── trim controls ───────────────────────────────────── */
    setLength(p) {
      this.length = this.clamp(p, this.minLength, this.maxLength);
      if (this.start > this.maxStart) this.start = this.maxStart;
    },
    nudgeLength(d) { this.setLength(this.length + d); },
    nudgeStart(d) { this.start = this.clamp(this.start + d, 0, this.maxStart); },
    setStartToPlayhead() { this.start = this.clamp(Math.round(this.playhead), 0, this.maxStart); },
    jumpToStart() {
      this.playhead = this.start;
      if (this.audio) this.audio.currentTime = this.start;
    },

    /* ── transport ───────────────────────────────────────── */
    onPlay() { this.playing = true; if (!this._ticking) { this._ticking = true; this.tick(); } },
    onPause() { this.playing = false; this._ticking = false; },
    tick() {
      if (!this.audio || this.audio.paused) { this._ticking = false; return; }
      this.playhead = this.audio.currentTime;
      if (this.loopingSelection && this.playhead >= this.selEnd) this.audio.currentTime = this.start;
      requestAnimationFrame(() => this.tick());
    },
    togglePlay() {
      if (!this.audio) return;
      if (!this.audio.paused) { this.audio.pause(); return; }
      this.loopingSelection = false;
      this.audio.play().catch(() => {});
    },
    playSelection() {
      if (!this.audio) return;
      if (this.loopingSelection && !this.audio.paused) { this.audio.pause(); this.loopingSelection = false; return; }
      this.loopingSelection = true;
      this.audio.currentTime = this.start;
      this.playhead = this.start;
      this.audio.play().catch(() => {});
    },
    stop() {
      if (this.audio) this.audio.pause();
      this.playing = false; this.loopingSelection = false; this._ticking = false;
    },

    /* ── seeking & dragging ──────────────────────────────── */
    seekToClientX(clientX) {
      const d = this.trackDuration; if (!d) return;
      const rect = this.$refs.track.getBoundingClientRect();
      const t = this.clamp(((clientX - rect.left) / rect.width) * d, 0, d);
      this.playhead = t;
      this.loopingSelection = false;
      if (this.audio) this.audio.currentTime = t;
    },
    onTrackDown(e) {
      this.seekToClientX(e.clientX);
      const move = (ev) => this.seekToClientX(ev.clientX);
      const up = () => { window.removeEventListener('pointermove', move); window.removeEventListener('pointerup', up); };
      window.addEventListener('pointermove', move);
      window.addEventListener('pointerup', up);
    },
    beginDrag(mode, e) {
      e.preventDefault();
      const d = this.trackDuration; if (!d) return;
      const rect = this.$refs.track.getBoundingClientRect();
      const pxToSec = d / rect.width;
      const startX = e.clientX, s0 = this.start, l0 = this.length;
      let moved = false;
      const move = (ev) => {
        const dx = (ev.clientX - startX) * pxToSec;
        if (Math.abs(ev.clientX - startX) > 2) moved = true;
        if (mode === 'move') {
          this.start = this.clamp(Math.round(s0 + dx), 0, d - this.length);
        } else if (mode === 'start') {
          const ns = this.clamp(Math.round(s0 + dx), 0, s0 + l0 - this.minLength);
          this.length = this.clamp(s0 + l0 - ns, this.minLength, this.maxLength);
          this.start = ns;
        } else if (mode === 'end') {
          const ne = this.clamp(Math.round(s0 + l0 + dx), s0 + this.minLength, d);
          this.length = this.clamp(ne - s0, this.minLength, this.maxLength);
        }
      };
      const up = (ev) => {
        if (!moved && mode === 'move') this.seekToClientX(ev.clientX);
        window.removeEventListener('pointermove', move);
        window.removeEventListener('pointerup', up);
      };
      window.addEventListener('pointermove', move);
      window.addEventListener('pointerup', up);
    },

    /* ── waveform ────────────────────────────────────────── */
    async buildWaveform(url) {
      this.waveLoading = true;
      try {
        const resp = await fetch(url);
        const buf = await resp.arrayBuffer();
        if (buf.byteLength > 40 * 1024 * 1024) { this.peaks = null; this.waveLoading = false; this.drawWave(); return; }
        const Ctx = window.AudioContext || window.webkitAudioContext;
        const ctx = new Ctx();
        const audioBuf = await ctx.decodeAudioData(buf);
        if (!this.selected?.duration && audioBuf.duration) this._audioDur = Math.floor(audioBuf.duration);
        const raw = audioBuf.getChannelData(0);
        const samples = 800;
        const block = Math.max(1, Math.floor(raw.length / samples));
        const step = Math.max(1, Math.floor(block / 200));
        const peaks = new Array(samples);
        let norm = 0;
        for (let i = 0; i < samples; i++) {
          let m = 0; const o = i * block;
          for (let j = 0; j < block; j += step) { const v = Math.abs(raw[o + j] || 0); if (v > m) m = v; }
          peaks[i] = m; if (m > norm) norm = m;
        }
        norm = norm || 1;
        this.peaks = peaks.map(p => p / norm);
        ctx.close();
      } catch (e) {
        this.peaks = null;
      }
      this.waveLoading = false;
      this.drawWave();
    },
    drawWave() {
      const canvas = this.$refs.wave, wrap = this.$refs.track;
      if (!canvas || !wrap) return;
      const dpr = window.devicePixelRatio || 1;
      const w = wrap.clientWidth, h = wrap.clientHeight;
      if (!w || !h) return;
      canvas.width = w * dpr; canvas.height = h * dpr;
      canvas.style.width = w + 'px'; canvas.style.height = h + 'px';
      const ctx = canvas.getContext('2d');
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.clearRect(0, 0, w, h);
      const mid = h / 2;
      if (!this.peaks) {
        ctx.fillStyle = 'rgba(148,163,184,.35)';
        ctx.fillRect(0, mid - 1, w, 2);
        return;
      }
      const n = this.peaks.length, barW = w / n;
      ctx.fillStyle = 'rgba(148,163,184,.6)';
      for (let i = 0; i < n; i++) {
        const ph = Math.max(2, this.peaks[i] * (h * 0.86));
        ctx.fillRect(i * barW, mid - ph / 2, Math.max(1, barW * 0.66), ph);
      }
    },
    fmt(s) {
      s = Math.floor(s || 0);
      return Math.floor(s / 60) + ':' + (s % 60 < 10 ? '0' : '') + (s % 60);
    },

    /* ── render ──────────────────────────────────────────── */
    async render(ev) {
      if (this.rendering) return;
      this.rendering = true;
      this.stop();
      try {
        const dataUrl = await this.capture();
        this.$refs.overlayInput.value = dataUrl;
        ev.target.submit();
      } catch (e) {
        this.rendering = false;
        alert(@js(__('The video preview could not be captured. Please try again.')));
      }
    },
    rendering: false,
    async capture() {
      const wrap = this.$refs.stageWrap, stage = this.$refs.stage;
      const prevTransform = stage.style.transform;
      const prevWrapStyle = wrap.getAttribute('style');
      stage.style.transform = 'none';
      wrap.style.cssText = 'position:fixed;left:-99999px;top:0;width:1080px;height:1920px;overflow:hidden';
      const canvas = await html2canvas(this.$refs.overlay, {
        backgroundColor: null, scale: 1, width: 1080, height: 1920,
        windowWidth: 1080, windowHeight: 1920, useCORS: true,
      });
      stage.style.transform = prevTransform;
      wrap.setAttribute('style', prevWrapStyle);
      return canvas.toDataURL('image/png');
    },
  };
}

/* Modal player for already-rendered clips (used by the clip-queue component).
   The dialog is teleported to the body element so the queue's wire:poll never
   re-morphs the playing video. */
function clipPreview() {
  return {
    open: false, url: '', poster: '', title: '', download: '',
    show(url, poster, title, download) {
      this.url = url; this.poster = poster || ''; this.title = title || ''; this.download = download || '';
      this.open = true;
      this.$nextTick(() => { const v = this.$refs.vid; if (v) { v.currentTime = 0; v.play().catch(() => {}); } });
    },
    close() {
      const v = this.$refs.vid; if (v) v.pause();
      this.open = false; this.url = '';
    },
  };
}
</script>
@endpush
@endsection
