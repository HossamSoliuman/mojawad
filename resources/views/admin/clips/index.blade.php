@extends('layouts.admin')
@section('title', __('Create short video'))
@section('page-title', __('Create short video'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Short videos') }}@endsection
@section('content')

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:1.25rem">
  @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
</div>
@endif

<div x-data="clipEditor({
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
    <div style="margin-bottom:1.4rem">
      <h2 style="font-size:1.1rem;font-weight:700;margin:0 0 .25rem">{{ __('Build a TikTok clip') }}</h2>
      <p style="color:var(--text2);font-size:.86rem;margin:0">{{ __('Pick a tilawa, trim a passage, write the on-video text, then render a ready-to-post vertical video.') }}</p>
    </div>

    {{-- Step 1: pick tilawa --}}
    <div class="form-group" style="position:relative">
      <label class="form-label"><i class="fas fa-music"></i> {{ __('Tilawa') }} <span style="color:var(--red)">*</span></label>
      <template x-if="!selected">
        <div>
          <input type="text" class="form-control" x-model="query" @input.debounce.300ms="search()" placeholder="{{ __('Search a tilawa or qari…') }}">
          <div x-show="results.length" style="position:absolute;z-index:20;left:0;right:0;background:var(--bg2,#15151f);border:1px solid var(--line,#2a2a3a);border-radius:8px;margin-top:.3rem;max-height:280px;overflow:auto;box-shadow:0 8px 24px rgba(0,0,0,.3)">
            <template x-for="r in results" :key="r.id">
              <button type="button" @click="select(r)" style="display:flex;gap:.6rem;align-items:center;width:100%;padding:.55rem .7rem;background:none;border:0;border-bottom:1px solid var(--line,#2a2a3a);cursor:pointer;text-align:start">
                <img :src="r.cover" style="width:36px;height:36px;border-radius:6px;object-fit:cover;flex-shrink:0" alt="">
                <span style="min-width:0">
                  <span style="display:block;font-weight:600;font-size:.85rem;color:var(--text)" x-text="r.title"></span>
                  <span style="display:block;font-size:.72rem;color:var(--text3)" x-text="r.qari + (r.surah ? ' · ' + r.surah : '')"></span>
                </span>
              </button>
            </template>
          </div>
        </div>
      </template>
      <template x-if="selected">
        <div style="display:flex;gap:.7rem;align-items:center;padding:.6rem;background:var(--bg2,#15151f);border:1px solid var(--line,#2a2a3a);border-radius:8px">
          <img :src="selected.cover" style="width:44px;height:44px;border-radius:7px;object-fit:cover" alt="">
          <div style="flex:1;min-width:0">
            <div style="font-weight:600;font-size:.88rem" x-text="selected.title"></div>
            <div style="font-size:.74rem;color:var(--text3)" x-text="selected.qari + (selected.surah ? ' · ' + selected.surah : '')"></div>
          </div>
          <button type="button" class="btn-icon" @click="clearSelection()" title="{{ __('Change') }}"><i class="fas fa-xmark"></i></button>
        </div>
      </template>
    </div>

    {{-- Step 2: trim --}}
    <div class="form-group" x-show="selected" style="display:none">
      <label class="form-label"><i class="fas fa-scissors"></i> {{ __('Clip window') }}</label>

      <div style="display:flex;gap:.4rem;margin-bottom:.7rem">
        <template x-for="p in presets" :key="p">
          <button type="button" class="btn btn-sm" :class="length===p ? 'btn-primary' : 'btn-ghost'" @click="setLength(p)">
            <span x-text="p"></span>{{ __('s') }}
          </button>
        </template>
      </div>

      <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.4rem">
        <button type="button" class="btn-icon" @click="previewClip()"><i class="fas" :class="previewing ? 'fa-stop' : 'fa-play'"></i></button>
        <input type="range" min="0" :max="maxStart" step="1" x-model.number="start" @input="syncAudio()" style="flex:1">
      </div>
      <div style="display:flex;justify-content:space-between;font-size:.74rem;color:var(--text3)">
        <span x-text="'{{ __('Start') }}: ' + fmt(start)"></span>
        <span x-text="fmt(start) + ' → ' + fmt(start + length)"></span>
      </div>
    </div>

    {{-- Step 3: template --}}
    <div class="form-group" x-show="selected" style="display:none">
      <label class="form-label"><i class="fas fa-swatchbook"></i> {{ __('Template') }}</label>
      <div style="display:flex;gap:.4rem">
        <button type="button" class="btn btn-sm" :class="template==='dark-waveform' ? 'btn-primary' : 'btn-ghost'" @click="template='dark-waveform'">{{ __('Dark waveform') }}</button>
        <button type="button" class="btn btn-sm" :class="template==='cover-blur' ? 'btn-primary' : 'btn-ghost'" @click="template='cover-blur'">{{ __('Blurred cover') }}</button>
      </div>
    </div>

    {{-- Step 4: text --}}
    <div class="form-group" x-show="selected" style="display:none">
      <label class="form-label"><i class="fas fa-font"></i> {{ __('Subtitle text') }}</label>
      <textarea class="form-control" rows="3" x-model="subtitle" dir="rtl" placeholder="{{ __('Type the ayah or passage shown on the video…') }}"></textarea>
      <span class="form-hint">{{ __('Shown large and centered over the video.') }}</span>
    </div>

    <div class="form-group" x-show="selected" style="display:none">
      <label class="form-label"><i class="fas fa-align-right"></i> {{ __('TikTok caption') }}</label>
      <textarea class="form-control" rows="2" x-model="caption" dir="rtl" placeholder="{{ __('Suggested description to paste when posting…') }}"></textarea>
    </div>

    {{-- Submit --}}
    <form method="POST" action="{{ route('admin.clips.store') }}" @submit.prevent="render($event)" x-show="selected" style="display:none">
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

  {{-- ── Live preview ────────────────────────────────────────────── --}}
  <div>
    <div style="font-size:.78rem;color:var(--text3);margin-bottom:.5rem;text-align:center">{{ __('Preview (9:16)') }}</div>
    <div style="width:292px;height:520px;overflow:hidden;border-radius:14px;margin:0 auto;border:1px solid var(--line,#2a2a3a)" x-ref="stageWrap">
      {{-- 1080×1920 stage scaled to fit --}}
      <div x-ref="stage" style="width:1080px;height:1920px;transform:scale(.2704);transform-origin:top left;position:relative;overflow:hidden">

        {{-- Background layer (preview only — NOT captured) --}}
        <div style="position:absolute;inset:0">
          <template x-if="template==='cover-blur' && selected">
            <div style="position:absolute;inset:0">
              <img :src="selected.cover" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:blur(40px) brightness(.5);transform:scale(1.2)" alt="">
              <div style="position:absolute;inset:0;background:rgba(0,0,0,.5)"></div>
            </div>
          </template>
          <template x-if="template!=='cover-blur'">
            <div style="position:absolute;inset:0;background:#0b0f14">
              {{-- decorative waveform placeholder --}}
              <div style="position:absolute;top:50%;left:0;right:0;height:300px;transform:translateY(-50%);display:flex;align-items:center;justify-content:center;gap:10px;opacity:.55">
                <template x-for="i in 40" :key="i">
                  <span :style="'width:10px;border-radius:6px;background:linear-gradient(#6ee7b7,#34d399);height:'+(40+Math.abs(Math.sin(i*0.7))*220)+'px'"></span>
                </template>
              </div>
            </div>
          </template>
        </div>

        {{-- Overlay layer (transparent — THIS is captured) --}}
        <div x-ref="overlay" style="position:absolute;inset:0;background:transparent;display:flex;flex-direction:column;padding:120px 90px">
          <div style="flex:1;display:flex;flex-direction:column;justify-content:center;text-align:center">
            <div x-text="subtitle || '{{ __('Your subtitle appears here') }}'"
                 style="font-size:74px;line-height:1.6;font-weight:700;color:#fff;text-shadow:0 2px 18px rgba(0,0,0,.6)" dir="rtl"></div>
            <div x-show="selected" style="margin-top:60px">
              <div x-text="selected?.qari" style="font-size:46px;font-weight:600;color:#34d399"></div>
              <div x-text="selected?.surah" style="font-size:38px;color:rgba(255,255,255,.8);margin-top:12px"></div>
            </div>
          </div>
          {{-- branding bar --}}
          <div style="text-align:center;padding-top:40px">
            <div style="display:inline-flex;align-items:center;gap:18px;background:rgba(0,0,0,.35);padding:24px 44px;border-radius:60px">
              <span style="font-size:40px;color:#34d399"><i class="fas fa-book-open-reader"></i></span>
              <span x-text="cta" style="font-size:36px;color:#fff;font-weight:500" dir="rtl"></span>
            </div>
          </div>
        </div>

      </div>
    </div>
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
    query: '', results: [], selected: null,
    template: cfg.defaultTemplate,
    start: 0, length: cfg.presets[0] ?? 30,
    subtitle: '', caption: '',
    audio: null, previewing: false, rendering: false,
    get maxStart() {
      if (!this.selected) return 0;
      return Math.max(0, this.selected.duration - this.length);
    },
    async search() {
      if (this.query.trim().length < 2) { this.results = []; return; }
      try {
        const res = await fetch(this.cfg.searchUrl + '?q=' + encodeURIComponent(this.query));
        const data = await res.json();
        this.results = data.tilawat || [];
      } catch (e) { this.results = []; }
    },
    select(r) {
      this.selected = r;
      this.results = [];
      this.query = '';
      this.start = 0;
      this.length = Math.min(this.length, r.duration || this.length);
      this.audio = new Audio(r.audio);
    },
    clearSelection() {
      this.stopPreview();
      this.selected = null;
      this.audio = null;
    },
    setLength(p) {
      this.length = p;
      if (this.start > this.maxStart) this.start = this.maxStart;
    },
    syncAudio() {
      if (this.audio) this.audio.currentTime = this.start;
    },
    previewClip() {
      if (!this.audio) return;
      if (this.previewing) { this.stopPreview(); return; }
      this.audio.currentTime = this.start;
      this.audio.play();
      this.previewing = true;
      this._stopAt = setTimeout(() => this.stopPreview(), this.length * 1000);
    },
    stopPreview() {
      if (this.audio) this.audio.pause();
      this.previewing = false;
      if (this._stopAt) clearTimeout(this._stopAt);
    },
    fmt(s) {
      s = Math.floor(s || 0);
      return Math.floor(s / 60) + ':' + (s % 60 < 10 ? '0' : '') + (s % 60);
    },
    async render(ev) {
      if (this.rendering) return;
      this.rendering = true;
      this.stopPreview();
      try {
        const dataUrl = await this.capture();
        this.$refs.overlayInput.value = dataUrl;
        ev.target.submit();
      } catch (e) {
        this.rendering = false;
        alert(@js(__('The video preview could not be captured. Please try again.')));
      }
    },
    async capture() {
      const wrap = this.$refs.stageWrap;
      const stage = this.$refs.stage;
      const prevTransform = stage.style.transform;
      const prevWrapStyle = wrap.getAttribute('style');
      // Render at full 1080×1920 offscreen for a crisp capture.
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
</script>
@endpush
@endsection
