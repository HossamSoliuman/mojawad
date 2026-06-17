@extends('layouts.admin')
@section('title', __('Dashboard'))
@section('page-title', __('Dashboard'))
@section('topbar-actions')
<a href="{{ route('admin.reports') }}" class="btn btn-primary btn-xs"><i class="fas fa-chart-line"></i> {{ __('Reports') }}</a>
@endsection
@section('content')

@php
$deltaPct = function (int $current, int $previous): ?float {
    if ($previous === 0) {
        return $current > 0 ? 100.0 : null;
    }

    return round((($current - $previous) / $previous) * 100, 1);
};
@endphp

{{-- KPI CARDS --}}
<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-top">
      <div class="kpi-icon"><i class="fas fa-music"></i></div>
      <span class="kpi-chip">+{{ number_format($stats['tilawat_30d']) }} / {{ __('30 days') }}</span>
    </div>
    <div class="kpi-val">{{ number_format($stats['total_tilawat']) }}</div>
    <div class="kpi-lbl">{{ __('Tilawat') }} · {{ number_format($stats['active_tilawat']) }} {{ __('active') }}</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-top">
      <div class="kpi-icon"><i class="fas fa-download"></i></div>
      @php($d = $deltaPct($stats['downloads_7d'], $stats['downloads_prev7d']))
      @if($d !== null)
      <span class="kpi-delta {{ $d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat') }}">
        <i class="fas {{ $d > 0 ? 'fa-arrow-trend-up' : ($d < 0 ? 'fa-arrow-trend-down' : 'fa-minus') }}"></i> {{ abs($d) }}%
      </span>
      @endif
    </div>
    <div class="kpi-val">{{ number_format($stats['total_downloads']) }}</div>
    <div class="kpi-lbl">{{ __('Downloads') }} · {{ number_format($stats['downloads_7d']) }} {{ __('this week') }}</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-top">
      <div class="kpi-icon"><i class="fas fa-heart"></i></div>
      @php($d = $deltaPct($stats['likes_7d'], $stats['likes_prev7d']))
      @if($d !== null)
      <span class="kpi-delta {{ $d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat') }}">
        <i class="fas {{ $d > 0 ? 'fa-arrow-trend-up' : ($d < 0 ? 'fa-arrow-trend-down' : 'fa-minus') }}"></i> {{ abs($d) }}%
      </span>
      @endif
    </div>
    <div class="kpi-val">{{ number_format($stats['total_likes']) }}</div>
    <div class="kpi-lbl">{{ __('Likes') }} · {{ number_format($stats['likes_7d']) }} {{ __('this week') }}</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-top">
      <div class="kpi-icon"><i class="fas fa-users"></i></div>
      <span class="kpi-chip">+{{ number_format($stats['users_7d']) }} / {{ __('7 days') }}</span>
    </div>
    <div class="kpi-val">{{ number_format($stats['total_users']) }}</div>
    <div class="kpi-lbl">{{ __('Users') }}</div>
  </div>
</div>

{{-- NEEDS ATTENTION --}}
@php($hasAttention = collect($attention)->filter()->isNotEmpty())
<div class="panel" style="margin-bottom:1.85rem">
  <div class="panel-hd">
    <i class="fas fa-triangle-exclamation gold"></i> {{ __('Needs attention') }}
  </div>
  <div class="panel-bd">
    @if(! $hasAttention)
    <div class="att-clear"><i class="fas fa-circle-check"></i> {{ __('All clear — nothing needs your attention.') }}</div>
    @else
    <div class="att-grid">
      @if($attention['pending_review'] > 0)
      <a href="{{ route('admin.tilawat.index', ['review' => 'pending']) }}" class="att-card warn">
        <span class="att-num">{{ number_format($attention['pending_review']) }}</span>
        <span class="att-lbl"><i class="fas fa-clipboard-check"></i> {{ __('Awaiting review') }}</span>
      </a>
      @endif
      @if($attention['rejected'] > 0)
      <a href="{{ route('admin.tilawat.index', ['review' => 'rejected']) }}" class="att-card danger">
        <span class="att-num">{{ number_format($attention['rejected']) }}</span>
        <span class="att-lbl"><i class="fas fa-xmark"></i> {{ __('Rejected tilawat') }}</span>
      </a>
      @endif
      @if($attention['pending_qaris'] > 0)
      <a href="{{ route('admin.qaris.index', ['status' => 'pending']) }}" class="att-card warn">
        <span class="att-num">{{ number_format($attention['pending_qaris']) }}</span>
        <span class="att-lbl"><i class="fas fa-microphone-lines"></i> {{ __('Pending qaris') }}</span>
      </a>
      @endif
      @if($attention['stuck_uploads'] > 0)
      <a href="{{ route('admin.tilawat.index') }}" class="att-card danger">
        <span class="att-num">{{ number_format($attention['stuck_uploads']) }}</span>
        <span class="att-lbl"><i class="fas fa-cloud-arrow-up"></i> {{ __('Stuck uploads') }}</span>
      </a>
      @endif
      @if($attention['local_audio'] > 0)
      <div class="att-card">
        <span class="att-num">{{ number_format($attention['local_audio']) }}</span>
        <span class="att-lbl"><i class="fas fa-hard-drive"></i> {{ __('Hosted locally (not on Archive.org)') }}</span>
      </div>
      @endif
    </div>
    @endif
  </div>
</div>

{{-- TREND CHARTS --}}
<div class="chart-grid">
  <div class="panel">
    <div class="panel-hd"><i class="fas fa-download gold"></i> {{ __('Downloads — last 30 days') }}</div>
    <div class="panel-bd"><canvas id="dlChart" height="190"></canvas></div>
  </div>
  <div class="panel">
    <div class="panel-hd"><i class="fas fa-heart gold"></i> {{ __('Likes — last 30 days') }}</div>
    <div class="panel-bd"><canvas id="likeChart" height="190"></canvas></div>
  </div>
</div>

{{-- TOP CONTENT --}}
<div class="chart-grid">
  <div class="panel">
    <div class="panel-hd">
      <i class="fas fa-ranking-star gold"></i> {{ __('Top tilawat by downloads') }}
      <a href="{{ route('admin.reports') }}" class="btn btn-ghost btn-xs" style="margin-inline-start:auto">{{ __('Full report') }}</a>
    </div>
    <div class="panel-bd">
      @php($maxDl = max(1, $topTilawat->max('downloads_count')))
      <div class="rank-list">
        @forelse($topTilawat as $i => $t)
        <div class="rank-item">
          <span class="rank-pos">{{ $i + 1 }}</span>
          <img src="{{ $t->cover_url }}" class="rank-img" alt="">
          <div class="rank-info">
            <div class="rank-title">{{ $t->title }}</div>
            <div class="rank-sub">{{ $t->qari->name }}</div>
            <div class="rank-bar"><span style="width:{{ round($t->downloads_count / $maxDl * 100) }}%"></span></div>
          </div>
          <span class="rank-val"><i class="fas fa-download"></i> {{ number_format($t->downloads_count) }}</span>
        </div>
        @empty
        <div class="att-clear">{{ __('No data yet.') }}</div>
        @endforelse
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-hd"><i class="fas fa-microphone-lines gold"></i> {{ __('Top qaris') }}</div>
    <div class="panel-bd">
      @php($maxQ = max(1, $topQaris->max('total_downloads')))
      <div class="rank-list">
        @forelse($topQaris as $i => $q)
        <div class="rank-item">
          <span class="rank-pos">{{ $i + 1 }}</span>
          <img src="{{ $q->image_url }}" class="rank-img round" alt="">
          <div class="rank-info">
            <div class="rank-title">{{ $q->name }}</div>
            <div class="rank-sub">{{ $q->active_tilawat_count }} {{ __('Tilawat') }} · <i class="fas fa-heart"></i> {{ number_format($q->total_likes ?? 0) }}</div>
            <div class="rank-bar"><span style="width:{{ round(($q->total_downloads ?? 0) / $maxQ * 100) }}%"></span></div>
          </div>
          <span class="rank-val"><i class="fas fa-download"></i> {{ number_format($q->total_downloads ?? 0) }}</span>
        </div>
        @empty
        <div class="att-clear">{{ __('No data yet.') }}</div>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- RECENT --}}
<div class="panel">
  <div class="panel-hd">
    <i class="fas fa-clock-rotate-left gold"></i> {{ __('Recently Added') }}
    <a href="{{ route('admin.tilawat.index') }}" class="btn btn-ghost btn-xs" style="margin-inline-start:auto">{{ __('View All') }}</a>
  </div>
  <div class="tbl-wrap" style="border:none;border-radius:0">
    <table class="tbl">
      <thead><tr><th>{{ __('Title') }}</th><th>{{ __('Qari') }}</th><th>{{ __('By') }}</th><th>{{ __('Status') }}</th><th>{{ __('Date') }}</th><th></th></tr></thead>
      <tbody>
        @foreach($recentTilawat as $t)
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:.68rem">
              <img src="{{ $t->cover_url }}" style="width:32px;height:32px;border-radius:6px;object-fit:cover;flex-shrink:0" alt="">
              <span style="font-weight:600;font-size:.86rem">{{ $t->title }}</span>
            </div>
          </td>
          <td style="color:var(--gold);font-size:.85rem">{{ $t->qari->name }}</td>
          <td style="font-size:.82rem;color:var(--text2)">{{ $t->uploader?->name ?? '—' }}</td>
          <td>
            <span class="badge {{ $t->status==='active'?'badge-green':($t->status==='pending'?'badge-amber':'badge-red') }}">
              {{ __(ucfirst($t->status)) }}
            </span>
          </td>
          <td style="font-size:.78rem;color:var(--text2)">{{ $t->created_at->format('d M Y') }}</td>
          <td>
            <a href="{{ route('admin.tilawat.edit',$t) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="fas fa-pen-to-square"></i></a>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>
<script>
(() => {
  /* Admin corporate colours — read from body.adm computed style */
  const bodyStyle = getComputedStyle(document.body);
  const primary = bodyStyle.getPropertyValue('--gold').trim() || '#2563eb';
  const text2   = '#94a3b8';
  const border   = '#f1f5f9';
  const labels   = @json($charts['labels']).map(d => d.slice(5));
  const rtl      = document.documentElement.dir === 'rtl';

  Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

  const lineOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: {
      backgroundColor: '#fff',
      titleColor: '#0f172a',
      bodyColor: '#64748b',
      borderColor: '#e2e8f0',
      borderWidth: 1,
      padding: 10,
      cornerRadius: 8,
    }},
    scales: {
      x: { reverse: rtl, ticks: { color: text2, maxTicksLimit: 10, font: { size: 10 } }, grid: { display: false } },
      y: { beginAtZero: true, ticks: { color: text2, precision: 0, font: { size: 10 } }, grid: { color: border } },
    },
    interaction: { intersect: false, mode: 'index' },
  };

  const area = (id, data, color, alphaHex) => new Chart(document.getElementById(id), {
    type: 'line',
    data: { labels, datasets: [{ data, borderColor: color, backgroundColor: color + (alphaHex || '18'),
      fill: true, tension: .38, pointRadius: 0, borderWidth: 2.5 }] },
    options: lineOpts,
  });

  area('dlChart',   @json($charts['downloads']), primary, '14');
  area('likeChart', @json($charts['likes']),     '#ef4444', '12');
})();
</script>
@endpush
