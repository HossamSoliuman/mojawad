@extends('layouts.app')
@section('title', $qari->name)
@section('content')

<div style="position:relative;overflow:hidden;padding-bottom:2rem">
  <div style="position:absolute;inset:0">
    <img src="{{ $qari->image_url }}" alt="" style="width:100%;height:100%;object-fit:cover;opacity:.12;filter:blur(2px)">
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(7,7,15,.88) 0%,var(--bg) 100%)"></div>
  </div>
  <div class="wrap z1" style="padding-top:2.5rem">
    <a href="{{ route('qaris.index') }}" style="font-size:.8rem;color:var(--text2);display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1.4rem">
      <i class="fas fa-arrow-left"></i> All Qaris
    </a>
    <div style="display:flex;align-items:flex-end;gap:1.85rem;flex-wrap:wrap">
      <img src="{{ $qari->image_url }}" alt="{{ $qari->name }}"
        style="width:148px;height:148px;border-radius:var(--r2);object-fit:cover;border:2px solid var(--border2);box-shadow:0 20px 60px rgba(0,0,0,.65);flex-shrink:0">
      <div>
        @if($qari->is_featured)
        <div class="badge badge-gold" style="margin-bottom:.65rem"><i class="fas fa-star"></i> Featured</div>
        @endif
        <h1 style="font-size:clamp(1.65rem,4vw,2.7rem);margin-bottom:.4rem">{{ $qari->name }}</h1>
        <div class="gold" style="font-size:.88rem;margin-bottom:1.1rem">
          <i class="fas fa-music"></i> {{ $tilawat->total() }} Tilawat
        </div>
        <button class="btn btn-primary" onclick="playFirst()">
          <i class="fas fa-play"></i> Play All
        </button>
      </div>
    </div>
  </div>
</div>

<div class="wrap z1" style="padding-bottom:3rem">
  @if($qari->biography)
  <div class="card" style="padding:1.5rem;margin-bottom:2.1rem;max-width:680px">
    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.09em;color:var(--text2);margin-bottom:.65rem;font-family:'Cinzel',serif">
      Biography
    </div>
    <div style="line-height:1.8;color:var(--text2)">{!! $qari->biography !!}</div>
  </div>
  @endif

  <div class="sec-title"><i class="fas fa-list-music gold"></i> Tilawat</div>

  @if($tilawat->isEmpty())
  <div style="text-align:center;padding:4rem 1rem;color:var(--text2)">
    <i class="fas fa-music-slash" style="font-size:2.5rem;display:block;margin-bottom:.65rem"></i>
    No tilawat yet.
  </div>
  @else
  <div style="display:flex;flex-direction:column;gap:.45rem" id="tList">
    @foreach($tilawat as $t)
    <div class="track-row"
      onclick="playTilawa({{ $t->id }},'{{ $t->audio_url }}',{{ json_encode($t->title) }},{{ json_encode($t->qari->name) }},'{{ $t->cover_url }}',{{ $t->duration }},'{{ route('tilawa.download', $t) }}')">
      <span class="track-num">{{ $loop->iteration }}</span>
      <img src="{{ $t->cover_url }}" class="track-cover" alt="">
      <div class="track-info">
        <div class="track-title">{{ $t->title }}</div>
        @if($t->recorded_place)
        <div class="track-place"><i class="fas fa-location-dot"></i> {{ $t->recorded_place }}</div>
        @endif
      </div>
      <span style="font-size:.74rem;color:var(--text2);font-family:monospace;flex-shrink:0">{{ $t->formatted_duration }}</span>
      <span style="font-size:.74rem;color:var(--text2);flex-shrink:0"><i class="fas fa-heart"></i> {{ number_format($t->likes_count) }}</span>
      <a href="{{ route('tilawa.show',$t) }}" onclick="event.stopPropagation()" class="btn-icon" title="Details">
        <i class="fas fa-circle-info"></i>
      </a>
    </div>
    @endforeach
  </div>
  {{ $tilawat->links('vendor.pagination.custom') }}
  @endif
</div>

@push('scripts')
<script>
function playFirst() {
  const el = document.querySelector('#tList .track-row');
  if (el) el.click();
}
</script>
@endpush
@endsection
