@extends('layouts.uploader')
@section('title', __('Upload Tilawat'))

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
body { padding-bottom: 0; }
.up-shell { width: min(100%, 1240px); max-width: none; margin: 0 auto; padding: 0 1.25rem 5rem; }
.up-topbar { display: flex; align-items: center; justify-content: space-between; min-height: 72px; padding: .85rem 0; }
.up-main { min-width: 0; }
.up-user { display: flex; align-items: center; gap: .5rem; min-width: 0; }
.up-user-name { max-width: 9rem; overflow: hidden; color: var(--text2); font-size: .78rem; text-overflow: ellipsis; white-space: nowrap; }

.tilawa-create {
  --tu-ink: #13241e;
  --tu-ink-soft: #52635c;
  --tu-green: #1b7555;
  --tu-green-dark: #0f4d39;
  --tu-mint: #dff0e7;
  --tu-cream: #f7f3e9;
  --tu-paper: #fffdf7;
  --tu-line: #dcd7ca;
  --tu-gold: #d9aa55;
  color: var(--tu-ink);
  font-family: 'Manrope', 'Noto Sans Arabic', sans-serif;
}

[dir="rtl"] .tilawa-create { font-family: 'Noto Sans Arabic', 'Manrope', sans-serif; }

.tu-hero {
  position: relative;
  overflow: hidden;
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(250px, .65fr);
  align-items: end;
  gap: 2rem;
  min-height: 310px;
  padding: clamp(1.75rem, 5vw, 4.25rem);
  border: 1px solid rgba(255,255,255,.09);
  border-radius: 30px;
  background:
    radial-gradient(circle at 82% 22%, rgba(217,170,85,.2), transparent 22%),
    linear-gradient(128deg, #0d241c 0%, #123c2c 55%, #1b694d 100%);
  box-shadow: 0 30px 80px rgba(0,0,0,.28);
}

.tu-hero::before {
  content: '';
  position: absolute;
  width: 420px;
  height: 420px;
  inset-inline-end: -100px;
  top: -160px;
  border: 1px solid rgba(255,255,255,.11);
  border-radius: 50%;
  box-shadow: 0 0 0 52px rgba(255,255,255,.025), 0 0 0 104px rgba(255,255,255,.018);
}

.tu-hero-copy, .tu-hero-card { position: relative; z-index: 1; }
.tu-eyebrow { display: flex; align-items: center; gap: .65rem; margin-bottom: 1rem; color: #b6dccd; font-size: .68rem; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; }
.tu-eyebrow::before { content: ''; width: 30px; height: 1px; background: currentColor; }
.tu-hero h1 { max-width: 760px; margin: 0; color: #fff; font-family: inherit; font-size: clamp(2rem, 5vw, 4.2rem); font-weight: 700; line-height: 1.07; letter-spacing: -.055em; }
[dir="rtl"] .tu-hero h1 { line-height: 1.35; letter-spacing: 0; }
.tu-hero-copy p { max-width: 650px; margin: 1rem 0 0; color: rgba(255,255,255,.68); font-size: clamp(.84rem, 1.7vw, 1rem); line-height: 1.8; }
.tu-hero-card { padding: 1.15rem; border: 1px solid rgba(255,255,255,.16); border-radius: 18px; background: rgba(255,255,255,.075); color: #fff; backdrop-filter: blur(14px); }
.tu-hero-card-head { display: flex; align-items: center; gap: .65rem; color: #d8ecdf; font-size: .72rem; font-weight: 800; }
.tu-hero-card-head i { color: var(--tu-gold); }
.tu-hero-card p { margin: .6rem 0 0; color: rgba(255,255,255,.62); font-size: .7rem; line-height: 1.7; }
.tu-specs { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .85rem; }
.tu-spec { padding: .32rem .5rem; border-radius: 999px; background: rgba(255,255,255,.09); color: rgba(255,255,255,.76); font-size: .59rem; font-weight: 700; }

.tu-workspace { display: grid; grid-template-columns: 260px minmax(0, 1fr); align-items: start; gap: 1.25rem; margin-top: 1.25rem; }
.tu-rail { position: sticky; top: 1.25rem; padding: 1.35rem; border: 1px solid rgba(255,255,255,.09); border-radius: 22px; background: #202622; box-shadow: 0 16px 35px rgba(0,0,0,.16); }
.tu-rail-title { margin: 0; color: #fff; font-family: inherit; font-size: .92rem; font-weight: 800; }
.tu-rail-sub { margin: .3rem 0 0; color: rgba(255,255,255,.45); font-size: .66rem; line-height: 1.55; }
.tu-steps { position: relative; display: flex; flex-direction: column; gap: 0; margin: 1.35rem 0; padding: 0; list-style: none; }
.tu-steps::before { content: ''; position: absolute; top: 24px; bottom: 24px; inset-inline-start: 16px; width: 1px; background: rgba(255,255,255,.1); }
.tu-step { position: relative; display: grid; grid-template-columns: 33px 1fr; gap: .75rem; padding: .65rem 0; color: rgba(255,255,255,.42); }
.tu-step-dot { position: relative; z-index: 1; display: grid; place-items: center; width: 33px; height: 33px; border: 1px solid rgba(255,255,255,.14); border-radius: 50%; background: #202622; color: rgba(255,255,255,.5); font-size: .66rem; font-weight: 800; transition: .2s; }
.tu-step strong { display: block; padding-top: .1rem; color: inherit; font-size: .72rem; }
.tu-step small { display: block; margin-top: .14rem; color: inherit; font-size: .59rem; line-height: 1.45; opacity: .72; }
.tu-step.is-active { color: #fff; }
.tu-step.is-active .tu-step-dot { border-color: #79b69e; color: #a9d6c4; box-shadow: 0 0 0 4px rgba(121,182,158,.1); }
.tu-step.is-complete .tu-step-dot { border-color: var(--tu-green); background: var(--tu-green); color: #fff; }
.tu-step.is-complete .tu-step-dot span { display: none; }
.tu-step.is-complete .tu-step-dot::after { content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900; }
.tu-rail-note { display: flex; align-items: flex-start; gap: .6rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,.09); color: rgba(255,255,255,.5); font-size: .61rem; line-height: 1.55; }
.tu-rail-note i { margin-top: .12rem; color: var(--tu-gold); }

.tu-stages { display: flex; flex-direction: column; gap: 1.25rem; min-width: 0; }
.tu-stage { overflow: hidden; border: 1px solid var(--tu-line); border-radius: 22px; background: var(--tu-paper); box-shadow: 0 12px 32px rgba(0,0,0,.12); }
.tu-stage-head { display: flex; align-items: flex-start; gap: .85rem; padding: 1.35rem 1.5rem 1.1rem; border-bottom: 1px solid #e9e4d9; }
.tu-stage-no { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 11px; background: var(--tu-ink); color: #fff; font-size: .69rem; font-weight: 800; flex-shrink: 0; }
.tu-stage h2 { margin: 0; color: var(--tu-ink); font-family: inherit; font-size: 1rem; font-weight: 800; line-height: 1.4; }
.tu-stage-head p { margin: .22rem 0 0; color: var(--tu-ink-soft); font-size: .7rem; line-height: 1.55; }
.tu-stage-body { padding: 1.5rem; }

.tu-field-label { display: block; margin-bottom: .5rem; color: var(--tu-ink); font-size: .7rem; font-weight: 800; }
.qari-search { display: flex; align-items: center; gap: .7rem; min-height: 52px; padding: .7rem 1rem; border: 1px solid #d3d2ca; border-radius: 13px; background: #fff; transition: .18s; }
.qari-search:focus-within { border-color: var(--tu-green); box-shadow: 0 0 0 4px rgba(27,117,85,.1); }
.qari-search i { color: var(--tu-green); }
.qari-search input { width: 100%; min-width: 0; border: 0; outline: 0; background: transparent; color: var(--tu-ink); font-family: inherit; font-size: .82rem; font-weight: 600; line-height: 1.4; }
.qari-search input::placeholder { color: #99a29e; }
.qari-results { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; max-height: 330px; margin-top: .85rem; overflow: auto; scrollbar-width: thin; }
.qari-opt { display: flex; align-items: center; gap: .7rem; min-width: 0; padding: .65rem; border: 1px solid #dddbd3; border-radius: 13px; background: #fbfaf6; color: var(--tu-ink); font-family: inherit; text-align: start; transition: .16s; }
.qari-opt:hover, .qari-opt:focus-visible { border-color: var(--tu-green); background: var(--tu-mint); outline: none; transform: translateY(-1px); }
.qari-opt img { width: 42px; height: 42px; border-radius: 12px; object-fit: cover; flex-shrink: 0; }
.qari-opt span { min-width: 0; overflow: hidden; font-size: .72rem; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.qari-empty { grid-column: 1 / -1; padding: 1.25rem; border: 1px dashed #d6d4cc; border-radius: 13px; color: var(--tu-ink-soft); font-size: .72rem; text-align: center; }
.qari-card { display: flex; align-items: center; gap: .9rem; min-height: 78px; padding: .8rem; border: 1px solid #a8c9bb; border-radius: 15px; background: var(--tu-mint); }
.qari-card img { width: 56px; height: 56px; border-radius: 14px; object-fit: cover; flex-shrink: 0; }
.qari-card-info { flex: 1; min-width: 0; }
.qari-card-name { overflow: hidden; color: var(--tu-ink); font-size: .86rem; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.qari-card-sub { margin-top: .22rem; color: var(--tu-ink-soft); font-size: .61rem; line-height: 1.45; }
.qari-card-sub i { color: var(--tu-green); }
.tu-change { display: inline-flex; align-items: center; gap: .4rem; min-height: 38px; padding: .55rem .75rem; border: 1px solid #a6b8b0; border-radius: 10px; background: rgba(255,255,255,.65); color: var(--tu-green-dark); font-family: inherit; font-size: .65rem; font-weight: 800; line-height: 1; white-space: nowrap; }

.mode-cards { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
.mode-card { position: relative; display: grid; grid-template-columns: 44px 1fr 20px; align-items: center; gap: .8rem; min-height: 94px; padding: 1rem; border: 1px solid #d9d6cd; border-radius: 15px; background: #fbfaf6; color: var(--tu-ink); font-family: inherit; text-align: start; transition: .18s; }
.mode-card:hover { border-color: #a7b8b0; transform: translateY(-1px); }
.mode-icon { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 12px; background: #ece9df; color: var(--tu-ink-soft); font-size: 1rem; transition: .18s; }
.mode-card strong { display: block; font-size: .77rem; }
.mode-card small { display: block; margin-top: .22rem; color: var(--tu-ink-soft); font-size: .62rem; line-height: 1.45; }
.mode-check { display: grid; place-items: center; width: 19px; height: 19px; border: 1px solid #c4c8c3; border-radius: 50%; color: transparent; font-size: .55rem; }
.mode-card.active { border-color: var(--tu-green); background: var(--tu-mint); box-shadow: inset 0 0 0 1px var(--tu-green); }
.mode-card.active .mode-icon { background: var(--tu-green); color: #fff; }
.mode-card.active .mode-check { border-color: var(--tu-green); background: var(--tu-green); color: #fff; }
.mode-card:focus-visible { outline: 3px solid rgba(27,117,85,.2); outline-offset: 2px; }

.dropzone { position: relative; overflow: hidden; display: grid; place-items: center; min-height: 245px; padding: 2rem; border: 1.5px dashed #9bad9f; border-radius: 18px; background: #f8f6ef; text-align: center; transition: .2s; }
.dropzone::before { content: ''; position: absolute; width: 240px; height: 240px; border: 1px solid rgba(27,117,85,.09); border-radius: 50%; box-shadow: 0 0 0 40px rgba(27,117,85,.025), 0 0 0 80px rgba(27,117,85,.018); }
.dropzone:not(.locked) { cursor: pointer; }
.dropzone:not(.locked):hover, .dropzone.drag, .dropzone:focus-visible { border-color: var(--tu-green); background: #eef6f1; outline: none; }
.dropzone-inner { position: relative; z-index: 1; }
.dz-icon { display: grid; place-items: center; width: 58px; height: 58px; margin: 0 auto .9rem; border-radius: 18px; background: var(--tu-green); color: #fff; font-size: 1.25rem; box-shadow: 0 10px 24px rgba(27,117,85,.22); }
.dz-title { color: var(--tu-ink); font-size: .9rem; font-weight: 800; }
.dz-sub { margin-top: .35rem; color: var(--tu-ink-soft); font-size: .68rem; }
.dz-browse { color: var(--tu-green); font-weight: 800; text-decoration: underline; text-underline-offset: 3px; }
.dz-rules { display: flex; justify-content: center; flex-wrap: wrap; gap: .35rem; margin-top: .85rem; }
.dz-rule { padding: .28rem .48rem; border: 1px solid #dbd8ce; border-radius: 99px; background: rgba(255,255,255,.7); color: #718078; font-size: .56rem; font-weight: 700; }
.dz-lock { display: none; align-items: center; justify-content: center; gap: .4rem; margin-top: .8rem; color: #9b493f; font-size: .64rem; font-weight: 800; }
.dropzone.locked { opacity: .62; cursor: not-allowed; }
.dropzone.locked .dz-icon { background: #7e8983; box-shadow: none; }
.dropzone.locked .dz-lock { display: flex; }

.queue-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1.25rem; padding-bottom: .65rem; border-bottom: 1px solid #e5e1d7; }
.queue-head strong { color: var(--tu-ink); font-size: .72rem; }
.queue-count { min-width: 25px; padding: .2rem .45rem; border-radius: 999px; background: #e9e5da; color: var(--tu-ink-soft); font-size: .58rem; font-weight: 800; text-align: center; }
.queue-empty { padding: 1.15rem 0 .1rem; color: #87928d; font-size: .65rem; text-align: center; }
.queue { display: flex; flex-direction: column; gap: .65rem; margin-top: .75rem; }
.qrow { padding: .8rem; border: 1px solid #dedbd2; border-radius: 13px; background: #fff; }
.qrow-main { display: flex; align-items: center; gap: .75rem; }
.qrow-ic { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 10px; background: var(--tu-mint); color: var(--tu-green); font-size: .75rem; flex-shrink: 0; }
.qrow-body { flex: 1; min-width: 0; }
.qrow-name { overflow: hidden; color: var(--tu-ink); font-size: .7rem; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.qrow-title { width: 100%; padding: .52rem .65rem; border: 1px solid #d4d5ce; border-radius: 9px; outline: 0; background: #faf9f5; color: var(--tu-ink); font-family: inherit; font-size: .7rem; font-weight: 700; line-height: 1.3; }
.qrow-title:focus { border-color: var(--tu-green); box-shadow: 0 0 0 3px rgba(27,117,85,.1); }
.qrow-bar { height: 4px; margin-top: .55rem; overflow: hidden; border-radius: 99px; background: #e4e4df; }
.qrow-fill { width: 0; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--tu-green), #55a883); transition: width .2s; }
.qrow-status { display: flex; align-items: center; gap: .35rem; color: var(--tu-ink-soft); font-size: .6rem; font-weight: 800; white-space: nowrap; }
.qrow-x { display: grid; place-items: center; width: 30px; height: 30px; border: 0; border-radius: 8px; background: transparent; color: #8b9690; font-size: .9rem; }
.qrow-x:hover { background: #fff0ed; color: #af493e; }
.qrow-save { min-height: 34px; padding: .45rem .7rem; border: 0; border-radius: 8px; background: var(--tu-green); color: #fff; font-family: inherit; font-size: .61rem; font-weight: 800; line-height: 1; }
.st-up { color: #356e9f; }.st-ok { color: var(--tu-green); }.st-err { color: #b34b40; }.st-wait { color: var(--tu-ink-soft); }
.save-all-wrap { display: flex; justify-content: flex-end; margin-top: 1rem; }
.save-all-btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 42px; padding: .65rem .9rem; border: 0; border-radius: 10px; background: var(--tu-green); color: #fff; font-family: inherit; font-size: .68rem; font-weight: 800; line-height: 1; }

.tu-foot { display: flex; align-items: center; justify-content: center; gap: .5rem; margin-top: 1.25rem; padding: 1rem; color: rgba(255,255,255,.5); font-size: .65rem; text-align: center; }
.tu-foot i { color: var(--tu-gold); }

@media (max-width: 900px) {
  .tu-hero { grid-template-columns: 1fr; min-height: 0; }
  .tu-hero-card { max-width: 520px; }
  .tu-workspace { grid-template-columns: 1fr; }
  .tu-rail { position: static; }
  .tu-rail-head, .tu-rail-note { display: none; }
  .tu-steps { display: grid; grid-template-columns: repeat(3, 1fr); margin: 0; }
  .tu-steps::before { top: 16px; right: 16%; bottom: auto; left: 16%; width: auto; height: 1px; }
  .tu-step { grid-template-columns: 33px 1fr; padding: 0 .35rem; }
  .tu-step small { display: none; }
}

@media (max-width: 620px) {
  .up-shell { padding-inline: .75rem; }
  .up-user-name { display: none; }
  .tu-hero { padding: 1.5rem; border-radius: 22px; }
  .tu-hero h1 { font-size: 2rem; }
  .tu-rail { padding: .9rem; }
  .tu-steps { gap: .25rem; }
  .tu-step { grid-template-columns: 1fr; justify-items: center; gap: .35rem; padding: 0; text-align: center; }
  .tu-step strong { font-size: .58rem; }
  .tu-stage-head, .tu-stage-body { padding-inline: 1rem; }
  .qari-results, .mode-cards { grid-template-columns: 1fr; }
  .qari-card { align-items: flex-start; flex-wrap: wrap; }
  .tu-change { margin-inline-start: auto; }
  .qrow-main { align-items: flex-start; flex-wrap: wrap; }
  .qrow-body { min-width: calc(100% - 80px); }
  .qrow-status { margin-inline-start: 42px; }
}
</style>
@endpush

@section('content')
<div class="tilawa-create">
  <header class="tu-hero">
    <div class="tu-hero-copy">
      <div class="tu-eyebrow">{{ __('Tilawa upload studio') }}</div>
      <h1>{{ __('From recording to review, without the clutter.') }}</h1>
      <p>{{ __('Choose one reciter, decide how titles are created, then add as many audio files as you need.') }}</p>
    </div>
    <div class="tu-hero-card">
      <div class="tu-hero-card-head"><i class="fas fa-shield-halved"></i>{{ __('Ready for a clean handoff') }}</div>
      <p>{{ __('Every file is saved as a draft and sent to the review queue before it appears publicly.') }}</p>
      <div class="tu-specs">
        <span class="tu-spec">MP3</span>
        <span class="tu-spec">OGG</span>
        <span class="tu-spec">WAV</span>
        <span class="tu-spec">{{ __('Up to 200 MB') }}</span>
      </div>
    </div>
  </header>

  <div class="tu-workspace">
    <aside class="tu-rail" aria-label="{{ __('Upload progress') }}">
      <div class="tu-rail-head">
        <h2 class="tu-rail-title">{{ __('Your upload path') }}</h2>
        <p class="tu-rail-sub">{{ __('Three clear decisions. The form keeps the rest out of your way.') }}</p>
      </div>
      <ol class="tu-steps">
        <li class="tu-step is-active" id="railQari">
          <span class="tu-step-dot"><span>1</span></span>
          <div><strong>{{ __('Reciter') }}</strong><small>{{ __('Who is reciting?') }}</small></div>
        </li>
        <li class="tu-step" id="railNaming">
          <span class="tu-step-dot"><span>2</span></span>
          <div><strong>{{ __('Naming') }}</strong><small>{{ __('How should titles work?') }}</small></div>
        </li>
        <li class="tu-step" id="railFiles">
          <span class="tu-step-dot"><span>3</span></span>
          <div><strong>{{ __('Audio files') }}</strong><small>{{ __('Add and track uploads') }}</small></div>
        </li>
      </ol>
      <div class="tu-rail-note"><i class="fas fa-circle-info"></i><span>{{ __('Your reciter and naming preference are remembered for the next upload session.') }}</span></div>
    </aside>

    <main class="tu-stages">
      <section class="tu-stage" aria-labelledby="qari-stage-title">
        <header class="tu-stage-head">
          <span class="tu-stage-no">01</span>
          <div>
            <h2 id="qari-stage-title">{{ __('Choose the reciter') }}</h2>
            <p>{{ __('This reciter will be attached to every file in the batch.') }}</p>
          </div>
        </header>
        <div class="tu-stage-body">
          <div id="qariSearchWrap">
            <label class="tu-field-label" for="qariSearch">{{ __('Find a reciter') }}</label>
            <div class="qari-search">
              <i class="fas fa-magnifying-glass"></i>
              <input type="search" id="qariSearch" placeholder="{{ __('Search qari by name…') }}" autocomplete="off">
            </div>
            <div class="qari-results" id="qariResults"></div>
          </div>

          <div id="qariCardWrap" hidden>
            <div class="qari-card">
              <img id="qariCardImg" src="" alt="" width="56" height="56">
              <div class="qari-card-info">
                <div class="qari-card-name" id="qariCardName"></div>
                <div class="qari-card-sub"><i class="fas fa-circle-check"></i> {{ __('Selected for this entire upload batch') }}</div>
              </div>
              <button type="button" class="tu-change" id="qariChangeBtn"><i class="fas fa-arrow-rotate-right"></i>{{ __('Change') }}</button>
            </div>
          </div>
        </div>
      </section>

      <section class="tu-stage" aria-labelledby="naming-stage-title">
        <header class="tu-stage-head">
          <span class="tu-stage-no">02</span>
          <div>
            <h2 id="naming-stage-title">{{ __('Choose how titles are created') }}</h2>
            <p>{{ __('Use clean file names for speed, or review every title before saving.') }}</p>
          </div>
        </header>
        <div class="tu-stage-body">
          <div class="mode-cards" role="group" aria-label="{{ __('Title mode') }}">
            <button type="button" class="mode-card" data-mode="filename" aria-pressed="false">
              <span class="mode-icon"><i class="fas fa-bolt"></i></span>
              <span><strong>{{ __('Use file name') }}</strong><small>{{ __('Each upload saves automatically with its file name.') }}</small></span>
              <span class="mode-check"><i class="fas fa-check"></i></span>
            </button>
            <button type="button" class="mode-card" data-mode="manual" aria-pressed="false">
              <span class="mode-icon"><i class="fas fa-pen-nib"></i></span>
              <span><strong>{{ __('Review each title') }}</strong><small>{{ __('Edit every title, then save when it reads correctly.') }}</small></span>
              <span class="mode-check"><i class="fas fa-check"></i></span>
            </button>
          </div>
        </div>
      </section>

      <section class="tu-stage" aria-labelledby="files-stage-title">
        <header class="tu-stage-head">
          <span class="tu-stage-no">03</span>
          <div>
            <h2 id="files-stage-title">{{ __('Add audio files') }}</h2>
            <p>{{ __('Drop a complete batch here and follow each file from upload to saved draft.') }}</p>
          </div>
        </header>
        <div class="tu-stage-body">
          <div class="dropzone" id="dropzone" role="button" tabindex="0" aria-disabled="true" aria-describedby="dropzone-help">
            <input type="file" id="fileInput" multiple accept="audio/mpeg,audio/mp3,audio/ogg,audio/wav,.mp3,.ogg,.wav" hidden>
            <div class="dropzone-inner">
              <span class="dz-icon"><i class="fas fa-wave-square"></i></span>
              <div class="dz-title">{{ __('Drop audio here') }}</div>
              <div class="dz-sub" id="dropzone-help">{{ __('or') }} <span class="dz-browse">{{ __('choose files from your device') }}</span></div>
              <div class="dz-rules">
                <span class="dz-rule">MP3 · OGG · WAV</span>
                <span class="dz-rule">{{ __('Maximum 200 MB each') }}</span>
                <span class="dz-rule">{{ __('Multiple files supported') }}</span>
              </div>
              <div class="dz-lock" id="dzLock"><i class="fas fa-lock"></i>{{ __('Choose a reciter to unlock uploads') }}</div>
            </div>
          </div>

          <div class="queue-head">
            <strong>{{ __('Upload queue') }}</strong>
            <span class="queue-count" id="queueCount">0</span>
          </div>
          <div class="queue-empty" id="queueEmpty">{{ __('Your selected files will appear here with live progress.') }}</div>
          <div class="queue" id="queue" aria-live="polite"></div>

          <div id="saveAllWrap" class="save-all-wrap" hidden>
            <button type="button" class="save-all-btn" id="saveAllBtn"><i class="fas fa-check-double"></i>{{ __('Save all ready files') }}</button>
          </div>
        </div>
      </section>
    </main>
  </div>

  <footer class="tu-foot"><i class="fas fa-shield-halved"></i>{{ __('Every upload is submitted for review before appearing on the site.') }}</footer>
</div>

@push('scripts')
<script>
(function(){
  const CFG = {
    tmpUrl:    @json(route('admin.upload.tmp')) + '?type=audio',
    tmpBase:   @json(url('/admin/upload/tmp')),
    quickStore:@json(route('admin.tilawat.quick-store')),
    setQari:   @json(route('admin.uploader.qari')),
    csrf:      document.querySelector('meta[name="csrf-token"]').content,
    maxSize:   200 * 1024 * 1024,
    exts:      ['mp3','ogg','wav','mpeg'],
    t: {
      noQari:      @json(__('No qari found.')),
      badType:     @json(__('Unsupported file type')),
      tooBig:      @json(__('File exceeds 200MB')),
      title:       @json(__('Title')),
      remove:      @json(__('Remove')),
      uploading:   @json(__('Uploading…')),
      save:        @json(__('Save')),
      saving:      @json(__('Saving…')),
      saved:       @json(__('Saved')),
      networkErr:  @json(__('Network error')),
      saveFailed:  @json(__('Save failed')),
    },
  };

  let currentQari = @json($defaultQari);
  let titleMode   = @json($titleMode);

  const QARIS = @json($qaris);
  const $ = id => document.getElementById(id);
  const searchWrap = $('qariSearchWrap'), cardWrap = $('qariCardWrap'),
        searchInput = $('qariSearch'), results = $('qariResults'),
        cardImg = $('qariCardImg'), cardName = $('qariCardName'),
        dropzone = $('dropzone'), fileInput = $('fileInput'),
        queue = $('queue'), saveAllWrap = $('saveAllWrap'),
        queueEmpty = $('queueEmpty'), queueCount = $('queueCount');

  const rows = new Map();
  const stripExt = n => n.replace(/\.[^/.]+$/, '');

  function renderResults(filter){
    const f = (filter || '').trim().toLowerCase();
    const list = f ? QARIS.filter(q => q.name.toLowerCase().includes(f)) : QARIS;
    if(!list.length){
      results.innerHTML = `<div class="qari-empty">${escapeHtml(CFG.t.noQari)}</div>`;
      return;
    }
    results.innerHTML = list.map(q =>
      `<button type="button" class="qari-opt" data-id="${q.id}" title="${escapeAttr(q.name)}">
         <img src="${escapeAttr(q.image)}" alt=""><span>${escapeHtml(q.name)}</span>
       </button>`).join('');
  }

  results.addEventListener('click', e => {
    const btn = e.target.closest('.qari-opt');
    if(btn) selectQari(QARIS.find(q => q.id == btn.dataset.id));
  });
  searchInput.addEventListener('input', () => renderResults(searchInput.value));

  function showCard(q){
    cardImg.src = q.image;
    cardImg.alt = q.name;
    cardName.textContent = q.name;
    cardName.title = q.name;
    searchWrap.hidden = true;
    cardWrap.hidden = false;
    updateInterface();
  }

  function showSearch(){
    cardWrap.hidden = true;
    searchWrap.hidden = false;
    searchInput.value = '';
    renderResults('');
    searchInput.focus();
  }

  function selectQari(q){
    if(!q) return;
    currentQari = q;
    showCard(q);
    fetch(CFG.setQari, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CFG.csrf,'Accept':'application/json','Content-Type':'application/json'},
      body: JSON.stringify({ qari_id: q.id })
    }).catch(()=>{});
  }

  $('qariChangeBtn').addEventListener('click', showSearch);

  function applyMode(mode){
    titleMode = mode;
    document.querySelectorAll('.mode-card').forEach(card => {
      const active = card.dataset.mode === mode;
      card.classList.toggle('active', active);
      card.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    updateInterface();
  }

  document.querySelectorAll('.mode-card').forEach(card => card.addEventListener('click', () => {
    applyMode(card.dataset.mode);
    fetch(CFG.setQari, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CFG.csrf,'Accept':'application/json','Content-Type':'application/json'},
      body: JSON.stringify({ title_mode: titleMode })
    }).catch(()=>{});
    refreshSaveAll();
  }));

  function updateInterface(){
    const hasQari = Boolean(currentQari);
    const hasRows = rows.size > 0;
    dropzone.classList.toggle('locked', !hasQari);
    dropzone.setAttribute('aria-disabled', hasQari ? 'false' : 'true');
    $('railQari').classList.toggle('is-complete', hasQari);
    $('railQari').classList.toggle('is-active', !hasQari);
    $('railNaming').classList.toggle('is-complete', hasQari);
    $('railNaming').classList.toggle('is-active', hasQari && !hasRows);
    $('railFiles').classList.toggle('is-active', hasQari && hasRows);
    $('railFiles').classList.toggle('is-complete', [...rows.values()].some(row => row.status === 'saved'));
    queueEmpty.hidden = hasRows;
    queueCount.textContent = rows.size;
  }

  dropzone.addEventListener('click', () => { if(currentQari) fileInput.click(); });
  dropzone.addEventListener('keydown', e => {
    if(currentQari && (e.key === 'Enter' || e.key === ' ')){
      e.preventDefault();
      fileInput.click();
    }
  });
  ['dragover','dragenter'].forEach(eventName => dropzone.addEventListener(eventName, e => {
    e.preventDefault();
    if(currentQari) dropzone.classList.add('drag');
  }));
  ['dragleave','drop'].forEach(eventName => dropzone.addEventListener(eventName, e => {
    e.preventDefault();
    dropzone.classList.remove('drag');
  }));
  dropzone.addEventListener('drop', e => { if(currentQari) addFiles(e.dataTransfer.files); });
  fileInput.addEventListener('change', () => { addFiles(fileInput.files); fileInput.value=''; });

  function validFile(file){
    const ext = (file.name.split('.').pop() || '').toLowerCase();
    if(!CFG.exts.includes(ext)) return CFG.t.badType;
    if(file.size > CFG.maxSize) return CFG.t.tooBig;
    return null;
  }

  function addFiles(fileList){
    if(!currentQari) return;
    [...fileList].forEach(file => {
      const uid = 'r' + Math.random().toString(36).slice(2);
      const error = validFile(file);
      const row = { uid, file, title: stripExt(file.name), token:null, status: error ? 'error' : 'queued' };
      rows.set(uid, row);
      renderRow(row, error);
      if(!error) processRow(row);
    });
    updateInterface();
  }

  function renderRow(row, error){
    const element = document.createElement('div');
    element.className = 'qrow';
    element.dataset.uid = row.uid;
    const titleHtml = titleMode === 'manual'
      ? `<input class="qrow-title" value="${escapeAttr(row.title)}" placeholder="${escapeAttr(CFG.t.title)}">`
      : `<div class="qrow-name">${escapeHtml(row.title)}</div>`;
    element.innerHTML =
      `<div class="qrow-main">
         <i class="fas fa-music qrow-ic"></i>
         <div class="qrow-body">${titleHtml}<div class="qrow-bar"><div class="qrow-fill"></div></div></div>
         <div class="qrow-status"></div>
         <button type="button" class="qrow-x" title="${escapeAttr(CFG.t.remove)}" aria-label="${escapeAttr(CFG.t.remove)}">&times;</button>
       </div>`;
    queue.appendChild(element);
    element.querySelector('.qrow-x').addEventListener('click', () => removeRow(row.uid));
    const titleInput = element.querySelector('.qrow-title');
    if(titleInput) titleInput.addEventListener('input', e => { row.title = e.target.value; });
    if(error) setStatus(row.uid, 'err', `<i class="fas fa-circle-xmark"></i> ${escapeHtml(error)}`);
  }

  function setStatus(uid, state, html){
    const element = queue.querySelector(`[data-uid="${uid}"] .qrow-status`);
    if(element){
      element.className = 'qrow-status st-' + state;
      element.innerHTML = html;
    }
  }

  function setProgress(uid, percent){
    const fill = queue.querySelector(`[data-uid="${uid}"] .qrow-fill`);
    if(fill) fill.style.width = percent + '%';
  }

  async function processRow(row){
    setStatus(row.uid, 'up', `<i class="fas fa-arrow-up-from-bracket"></i> ${escapeHtml(CFG.t.uploading)}`);
    try {
      row.token = await uploadToTmp(row.file, progress => setProgress(row.uid, progress));
    } catch(error){
      row.status = 'error';
      setStatus(row.uid, 'err', `<i class="fas fa-circle-xmark"></i> ${escapeHtml(error.message)}`);
      updateInterface();
      return;
    }
    setProgress(row.uid, 100);
    if(titleMode === 'manual'){
      row.status = 'ready';
      const statusElement = queue.querySelector(`[data-uid="${row.uid}"] .qrow-status`);
      statusElement.className = 'qrow-status';
      statusElement.innerHTML = `<button type="button" class="qrow-save">${escapeHtml(CFG.t.save)}</button>`;
      statusElement.querySelector('.qrow-save').addEventListener('click', () => saveRow(row));
      refreshSaveAll();
    } else {
      saveRow(row);
    }
    updateInterface();
  }

  async function saveRow(row){
    if(!row.token) return;
    const title = (row.title || '').trim() || stripExt(row.file.name);
    setStatus(row.uid, 'up', `<i class="fas fa-circle-notch fa-spin"></i> ${escapeHtml(CFG.t.saving)}`);
    try {
      await createTilawa(row.token, title);
      row.status = 'saved';
      setStatus(row.uid, 'ok', `<i class="fas fa-circle-check"></i> ${escapeHtml(CFG.t.saved)}`);
      const removeButton = queue.querySelector(`[data-uid="${row.uid}"] .qrow-x`);
      if(removeButton) removeButton.remove();
      const titleInput = queue.querySelector(`[data-uid="${row.uid}"] .qrow-title`);
      if(titleInput) titleInput.disabled = true;
    } catch(error){
      row.status = 'error';
      setStatus(row.uid, 'err', `<i class="fas fa-circle-xmark"></i> ${escapeHtml(error.message)}`);
    }
    refreshSaveAll();
    updateInterface();
  }

  function removeRow(uid){
    const row = rows.get(uid);
    if(row && row.token && row.status !== 'saved'){
      fetch(`${CFG.tmpBase}/${row.token}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':CFG.csrf}, keepalive:true }).catch(()=>{});
    }
    rows.delete(uid);
    const element = queue.querySelector(`[data-uid="${uid}"]`);
    if(element) element.remove();
    refreshSaveAll();
    updateInterface();
  }

  function refreshSaveAll(){
    const ready = [...rows.values()].filter(row => row.status === 'ready');
    saveAllWrap.hidden = !(titleMode === 'manual' && ready.length > 1);
  }

  $('saveAllBtn').addEventListener('click', () => {
    [...rows.values()].filter(row => row.status === 'ready').forEach(saveRow);
  });

  function uploadToTmp(file, onProgress){
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', CFG.tmpUrl);
      xhr.setRequestHeader('X-CSRF-TOKEN', CFG.csrf);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.upload.onprogress = e => { if(e.lengthComputable) onProgress(Math.round(e.loaded / e.total * 100)); };
      xhr.onload = () => (xhr.status >= 200 && xhr.status < 300)
        ? resolve(xhr.responseText.trim())
        : reject(new Error(parseError(xhr.responseText) || ('HTTP ' + xhr.status)));
      xhr.onerror = () => reject(new Error(CFG.t.networkErr));
      const formData = new FormData();
      formData.append('file', file);
      xhr.send(formData);
    });
  }

  async function createTilawa(token, title){
    const response = await fetch(CFG.quickStore, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CFG.csrf,'Accept':'application/json','Content-Type':'application/json'},
      body: JSON.stringify({ qari_id: currentQari.id, audio_tmp: token, title })
    });
    if(!response.ok){
      let message = CFG.t.saveFailed;
      try {
        const json = await response.json();
        message = json.message || message;
      } catch(error) {}
      throw new Error(message);
    }
    return response.json();
  }

  function parseError(text){ try { return JSON.parse(text).message; } catch(error){ return text; } }
  function escapeHtml(value){ const div = document.createElement('div'); div.textContent = value; return div.innerHTML; }
  function escapeAttr(value){ return escapeHtml(value).replace(/"/g, '&quot;'); }

  renderResults('');
  applyMode(titleMode);
  if(currentQari) showCard(currentQari);
  updateInterface();
})();
</script>
@endpush
@endsection
