@extends('layouts.app')
@section('title', $qari->name)
@section('meta_desc', __('Listen to Qur\'an recitations by :name', ['name' => $qari->name]))
@section('og_image', $qari->image_url)
@section('content')

<div style="position:relative;overflow:hidden;padding-bottom:2rem">
  <div style="position:absolute;inset:0">
    <img src="{{ $qari->image_url }}" alt="" style="width:100%;height:100%;object-fit:cover;opacity:.12;filter:blur(2px)">
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(17,24,39,.88) 0%,var(--bg) 100%)"></div>
  </div>
  <div class="wrap z1" style="padding-top:2.5rem">
    <a href="{{ route('qaris.index') }}" wire:navigate class="back-link" style="font-size:.8rem;color:var(--text2);display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1.4rem">
      <i class="fas fa-arrow-left"></i> {{ __('All Qaris') }}
    </a>
    <div style="display:flex;align-items:flex-end;gap:1.85rem;flex-wrap:wrap">
      <img src="{{ $qari->image_url }}" alt="{{ $qari->name }}"
        style="width:148px;height:148px;border-radius:var(--r2);object-fit:cover;border:2px solid var(--border2);box-shadow:0 20px 60px rgba(0,0,0,.65);flex-shrink:0">
      <div>
        @if($qari->is_featured)
        <div class="badge badge-gold" style="margin-bottom:.65rem"><i class="fas fa-star"></i> {{ __('Featured') }}</div>
        @endif
        <h1 style="font-size:clamp(1.65rem,4vw,2.7rem);margin-bottom:.4rem">{{ $qari->name }}</h1>
        <div class="gold" style="font-size:.88rem;margin-bottom:1.1rem">
          <i class="fas fa-music"></i> {{ $qari->tilawat()->where('status','active')->count() }} {{ __('Tilawat') }}
        </div>
        <button class="btn btn-primary" onclick="playFirst()">
          <i class="fas fa-play"></i> {{ __('Play All') }}
        </button>
      </div>
    </div>
  </div>
</div>

<div class="wrap z1" style="padding-bottom:3rem">
  @if($qari->biography)
  <div class="card" style="padding:1.5rem;margin-bottom:2.1rem;max-width:680px">
    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.09em;color:var(--text2);margin-bottom:.65rem;font-family:'Cinzel',serif">
      {{ __('Biography') }}
    </div>
    <div style="line-height:1.8;color:var(--text2)">{!! $qari->biography !!}</div>
  </div>
  @endif

  <div class="sec-title"><i class="fas fa-list-music gold"></i> {{ __('Tilawat') }}</div>

  <livewire:tilawa-list :qari="$qari" />
</div>

@push('scripts')
<script>
function playFirst() {
  const el = document.querySelector('#tList [data-track]');
  if (el) el.click();
}
</script>
@endpush
@endsection
