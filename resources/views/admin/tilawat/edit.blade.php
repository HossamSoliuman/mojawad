@extends('layouts.admin')
@section('title', __('Edit Tilawa'))
@section('page-title', __('Edit Tilawa'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › <a href="{{ route('admin.tilawat.index') }}">{{ __('Tilawat') }}</a> › {{ __('Edit') }} @endsection

@php
    $isAdmin = auth()->user()->hasRole('admin');
    $needsResubmission = ! $isAdmin && in_array($tilawa->review_status, ['rejected', 'pending']);
    $reviewLabel = match ($tilawa->review_status) {
        'approved' => __('Approved'),
        'rejected' => __('Needs changes'),
        default => __('Awaiting review'),
    };
    $selectedQariId = (string) old('qari_id', $tilawa->qari_id);
    $selectedQari = $qaris->first(fn ($qari) => (string) $qari->id === $selectedQariId) ?? $tilawa->qari;
    $selectedSurahNumber = (string) old('surah_number', $tilawa->surah_number);
    $selectedSurahName = $selectedSurahNumber !== '' ? config('surahs')[(int) $selectedSurahNumber] ?? null : null;
@endphp

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.tilawa-editor {
  --te-ink: #14251f;
  --te-ink-soft: #496159;
  --te-forest: #176b4d;
  --te-forest-dark: #0e4d38;
  --te-mint: #dff2e9;
  --te-paper: #fffefa;
  --te-canvas: #f4f1e9;
  --te-line: #dedbd1;
  --te-sand: #eee6d5;
  --te-red: #b8463b;
  color: var(--te-ink);
  font-family: 'Manrope', 'Noto Sans Arabic', sans-serif;
  max-width: 1240px;
  margin: 0 auto;
}

[dir="rtl"] .tilawa-editor { font-family: 'Noto Sans Arabic', 'Manrope', sans-serif; }

.te-masthead {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 2rem;
  min-height: 230px;
  margin-bottom: 1.25rem;
  padding: clamp(1.5rem, 4vw, 3rem);
  border-radius: 28px;
  background:
    radial-gradient(circle at 82% 20%, rgba(223, 242, 233, .2), transparent 27%),
    linear-gradient(125deg, #102b22 0%, #154a38 58%, #1c7052 100%);
  box-shadow: 0 22px 50px rgba(20, 45, 36, .15);
}

.te-masthead::after {
  content: '';
  position: absolute;
  inset: 0;
  opacity: .16;
  pointer-events: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='88' height='88' viewBox='0 0 88 88'%3E%3Cpath d='M44 8 80 44 44 80 8 44Z' fill='none' stroke='%23fff' stroke-width='.75'/%3E%3Ccircle cx='44' cy='44' r='9' fill='none' stroke='%23fff' stroke-width='.75'/%3E%3C/svg%3E");
}

.te-masthead-copy, .te-masthead-meta { position: relative; z-index: 1; }
.te-masthead-copy { max-width: 700px; }
.te-eyebrow { display: flex; align-items: center; gap: .55rem; margin-bottom: .9rem; color: #b9dfd0; font-size: .72rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
.te-eyebrow::before { content: ''; width: 28px; height: 1px; background: currentColor; }
.te-masthead h2 { max-width: 650px; margin: 0; color: #fff; font-family: inherit; font-size: clamp(1.65rem, 4vw, 3rem); font-weight: 700; line-height: 1.2; letter-spacing: -.035em; }
[dir="rtl"] .te-masthead h2 { letter-spacing: 0; line-height: 1.45; }
.te-masthead p { max-width: 620px; margin: .85rem 0 0; color: rgba(255,255,255,.72); font-size: .92rem; line-height: 1.8; }
.te-masthead-meta { display: flex; flex-direction: column; align-items: flex-end; gap: .7rem; flex-shrink: 0; }
.te-status-chip { display: inline-flex; align-items: center; gap: .5rem; padding: .52rem .8rem; border: 1px solid rgba(255,255,255,.18); border-radius: 999px; background: rgba(255,255,255,.1); color: #fff; font-size: .74rem; font-weight: 700; backdrop-filter: blur(12px); }
.te-record-id { color: rgba(255,255,255,.52); font-size: .72rem; font-weight: 600; letter-spacing: .08em; }

.te-alert { display: flex; align-items: flex-start; gap: .9rem; margin-bottom: 1.25rem; padding: 1rem 1.1rem; border: 1px solid #e7c7c1; border-radius: 16px; background: #fff6f3; color: #72342d; }
.te-alert.is-pending { border-color: #e4d7b8; background: #fffaf0; color: #6d5725; }
.te-alert-icon { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 10px; background: rgba(184,70,59,.1); flex-shrink: 0; }
.te-alert.is-pending .te-alert-icon { background: rgba(175,132,38,.12); }
.te-alert strong { display: block; font-size: .82rem; }
.te-alert p { margin: .2rem 0 0; color: inherit; font-size: .78rem; line-height: 1.65; opacity: .8; }
.te-error-list { margin: .35rem 0 0; padding-inline-start: 1.15rem; font-size: .78rem; }

.te-layout { display: grid; grid-template-columns: minmax(0, 1fr) 310px; align-items: start; gap: 1.25rem; }
.te-content { display: flex; flex-direction: column; gap: 1.25rem; min-width: 0; }
.te-section { position: relative; z-index: 0; overflow: visible; isolation: isolate; border: 1px solid var(--te-line); border-radius: 22px; background: var(--te-paper); box-shadow: 0 10px 30px rgba(25, 48, 40, .045); }
.te-section.has-open-selector { z-index: 80; }
.te-section-head { display: flex; align-items: flex-start; gap: .9rem; padding: 1.35rem 1.5rem 1.1rem; border-bottom: 1px solid #ebe8e0; }
.te-section-no { display: grid; place-items: center; width: 32px; height: 32px; border-radius: 50%; background: var(--te-ink); color: #fff; font-size: .72rem; font-weight: 800; flex-shrink: 0; }
.te-section-title { margin: 0; color: var(--te-ink); font-family: inherit; font-size: 1.02rem; font-weight: 800; line-height: 1.35; }
.te-section-copy { margin: .25rem 0 0; color: var(--te-ink-soft); font-size: .76rem; line-height: 1.65; }
.te-section-body { padding: 1.5rem; }
.te-field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.15rem; }
.te-field-grid + .te-field, .te-field + .te-field-grid, .te-field + .te-field { margin-top: 1.15rem; }
.te-field { min-width: 0; }
.te-field.is-wide { grid-column: 1 / -1; }
.te-label { display: flex; align-items: center; justify-content: space-between; gap: .65rem; margin-bottom: .5rem; color: var(--te-ink); font-size: .75rem; font-weight: 800; }
.te-label-note { color: #7c8d86; font-size: .65rem; font-weight: 600; }
.te-required { color: var(--te-red); }
.te-input { width: 100%; min-height: 48px; padding: .72rem .9rem; border: 1px solid #d6d7d0; border-radius: 12px; outline: none; background: #fff; color: var(--te-ink); font-family: inherit; font-size: .84rem; font-weight: 600; line-height: 1.55; transition: border-color .18s, box-shadow .18s, background .18s; }
.te-input:hover { border-color: #adb9b2; }
.te-input:focus { border-color: var(--te-forest); box-shadow: 0 0 0 4px rgba(23,107,77,.11); background: #fff; }
.te-input::placeholder { color: #a3aaa6; font-weight: 500; }
textarea.te-input { min-height: 150px; resize: vertical; }
select.te-input { cursor: pointer; }
.te-help { display: flex; align-items: flex-start; gap: .4rem; margin-top: .45rem; color: #71817b; font-size: .68rem; line-height: 1.6; }
.te-help i { margin-top: .2rem; color: var(--te-forest); }
.te-error { display: flex; align-items: center; gap: .35rem; margin-top: .45rem; color: var(--te-red); font-size: .7rem; font-weight: 700; }

.te-combobox { position: relative; z-index: 0; }
.te-combobox.is-open { z-index: 2; }
.te-combobox-trigger { width: 100%; min-height: 54px; display: grid; grid-template-columns: 36px minmax(0, 1fr) auto; align-items: center; gap: .75rem; padding: .55rem .7rem; border: 1px solid #d6d7d0; border-radius: 13px; background: #fff; color: var(--te-ink); font-family: inherit; text-align: start; transition: border-color .18s, box-shadow .18s, background .18s; }
.te-combobox-trigger:hover { border-color: #a9b5ae; background: #fdfdfa; }
.te-combobox-trigger:focus-visible, .te-combobox.is-open .te-combobox-trigger { border-color: var(--te-forest); outline: none; box-shadow: 0 0 0 4px rgba(23,107,77,.11); }
.te-combobox-selected-media { display: grid; place-items: center; width: 36px; height: 36px; overflow: hidden; border-radius: 10px; background: var(--te-mint); color: var(--te-forest); font-size: .75rem; font-weight: 800; }
.te-combobox-selected-media img { width: 100%; height: 100%; object-fit: cover; }
.te-combobox-selected-copy { min-width: 0; }
.te-combobox-selected-copy strong, .te-combobox-selected-copy small { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.te-combobox-selected-copy strong { color: var(--te-ink); font-size: .76rem; font-weight: 800; }
.te-combobox-selected-copy small { margin-top: .15rem; color: #75847e; font-size: .62rem; font-weight: 600; }
.te-combobox-chevron { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 8px; background: var(--te-canvas); color: #65766f; font-size: .62rem; transition: rotate .18s, background .18s; }
.te-combobox.is-open .te-combobox-chevron { rotate: 180deg; background: var(--te-mint); color: var(--te-forest); }
.te-combobox-panel { position: absolute; z-index: 60; top: calc(100% + .55rem); inset-inline-start: 0; width: min(660px, calc(100vw - 3rem)); overflow: hidden; border: 1px solid #ccd2cc; border-radius: 17px; background: #fff; box-shadow: 0 24px 60px rgba(17,39,31,.2), 0 2px 8px rgba(17,39,31,.08); transform-origin: top; animation: te-selector-in .16s ease-out; }
.te-combobox-panel[hidden] { display: none; }
@keyframes te-selector-in { from { opacity: 0; transform: translateY(-5px) scale(.99); } to { opacity: 1; transform: translateY(0) scale(1); } }
.te-combobox-search { display: flex; align-items: center; gap: .65rem; margin: .8rem; padding: .65rem .75rem; border: 1px solid #d9ddd8; border-radius: 11px; background: #f8f8f4; }
.te-combobox-search:focus-within { border-color: var(--te-forest); background: #fff; box-shadow: 0 0 0 3px rgba(23,107,77,.09); }
.te-combobox-search > i { color: var(--te-forest); font-size: .72rem; }
.te-combobox-search input { width: 100%; min-width: 0; border: 0; outline: 0; background: transparent; color: var(--te-ink); font-family: inherit; font-size: .72rem; font-weight: 700; }
.te-combobox-search input::placeholder { color: #909b96; }
.te-combobox-esc { padding: .2rem .35rem; border: 1px solid #d7dad6; border-radius: 5px; background: #fff; color: #89928e; font-size: .5rem; font-weight: 800; }
.te-combobox-options { --selector-columns: 2; display: grid; grid-template-columns: repeat(var(--selector-columns), minmax(0, 1fr)); gap: .45rem; max-height: 310px; padding: 0 .8rem .8rem; overflow-y: auto; overscroll-behavior: contain; scrollbar-width: thin; }
.te-combobox-option { position: relative; display: grid; grid-template-columns: 38px minmax(0, 1fr) 18px; align-items: center; gap: .65rem; min-width: 0; min-height: 58px; padding: .55rem .65rem; border: 1px solid #e2e3dd; border-radius: 12px; background: #fdfcf8; color: var(--te-ink); font-family: inherit; text-align: start; transition: border-color .14s, background .14s, transform .14s; }
.te-combobox-option:hover, .te-combobox-option:focus-visible { border-color: #9fb8ad; outline: none; background: #f1f7f3; transform: translateY(-1px); }
.te-combobox-option[aria-selected="true"] { border-color: var(--te-forest); background: var(--te-mint); box-shadow: inset 0 0 0 1px var(--te-forest); }
.te-combobox-option[hidden] { display: none; }
.te-combobox-option-media { display: grid; place-items: center; width: 38px; height: 38px; overflow: hidden; border-radius: 10px; background: #e8eee9; color: var(--te-forest); font-size: .66rem; font-weight: 800; }
.te-combobox-option-media img { width: 100%; height: 100%; object-fit: cover; }
.te-combobox-option-copy { min-width: 0; }
.te-combobox-option-copy strong, .te-combobox-option-copy small { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.te-combobox-option-copy strong { color: var(--te-ink); font-size: .68rem; font-weight: 800; }
.te-combobox-option-copy small { margin-top: .12rem; color: #798780; font-size: .56rem; font-weight: 600; }
.te-combobox-option-check { color: transparent; font-size: .66rem; }
.te-combobox-option[aria-selected="true"] .te-combobox-option-check { color: var(--te-forest); }
.te-combobox-empty { margin: 0 .8rem .8rem; padding: 1.25rem; border: 1px dashed #d7dbd6; border-radius: 12px; background: #faf9f5; color: #73817b; font-size: .68rem; font-weight: 700; text-align: center; }
.te-combobox-empty[hidden] { display: none; }
.te-combobox-footer { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .65rem .85rem; border-top: 1px solid #e8e7e1; background: #faf9f5; color: #7b8782; font-size: .56rem; font-weight: 700; }
.te-combobox-footer span:last-child { display: flex; align-items: center; gap: .35rem; }
.te-combobox-footer i { color: var(--te-forest); }
.te-range-grid { display: grid; grid-template-columns: minmax(0, 1.5fr) repeat(2, minmax(100px, .5fr)); gap: 1rem; }

.te-status-options { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .65rem; }
.te-status-option { position: relative; cursor: pointer; }
.te-status-option input { position: absolute; opacity: 0; pointer-events: none; }
.te-status-option span { display: flex; align-items: center; gap: .6rem; min-height: 48px; padding: .7rem .85rem; border: 1px solid #d9d8d0; border-radius: 12px; background: #fff; color: #687770; font-size: .73rem; font-weight: 700; transition: .18s ease; }
.te-status-option input:checked + span { border-color: var(--te-forest); background: var(--te-mint); color: var(--te-forest-dark); box-shadow: inset 0 0 0 1px var(--te-forest); }
.te-status-option input:focus-visible + span { outline: 3px solid rgba(23,107,77,.22); outline-offset: 2px; }

.te-media-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
.te-media-card { min-width: 0; padding: 1rem; border: 1px solid #dfddd5; border-radius: 16px; background: #faf9f5; }
.te-media-kicker { display: flex; align-items: center; gap: .5rem; margin-bottom: .75rem; color: var(--te-ink); font-size: .76rem; font-weight: 800; }
.te-media-current { display: flex; align-items: center; gap: .7rem; min-height: 52px; margin-bottom: .85rem; padding: .6rem; border-radius: 11px; background: #fff; color: var(--te-ink-soft); font-size: .7rem; }
.te-media-current i { color: var(--te-forest); font-size: 1rem; }
.te-cover-thumb { width: 42px; height: 42px; border-radius: 9px; object-fit: cover; }

.te-aside { position: sticky; top: 88px; }
.te-summary { overflow: hidden; border: 1px solid var(--te-line); border-radius: 22px; background: var(--te-paper); box-shadow: 0 16px 35px rgba(21, 45, 36, .08); }
.te-summary-art { position: relative; aspect-ratio: 16 / 11; overflow: hidden; background: linear-gradient(145deg, #133b2e, #1f7657); }
.te-summary-art img { width: 100%; height: 100%; object-fit: cover; }
.te-summary-art.is-placeholder { display: grid; place-items: center; }
.te-summary-art.is-placeholder::before, .te-summary-art.is-placeholder::after { content: ''; position: absolute; width: 145px; height: 145px; border: 1px solid rgba(255,255,255,.15); transform: rotate(45deg); }
.te-summary-art.is-placeholder::after { width: 82px; height: 82px; }
.te-summary-art.is-placeholder i { position: relative; z-index: 1; color: rgba(255,255,255,.85); font-size: 1.5rem; }
.te-summary-body { padding: 1.2rem; }
.te-summary-kicker { color: var(--te-forest); font-size: .62rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
.te-summary-title { margin: .45rem 0 0; color: var(--te-ink); font-size: 1rem; font-weight: 800; line-height: 1.5; }
.te-summary-qari { margin-top: .25rem; color: var(--te-ink-soft); font-size: .72rem; }
.te-summary-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; margin: 1rem 0; }
.te-fact { padding: .7rem; border-radius: 11px; background: var(--te-canvas); }
.te-fact span { display: block; color: #798881; font-size: .59rem; font-weight: 700; text-transform: uppercase; }
.te-fact strong { display: block; margin-top: .2rem; color: var(--te-ink); font-size: .7rem; line-height: 1.4; }
.te-featured { display: flex; align-items: center; justify-content: space-between; gap: .8rem; padding: .9rem 0; border-block: 1px solid #ebe8e0; cursor: pointer; }
.te-featured-copy strong { display: block; color: var(--te-ink); font-size: .75rem; }
.te-featured-copy small { display: block; margin-top: .18rem; color: #7a8882; font-size: .62rem; line-height: 1.4; }
.te-switch { position: relative; width: 42px; height: 24px; flex-shrink: 0; }
.te-switch input { position: absolute; opacity: 0; }
.te-switch-track { position: absolute; inset: 0; border-radius: 99px; background: #c9cec9; transition: .2s; }
.te-switch-track::after { content: ''; position: absolute; top: 4px; inset-inline-start: 4px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.22); transition: .2s; }
.te-switch input:checked + .te-switch-track { background: var(--te-forest); }
.te-switch input:checked + .te-switch-track::after { translate: 18px 0; }
[dir="rtl"] .te-switch input:checked + .te-switch-track::after { translate: -18px 0; }
.te-switch input:focus-visible + .te-switch-track { outline: 3px solid rgba(23,107,77,.22); outline-offset: 2px; }
.te-actions { display: grid; gap: .6rem; margin-top: 1rem; }
.te-save, .te-cancel { display: inline-flex; align-items: center; justify-content: center; gap: .55rem; min-height: 48px; border-radius: 12px; font-family: inherit; font-size: .74rem; font-weight: 800; line-height: 1; text-decoration: none; transition: .18s ease; }
.te-save { border: 0; background: var(--te-forest); color: #fff; box-shadow: 0 8px 18px rgba(23,107,77,.2); }
.te-save:hover { background: var(--te-forest-dark); color: #fff; transform: translateY(-1px); }
.te-cancel { border: 1px solid #d7d8d1; background: #fff; color: var(--te-ink-soft); }
.te-cancel:hover { border-color: #9caaa3; color: var(--te-ink); }
.te-save-note { margin: .75rem 0 0; color: #7c8984; font-size: .61rem; line-height: 1.55; text-align: center; }

.tilawa-editor .filepond--root { margin: 0; font-family: inherit; }
.tilawa-editor .filepond--panel-root { border: 1px dashed #b9c2bb; border-radius: 11px; background: #fff; }
.tilawa-editor .filepond--drop-label { color: var(--te-ink-soft); font-size: .7rem; }
.tilawa-editor .filepond--label-action { color: var(--te-forest); font-weight: 800; text-decoration: none; }
.tilawa-editor .filepond--item-panel { background: var(--te-forest); }
.tilawa-editor .filepond--file-action-button { background: rgba(255,255,255,.2); }

@media (max-width: 1050px) {
  .te-layout { grid-template-columns: minmax(0, 1fr) 280px; }
  .te-range-grid { grid-template-columns: 1fr 1fr; }
  .te-range-grid .te-field:first-child { grid-column: 1 / -1; }
}

@media (max-width: 820px) {
  .te-masthead { min-height: 0; align-items: flex-start; flex-direction: column; }
  .te-masthead-meta { align-items: flex-start; }
  .te-layout { grid-template-columns: 1fr; }
  .te-aside { position: static; }
  .te-summary { display: grid; grid-template-columns: 210px 1fr; }
  .te-summary-art { aspect-ratio: auto; min-height: 100%; }
}

@media (max-width: 600px) {
  .te-masthead { margin-inline: -.25rem; padding: 1.4rem; border-radius: 20px; }
  .te-section-head, .te-section-body { padding-inline: 1rem; }
  .te-field-grid, .te-media-grid, .te-range-grid { grid-template-columns: 1fr; }
  .te-range-grid .te-field:first-child { grid-column: auto; }
  .te-status-options { grid-template-columns: 1fr; }
  .te-combobox-panel { position: fixed; top: 50%; right: 1rem; left: 1rem; width: auto; max-height: min(82vh, 620px); transform: translateY(-50%); transform-origin: center; animation: te-selector-mobile-in .16s ease-out; }
  .te-combobox-options { --selector-columns: 1 !important; max-height: min(58vh, 390px); }
  .te-summary { display: block; }
  .te-summary-art { aspect-ratio: 16 / 8; }
}
@keyframes te-selector-mobile-in { from { opacity: 0; transform: translateY(calc(-50% + 8px)) scale(.98); } to { opacity: 1; transform: translateY(-50%) scale(1); } }
</style>
@endpush

@section('content')
<div class="tilawa-editor">
  <form method="POST" action="{{ route('admin.tilawat.update', $tilawa) }}" id="tilawa-form">
    @csrf
    @method('PUT')

    <header class="te-masthead">
      <div class="te-masthead-copy">
        <div class="te-eyebrow">{{ __('Tilawa workspace') }}</div>
        <h2>{{ old('title_ar', $tilawa->title_ar) }}</h2>
        <p>{{ __('Shape the recitation details, Quran reference and media from one focused workspace.') }}</p>
      </div>
      <div class="te-masthead-meta">
        <span class="te-status-chip"><i class="fas fa-circle-check"></i> {{ $reviewLabel }}</span>
        <span class="te-record-id">{{ __('Record') }} #{{ $tilawa->id }}</span>
      </div>
    </header>

    @if($errors->any())
      <div class="te-alert" role="alert">
        <span class="te-alert-icon"><i class="fas fa-triangle-exclamation"></i></span>
        <div>
          <strong>{{ __('Some details need your attention') }}</strong>
          <ul class="te-error-list">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    @endif

    @if($tilawa->review_status === 'rejected' && $tilawa->rejection_note)
      <div class="te-alert">
        <span class="te-alert-icon"><i class="fas fa-comment-dots"></i></span>
        <div>
          <strong>{{ __('Returned for changes') }}</strong>
          <p>{{ $tilawa->rejection_note }} — {{ __('Fix the issue above and save to resubmit for review.') }}</p>
        </div>
      </div>
    @elseif($tilawa->review_status === 'pending' && ! $isAdmin)
      <div class="te-alert is-pending">
        <span class="te-alert-icon"><i class="fas fa-clock"></i></span>
        <div>
          <strong>{{ __('This tilawa is awaiting review.') }}</strong>
          <p>{{ __('You can still refine its details. Saving will send the latest version to the review queue.') }}</p>
        </div>
      </div>
    @endif

    <div class="te-layout">
      <main class="te-content">
        <section class="te-section" aria-labelledby="identity-title">
          <header class="te-section-head">
            <span class="te-section-no">01</span>
            <div>
              <h3 class="te-section-title" id="identity-title">{{ __('Identity & ownership') }}</h3>
              <p class="te-section-copy">{{ __('The public title, reciter and URL people will use to find this recitation.') }}</p>
            </div>
          </header>
          <div class="te-section-body">
            <div class="te-field-grid">
              <div class="te-field">
                <label class="te-label" for="title_ar">{{ __('Title (Arabic)') }} <span class="te-required">*</span></label>
                <input class="te-input" id="title_ar" type="text" name="title_ar" value="{{ old('title_ar', $tilawa->title_ar) }}" required dir="rtl" placeholder="{{ __('سورة الفاتحة') }}">
                @error('title_ar')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
              <div class="te-field">
                <label class="te-label" for="title_en">{{ __('Title (English)') }} <span class="te-label-note">{{ __('Optional') }}</span></label>
                <input class="te-input" id="title_en" type="text" name="title_en" value="{{ old('title_en', $tilawa->title_en) }}" dir="ltr" placeholder="{{ __('Surah Al-Fatiha') }}">
                @error('title_en')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
            </div>

            <div class="te-field-grid">
              <div class="te-field">
                <label class="te-label" for="qari_id">{{ __('Qari') }} <span class="te-required">*</span></label>
                <div class="te-combobox" data-search-grid-select data-columns="2">
                  <input type="hidden" id="qari_id" name="qari_id" value="{{ $selectedQariId }}">
                  <button class="te-combobox-trigger" type="button" aria-haspopup="listbox" aria-expanded="false">
                    <span class="te-combobox-selected-media">
                      <img src="{{ $selectedQari?->image_url }}" alt="" data-selected-image @if(! $selectedQari?->image_url) hidden @endif>
                      <i class="fas fa-microphone-lines" data-selected-fallback @if($selectedQari?->image_url) hidden @endif></i>
                    </span>
                    <span class="te-combobox-selected-copy">
                      <strong data-selected-label>{{ $selectedQari?->name ?? __('Choose the reciter') }}</strong>
                      <small data-selected-meta>{{ __('Search by Arabic or English name') }}</small>
                    </span>
                    <span class="te-combobox-chevron"><i class="fas fa-chevron-down"></i></span>
                  </button>
                  <div class="te-combobox-panel" hidden>
                    <label class="te-combobox-search">
                      <i class="fas fa-magnifying-glass"></i>
                      <input type="search" data-select-search placeholder="{{ __('Search reciters…') }}" autocomplete="off">
                      <span class="te-combobox-esc">ESC</span>
                    </label>
                    <div class="te-combobox-options" data-select-options role="listbox" aria-label="{{ __('Qari') }}" style="--selector-columns:2">
                      @foreach($qaris as $qari)
                        <button class="te-combobox-option" type="button" role="option"
                                data-value="{{ $qari->id }}"
                                data-label="{{ $qari->name }}"
                                data-image="{{ $qari->image_url }}"
                                data-search-text="{{ $qari->name_ar }} {{ $qari->name_en }}"
                                aria-selected="{{ $selectedQariId === (string) $qari->id ? 'true' : 'false' }}">
                          <span class="te-combobox-option-media"><img src="{{ $qari->image_url }}" alt="" loading="lazy"></span>
                          <span class="te-combobox-option-copy">
                            <strong>{{ $qari->name }}</strong>
                            <small>{{ $qari->name_en && $qari->name_en !== $qari->name ? $qari->name_en : __('Reciter') }}</small>
                          </span>
                          <i class="fas fa-check te-combobox-option-check"></i>
                        </button>
                      @endforeach
                    </div>
                    <div class="te-combobox-empty" data-select-empty hidden><i class="fas fa-magnifying-glass"></i> {{ __('No matching results') }}</div>
                    <div class="te-combobox-footer">
                      <span><strong data-result-count>{{ $qaris->count() }}</strong> {{ __('options') }}</span>
                      <span><i class="fas fa-keyboard"></i>{{ __('Use arrow keys to navigate') }}</span>
                    </div>
                  </div>
                </div>
                @error('qari_id')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
              <div class="te-field">
                <label class="te-label" for="slug">{{ __('Slug (URL)') }} <span class="te-label-note">{{ __('Use with care') }}</span></label>
                <input class="te-input" id="slug" type="text" name="slug" value="{{ old('slug', $tilawa->slug) }}" dir="ltr" placeholder="surah-al-fatiha" aria-describedby="slug-help">
                <span class="te-help" id="slug-help"><i class="fas fa-link"></i>{{ __('Changing this may break existing shared links.') }}</span>
                @error('slug')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
            </div>

            @if($isAdmin)
              <div class="te-field">
                <span class="te-label" id="status-label">{{ __('Publication status') }}</span>
                <div class="te-status-options" role="radiogroup" aria-labelledby="status-label">
                  <label class="te-status-option">
                    <input type="radio" name="status" value="active" @checked(old('status', $tilawa->status) === 'active')>
                    <span><i class="fas fa-circle-check"></i>{{ __('Active') }}</span>
                  </label>
                  <label class="te-status-option">
                    <input type="radio" name="status" value="pending" @checked(old('status', $tilawa->status) === 'pending')>
                    <span><i class="fas fa-clock"></i>{{ __('Pending') }}</span>
                  </label>
                  <label class="te-status-option">
                    <input type="radio" name="status" value="inactive" @checked(old('status', $tilawa->status) === 'inactive')>
                    <span><i class="fas fa-circle-pause"></i>{{ __('Inactive') }}</span>
                  </label>
                </div>
              </div>
            @endif
          </div>
        </section>

        <section class="te-section" aria-labelledby="reference-title">
          <header class="te-section-head">
            <span class="te-section-no">02</span>
            <div>
              <h3 class="te-section-title" id="reference-title">{{ __('Quran reference') }}</h3>
              <p class="te-section-copy">{{ __('Place the recitation precisely within the surah and its ayah range.') }}</p>
            </div>
          </header>
          <div class="te-section-body">
            <div class="te-range-grid">
              <div class="te-field">
                <label class="te-label" for="surah_number">{{ __('Surah') }}</label>
                <div class="te-combobox" data-search-grid-select data-columns="3">
                  <input type="hidden" id="surah_number" name="surah_number" value="{{ $selectedSurahNumber }}">
                  <button class="te-combobox-trigger" type="button" aria-haspopup="listbox" aria-expanded="false">
                    <span class="te-combobox-selected-media" data-selected-badge>{{ $selectedSurahNumber !== '' ? $selectedSurahNumber : '—' }}</span>
                    <span class="te-combobox-selected-copy">
                      <strong data-selected-label>{{ $selectedSurahName ?? __('— No surah —') }}</strong>
                      <small data-selected-meta>{{ __('Search by surah name or number') }}</small>
                    </span>
                    <span class="te-combobox-chevron"><i class="fas fa-chevron-down"></i></span>
                  </button>
                  <div class="te-combobox-panel" hidden>
                    <label class="te-combobox-search">
                      <i class="fas fa-magnifying-glass"></i>
                      <input type="search" data-select-search placeholder="{{ __('Search surahs…') }}" autocomplete="off">
                      <span class="te-combobox-esc">ESC</span>
                    </label>
                    <div class="te-combobox-options" data-select-options role="listbox" aria-label="{{ __('Surah') }}" style="--selector-columns:3">
                      <button class="te-combobox-option" type="button" role="option"
                              data-value=""
                              data-label="{{ __('— No surah —') }}"
                              data-badge="—"
                              data-search-text="{{ __('No surah') }}"
                              aria-selected="{{ $selectedSurahNumber === '' ? 'true' : 'false' }}">
                        <span class="te-combobox-option-media">—</span>
                        <span class="te-combobox-option-copy"><strong>{{ __('— No surah —') }}</strong><small>{{ __('Clear selection') }}</small></span>
                        <i class="fas fa-check te-combobox-option-check"></i>
                      </button>
                      @foreach(config('surahs') as $number => $name)
                        <button class="te-combobox-option" type="button" role="option"
                                data-value="{{ $number }}"
                                data-label="{{ $name }}"
                                data-badge="{{ $number }}"
                                data-search-text="{{ $number }} {{ $name }}"
                                aria-selected="{{ $selectedSurahNumber === (string) $number ? 'true' : 'false' }}">
                          <span class="te-combobox-option-media">{{ $number }}</span>
                          <span class="te-combobox-option-copy"><strong>{{ $name }}</strong><small>{{ __('Surah') }} {{ $number }}</small></span>
                          <i class="fas fa-check te-combobox-option-check"></i>
                        </button>
                      @endforeach
                    </div>
                    <div class="te-combobox-empty" data-select-empty hidden><i class="fas fa-magnifying-glass"></i> {{ __('No matching results') }}</div>
                    <div class="te-combobox-footer">
                      <span><strong data-result-count>{{ count(config('surahs')) + 1 }}</strong> {{ __('options') }}</span>
                      <span><i class="fas fa-keyboard"></i>{{ __('Use arrow keys to navigate') }}</span>
                    </div>
                  </div>
                </div>
                @error('surah_number')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
              <div class="te-field">
                <label class="te-label" for="ayah_from">{{ __('From ayah') }}</label>
                <input class="te-input" id="ayah_from" type="number" name="ayah_from" min="1" max="300" value="{{ old('ayah_from', $tilawa->ayah_from) }}" placeholder="1">
                @error('ayah_from')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
              <div class="te-field">
                <label class="te-label" for="ayah_to">{{ __('To ayah') }}</label>
                <input class="te-input" id="ayah_to" type="number" name="ayah_to" min="1" max="300" value="{{ old('ayah_to', $tilawa->ayah_to) }}" placeholder="7">
                @error('ayah_to')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
            </div>

            @if($tilawa->isMultiSurah())
              <span class="te-help"><i class="fas fa-layer-group"></i>{{ __('This recitation spans several surahs') }}: {{ $tilawa->surah_label }}. {{ __('The ayah range applies to single-surah recitations only.') }}</span>
            @endif

            <div class="te-field-grid">
              <div class="te-field">
                <label class="te-label" for="recorded_at">{{ __('Recorded Date') }} <span class="te-label-note">{{ __('Optional') }}</span></label>
                <input class="te-input" id="recorded_at" type="date" name="recorded_at" value="{{ old('recorded_at', $tilawa->recorded_at?->format('Y-m-d')) }}">
                @error('recorded_at')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
              <div class="te-field">
                <label class="te-label" for="recorded_place">{{ __('Recorded Place') }} <span class="te-label-note">{{ __('Optional') }}</span></label>
                <input class="te-input" id="recorded_place" type="text" name="recorded_place" value="{{ old('recorded_place', $tilawa->recorded_place) }}" placeholder="{{ __('Makkah, Saudi Arabia') }}">
                @error('recorded_place')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
            </div>
          </div>
        </section>

        <section class="te-section" aria-labelledby="description-title">
          <header class="te-section-head">
            <span class="te-section-no">03</span>
            <div>
              <h3 class="te-section-title" id="description-title">{{ __('Descriptions') }}</h3>
              <p class="te-section-copy">{{ __('Add useful context for listeners without repeating the title.') }}</p>
            </div>
          </header>
          <div class="te-section-body">
            <div class="te-field-grid">
              <div class="te-field">
                <label class="te-label" for="description_ar">{{ __('Description (Arabic)') }} <span class="te-label-note">{{ __('Optional') }}</span></label>
                <textarea class="te-input" id="description_ar" name="description_ar" dir="rtl" placeholder="{{ __('Add a short Arabic description…') }}">{{ old('description_ar', $tilawa->description_ar) }}</textarea>
                @error('description_ar')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
              <div class="te-field">
                <label class="te-label" for="description_en">{{ __('Description (English)') }} <span class="te-label-note">{{ __('Optional') }}</span></label>
                <textarea class="te-input" id="description_en" name="description_en" dir="ltr" placeholder="{{ __('Add a short English description…') }}">{{ old('description_en', $tilawa->description_en) }}</textarea>
                @error('description_en')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
            </div>
          </div>
        </section>

        <section class="te-section" aria-labelledby="media-title">
          <header class="te-section-head">
            <span class="te-section-no">04</span>
            <div>
              <h3 class="te-section-title" id="media-title">{{ __('Audio & artwork') }}</h3>
              <p class="te-section-copy">{{ __('Replace media only when needed. Existing files stay untouched if you leave these empty.') }}</p>
            </div>
          </header>
          <div class="te-section-body">
            <div class="te-media-grid">
              <div class="te-media-card">
                <div class="te-media-kicker"><i class="fas fa-wave-square"></i>{{ __('Audio master') }}</div>
                <div class="te-media-current"><i class="fas fa-circle-check"></i><span>{{ __('Current audio') }} · {{ $tilawa->formatted_duration }}</span></div>
                <input type="hidden" name="audio_tmp" id="audio_tmp_input">
                <input type="file" id="audio-pond" data-filepond data-pond-type="audio" data-pond-token="audio_tmp" accept="audio/mp3,audio/mpeg,audio/ogg,audio/wav">
                <span class="te-help"><i class="fas fa-circle-info"></i>{{ __('Leave empty to keep current audio') }}</span>
                @error('audio_tmp')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
              <div class="te-media-card">
                <div class="te-media-kicker"><i class="fas fa-image"></i>{{ __('Cover artwork') }}</div>
                <div class="te-media-current">
                  @if($tilawa->cover_image)
                    <img class="te-cover-thumb" src="{{ $tilawa->cover_url }}" alt="">
                  @else
                    <i class="fas fa-image"></i>
                  @endif
                  <span>{{ $tilawa->cover_image ? __('Current cover is ready') : __('Using the generated cover') }}</span>
                </div>
                <input type="hidden" name="cover_image_tmp" id="cover_image_tmp_input">
                <input type="file" id="cover-pond" data-filepond data-pond-type="cover" data-pond-token="cover_image_tmp" accept="image/jpeg,image/png,image/webp">
                <span class="te-help"><i class="fas fa-circle-info"></i>{{ __('Leave empty to keep current cover') }}</span>
                @error('cover_image_tmp')<span class="te-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
              </div>
            </div>
          </div>
        </section>
      </main>

      <aside class="te-aside" aria-label="{{ __('Publication summary') }}">
        <div class="te-summary">
          @if($tilawa->cover_image)
            <div class="te-summary-art"><img src="{{ $tilawa->cover_url }}" alt=""></div>
          @else
            <div class="te-summary-art is-placeholder"><i class="fas fa-wave-square"></i></div>
          @endif
          <div class="te-summary-body">
            <span class="te-summary-kicker">{{ __('Publication snapshot') }}</span>
            <div class="te-summary-title">{{ $tilawa->title_ar }}</div>
            <div class="te-summary-qari">{{ $tilawa->qari?->name }}</div>

            <div class="te-summary-facts">
              <div class="te-fact"><span>{{ __('Duration') }}</span><strong>{{ $tilawa->formatted_duration }}</strong></div>
              <div class="te-fact"><span>{{ __('Status') }}</span><strong>{{ __(ucfirst($tilawa->status)) }}</strong></div>
              <div class="te-fact"><span>{{ __('Surah') }}</span><strong>{{ $tilawa->surah_label ?: __('Not set') }}</strong></div>
              <div class="te-fact"><span>{{ __('Review') }}</span><strong>{{ $reviewLabel }}</strong></div>
            </div>

            <label class="te-featured">
              <span class="te-featured-copy">
                <strong><i class="fas fa-star"></i> {{ __('Featured Tilawa') }}</strong>
                <small>{{ __('Give this recitation extra visibility across the site.') }}</small>
              </span>
              <span class="te-switch">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $tilawa->is_featured))>
                <span class="te-switch-track"></span>
              </span>
            </label>

            <div class="te-actions">
              <button type="submit" class="te-save" id="submit-btn">
                <i class="fas {{ $needsResubmission ? 'fa-paper-plane' : 'fa-check' }}"></i>
                {{ $needsResubmission ? __('Save & Resubmit for Review') : __('Save changes') }}
              </button>
              <a href="{{ route('admin.tilawat.index') }}" class="te-cancel">{{ __('Cancel') }}</a>
            </div>
            <p class="te-save-note">{{ __('Media files are replaced only after the form saves successfully.') }}</p>
          </div>
        </div>
      </aside>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const selectors = [...document.querySelectorAll('[data-search-grid-select]')];

  const normalizeSearch = value => String(value || '')
    .normalize('NFKD')
    .replace(/[\u064B-\u065F\u0670]/g, '')
    .replace(/[أإآ]/g, 'ا')
    .replace(/ى/g, 'ي')
    .toLocaleLowerCase()
    .trim();

  const closeOthers = current => {
    selectors.forEach(selector => {
      if (selector !== current) selector._closeSearchGrid?.();
    });
  };

  selectors.forEach((selector, selectorIndex) => {
    const hiddenInput = selector.querySelector('input[type="hidden"]');
    const trigger = selector.querySelector('.te-combobox-trigger');
    const panel = selector.querySelector('.te-combobox-panel');
    const searchInput = selector.querySelector('[data-select-search]');
    const optionsGrid = selector.querySelector('[data-select-options]');
    const options = [...selector.querySelectorAll('.te-combobox-option')];
    const emptyState = selector.querySelector('[data-select-empty]');
    const resultCount = selector.querySelector('[data-result-count]');
    const selectedLabel = selector.querySelector('[data-selected-label]');
    const selectedImage = selector.querySelector('[data-selected-image]');
    const selectedFallback = selector.querySelector('[data-selected-fallback]');
    const selectedBadge = selector.querySelector('[data-selected-badge]');
    const containingSection = selector.closest('.te-section');
    const panelId = `search-grid-select-${selectorIndex + 1}`;

    panel.id = panelId;
    trigger.setAttribute('aria-controls', panelId);

    const visibleOptions = () => options.filter(option => !option.hidden);

    const resetFilter = () => {
      searchInput.value = '';
      options.forEach(option => { option.hidden = false; });
      emptyState.hidden = true;
      resultCount.textContent = options.length;
    };

    const close = ({ returnFocus = false } = {}) => {
      if (panel.hidden) return;
      selector.classList.remove('is-open');
      containingSection?.classList.remove('has-open-selector');
      trigger.setAttribute('aria-expanded', 'false');
      panel.hidden = true;
      resetFilter();
      if (returnFocus) trigger.focus();
    };

    const open = ({ focusSelected = false } = {}) => {
      closeOthers(selector);
      selector.classList.add('is-open');
      containingSection?.classList.add('has-open-selector');
      trigger.setAttribute('aria-expanded', 'true');
      panel.hidden = false;
      requestAnimationFrame(() => {
        if (focusSelected) {
          (options.find(option => option.getAttribute('aria-selected') === 'true') || options[0])?.focus();
        } else {
          searchInput.focus();
        }
      });
    };

    selector._closeSearchGrid = close;

    const choose = option => {
      hiddenInput.value = option.dataset.value;
      hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
      selectedLabel.textContent = option.dataset.label;

      options.forEach(candidate => {
        candidate.setAttribute('aria-selected', candidate === option ? 'true' : 'false');
      });

      if (selectedBadge) selectedBadge.textContent = option.dataset.badge || '—';

      if (selectedImage && option.dataset.image) {
        selectedImage.src = option.dataset.image;
        selectedImage.hidden = false;
        if (selectedFallback) selectedFallback.hidden = true;
      } else if (selectedImage) {
        selectedImage.hidden = true;
        if (selectedFallback) selectedFallback.hidden = false;
      }

      close({ returnFocus: true });
    };

    trigger.addEventListener('click', () => panel.hidden ? open() : close());
    trigger.addEventListener('keydown', event => {
      if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
        event.preventDefault();
        open({ focusSelected: event.key === 'ArrowDown' || event.key === 'ArrowUp' });
      }
    });

    searchInput.addEventListener('input', () => {
      const query = normalizeSearch(searchInput.value);
      let matches = 0;

      options.forEach(option => {
        const matchesQuery = !query || normalizeSearch(option.dataset.searchText).includes(query);
        option.hidden = !matchesQuery;
        if (matchesQuery) matches++;
      });

      resultCount.textContent = matches;
      emptyState.hidden = matches > 0;
    });

    searchInput.addEventListener('keydown', event => {
      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        const available = visibleOptions();
        (event.key === 'ArrowDown' ? available[0] : available.at(-1))?.focus();
      }
      if (event.key === 'Escape') {
        event.preventDefault();
        close({ returnFocus: true });
      }
    });

    options.forEach(option => option.addEventListener('click', () => choose(option)));

    optionsGrid.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        event.preventDefault();
        close({ returnFocus: true });
        return;
      }

      const available = visibleOptions();
      const currentIndex = available.indexOf(document.activeElement);
      if (currentIndex < 0) return;

      const renderedColumns = getComputedStyle(optionsGrid).gridTemplateColumns.split(' ').length || 1;
      const isRtl = document.documentElement.dir === 'rtl';
      let nextIndex = currentIndex;

      if (event.key === 'ArrowDown') nextIndex += renderedColumns;
      if (event.key === 'ArrowUp') nextIndex -= renderedColumns;
      if (event.key === 'ArrowRight') nextIndex += isRtl ? -1 : 1;
      if (event.key === 'ArrowLeft') nextIndex += isRtl ? 1 : -1;
      if (event.key === 'Home') nextIndex = 0;
      if (event.key === 'End') nextIndex = available.length - 1;

      if (nextIndex !== currentIndex) {
        event.preventDefault();
        available[Math.max(0, Math.min(available.length - 1, nextIndex))]?.focus();
      }
    });
  });

  document.addEventListener('click', event => {
    selectors.forEach(selector => {
      if (!selector.contains(event.target)) selector._closeSearchGrid?.();
    });
  });
})();
</script>
@endpush
