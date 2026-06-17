@extends('layouts.admin')
@section('title', __('Reports'))
@section('page-title', __('Reports'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Reports') }}@endsection
@section('topbar-actions')
<div class="range-pills">
  @foreach([7 => __('7 days'), 30 => __('30 days'), 90 => __('90 days'), 365 => __('Year')] as $value => $label)
  <a href="{{ route('admin.reports', ['range' => $value]) }}" class="range-pill {{ $range === $value ? 'active' : '' }}">{{ $label }}</a>
  @endforeach
</div>
@endsection
@section('content')

{{-- PERIOD TOTALS --}}
<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-top"><div class="kpi-icon"><i class="fas fa-download"></i></div></div>
    <div class="kpi-val">{{ number_format(array_sum($series['downloads']['values'])) }}</div>
    <div class="kpi-lbl">{{ __('Downloads in period') }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-top"><div class="kpi-icon"><i class="fas fa-heart"></i></div></div>
    <div class="kpi-val">{{ number_format(array_sum($series['likes']['values'])) }}</div>
    <div class="kpi-lbl">{{ __('Likes in period') }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-top"><div class="kpi-icon"><i class="fas fa-music"></i></div></div>
    <div class="kpi-val">{{ number_format(array_sum($series['tilawat']['values'])) }}</div>
    <div class="kpi-lbl">{{ __('New tilawat in period') }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-top"><div class="kpi-icon"><i class="fas fa-user-plus"></i></div></div>
    <div class="kpi-val">{{ number_format(array_sum($series['users']['values'])) }}</div>
    <div class="kpi-lbl">{{ __('New users in period') }}</div>
  </div>
</div>

{{-- ENGAGEMENT TRENDS --}}
<div class="panel" style="margin-bottom:1.85rem">
  <div class="panel-hd"><i class="fas fa-chart-line gold"></i> {{ __('Engagement') }}</div>
  <div class="panel-bd"><canvas id="engageChart" height="220"></canvas></div>
</div>

<div class="chart-grid">
  <div class="panel">
    <div class="panel-hd"><i class="fas fa-music gold"></i> {{ __('New tilawat') }}</div>
    <div class="panel-bd"><canvas id="contentChart" height="180"></canvas></div>
  </div>
  <div class="panel">
    <div class="panel-hd"><i class="fas fa-user-plus gold"></i> {{ __('User growth') }}</div>
    <div class="panel-bd"><canvas id="usersChart" height="180"></canvas></div>
  </div>
</div>

{{-- TOP CONTENT IN PERIOD --}}
<div class="chart-grid">
  <div class="panel">
    <div class="panel-hd"><i class="fas fa-download gold"></i> {{ __('Most downloaded in period') }}</div>
    <div class="panel-bd">
      @php($maxDl = max(1, $topDownloaded->max('total')))
      <div class="rank-list">
        @forelse($topDownloaded as $i => $row)
        <div class="rank-item">
          <span class="rank-pos">{{ $i + 1 }}</span>
          <img src="{{ $row->tilawa->cover_url }}" class="rank-img" alt="">
          <div class="rank-info">
            <div class="rank-title">{{ $row->tilawa->title }}</div>
            <div class="rank-sub">{{ $row->tilawa->qari->name }}</div>
            <div class="rank-bar"><span style="width:{{ round($row->total / $maxDl * 100) }}%"></span></div>
          </div>
          <span class="rank-val">{{ number_format($row->total) }}</span>
        </div>
        @empty
        <div class="att-clear">{{ __('No data yet.') }}</div>
        @endforelse
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-hd"><i class="fas fa-heart gold"></i> {{ __('Most liked in period') }}</div>
    <div class="panel-bd">
      @php($maxLk = max(1, $topLiked->max('total')))
      <div class="rank-list">
        @forelse($topLiked as $i => $row)
        <div class="rank-item">
          <span class="rank-pos">{{ $i + 1 }}</span>
          <img src="{{ $row->tilawa->cover_url }}" class="rank-img" alt="">
          <div class="rank-info">
            <div class="rank-title">{{ $row->tilawa->title }}</div>
            <div class="rank-sub">{{ $row->tilawa->qari->name }}</div>
            <div class="rank-bar"><span style="width:{{ round($row->total / $maxLk * 100) }}%"></span></div>
          </div>
          <span class="rank-val">{{ number_format($row->total) }}</span>
        </div>
        @empty
        <div class="att-clear">{{ __('No data yet.') }}</div>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- QARIS + CREATORS --}}
<div class="chart-grid">
  <div class="panel">
    <div class="panel-hd"><i class="fas fa-microphone-lines gold"></i> {{ __('Qaris — all time') }}</div>
    <div class="tbl-wrap" style="border:none;border-radius:0">
      <table class="tbl">
        <thead><tr><th>{{ __('Qari') }}</th><th>{{ __('Tilawat') }}</th><th><i class="fas fa-heart"></i></th><th><i class="fas fa-download"></i></th></tr></thead>
        <tbody>
          @foreach($topQaris as $q)
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:.6rem">
                <img src="{{ $q->image_url }}" style="width:28px;height:28px;border-radius:50%;object-fit:cover" alt="">
                <span style="font-weight:600;font-size:.85rem">{{ $q->name }}</span>
              </div>
            </td>
            <td><span class="badge badge-muted">{{ $q->active_tilawat_count }}</span></td>
            <td style="font-size:.82rem">{{ number_format($q->total_likes ?? 0) }}</td>
            <td style="font-size:.82rem">{{ number_format($q->total_downloads ?? 0) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <div class="panel-hd"><i class="fas fa-user-pen gold"></i> {{ __('Most active creators in period') }}</div>
    <div class="panel-bd">
      @php($maxC = max(1, $creators->max('total')))
      <div class="rank-list">
        @forelse($creators as $i => $row)
        <div class="rank-item">
          <span class="rank-pos">{{ $i + 1 }}</span>
          <div class="rank-info">
            <div class="rank-title">{{ $row->uploader->name }}</div>
            <div class="rank-bar"><span style="width:{{ round($row->total / $maxC * 100) }}%"></span></div>
          </div>
          <span class="rank-val">{{ number_format($row->total) }} {{ __('tilawat') }}</span>
        </div>
        @empty
        <div class="att-clear">{{ __('No uploads in this period.') }}</div>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- REVIEW THROUGHPUT + LIBRARY HEALTH --}}
<div class="chart-grid">
  <div class="panel">
    <div class="panel-hd"><i class="fas fa-clipboard-check gold"></i> {{ __('Review throughput') }}</div>
    <div class="panel-bd">
      <div class="mini-stats">
        <div class="mini-stat"><span class="ms-val" style="color:#4ade80">{{ number_format($reviewStats['approved']) }}</span><span class="ms-lbl">{{ __('Approved') }}</span></div>
        <div class="mini-stat"><span class="ms-val" style="color:#e0626f">{{ number_format($reviewStats['rejected']) }}</span><span class="ms-lbl">{{ __('Rejected') }}</span></div>
        <div class="mini-stat"><span class="ms-val" style="color:#fbbf24">{{ number_format($reviewStats['pending']) }}</span><span class="ms-lbl">{{ __('In queue') }}</span></div>
        <div class="mini-stat"><span class="ms-val">{{ number_format($reviewStats['avg_hours']) }}</span><span class="ms-lbl">{{ __('Avg. hours to review') }}</span></div>
      </div>
      @if($reviewers->isNotEmpty())
      <table class="tbl" style="margin-top:1rem">
        <thead><tr><th>{{ __('Reviewer') }}</th><th>{{ __('Approved') }}</th><th>{{ __('Rejected') }}</th></tr></thead>
        <tbody>
          @foreach($reviewers as $r)
          <tr>
            <td style="font-size:.85rem;font-weight:600">{{ $r['name'] }}</td>
            <td><span class="badge badge-green">{{ $r['approved'] }}</span></td>
            <td><span class="badge badge-red">{{ $r['rejected'] }}</span></td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @endif
    </div>
  </div>

  <div class="panel">
    <div class="panel-hd"><i class="fas fa-server gold"></i> {{ __('Library health') }}</div>
    <div class="panel-bd">
      @php($totalHosted = max(1, $hosting['archive'] + $hosting['local']))
      <div class="health-row">
        <span class="h-lbl"><i class="fas fa-cloud"></i> {{ __('On Archive.org') }}</span>
        <div class="rank-bar" style="flex:1"><span style="width:{{ round($hosting['archive'] / $totalHosted * 100) }}%"></span></div>
        <span class="h-val">{{ number_format($hosting['archive']) }}</span>
      </div>
      <div class="health-row">
        <span class="h-lbl"><i class="fas fa-hard-drive"></i> {{ __('Hosted locally') }}</span>
        <div class="rank-bar" style="flex:1"><span style="width:{{ round($hosting['local'] / $totalHosted * 100) }}%"></span></div>
        <span class="h-val">{{ number_format($hosting['local']) }}</span>
      </div>
      <div class="mini-stats" style="margin-top:1.2rem">
        <div class="mini-stat"><span class="ms-val" style="color:#4ade80">{{ number_format($statuses['active'] ?? 0) }}</span><span class="ms-lbl">{{ __('Active') }}</span></div>
        <div class="mini-stat"><span class="ms-val" style="color:#fbbf24">{{ number_format($statuses['pending'] ?? 0) }}</span><span class="ms-lbl">{{ __('Pending') }}</span></div>
        <div class="mini-stat"><span class="ms-val" style="color:var(--text2)">{{ number_format($statuses['inactive'] ?? 0) }}</span><span class="ms-lbl">{{ __('Inactive') }}</span></div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>
<script>
(() => {
  /* Admin corporate chart colours */
  const primary  = '#2563eb';
  const danger   = '#ef4444';
  const text2    = '#94a3b8';
  const gridLine = '#f1f5f9';
  const labels   = @json($series['downloads']['labels']).map(d => d.slice(5));
  const rtl      = document.documentElement.dir === 'rtl';

  Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

  const tooltipDefaults = {
    backgroundColor: '#fff',
    titleColor: '#0f172a',
    bodyColor: '#64748b',
    borderColor: '#e2e8f0',
    borderWidth: 1,
    padding: 10,
    cornerRadius: 8,
  };

  const baseOpts = (legend) => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: legend, labels: { color: text2, boxWidth: 12, font: { size: 11 } } },
      tooltip: tooltipDefaults,
    },
    scales: {
      x: { reverse: rtl, ticks: { color: text2, maxTicksLimit: 12, font: { size: 10 } }, grid: { display: false } },
      y: { beginAtZero: true, ticks: { color: text2, precision: 0, font: { size: 10 } }, grid: { color: gridLine } },
    },
    interaction: { intersect: false, mode: 'index' },
  });

  new Chart(document.getElementById('engageChart'), {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: @json(__('Downloads')), data: @json($series['downloads']['values']),
          borderColor: primary, backgroundColor: primary + '14', fill: true, tension: .38, pointRadius: 0, borderWidth: 2.5 },
        { label: @json(__('Likes')), data: @json($series['likes']['values']),
          borderColor: danger, backgroundColor: danger + '12', fill: true, tension: .38, pointRadius: 0, borderWidth: 2.5 },
      ],
    },
    options: baseOpts(true),
  });

  new Chart(document.getElementById('contentChart'), {
    type: 'bar',
    data: { labels, datasets: [{ data: @json($series['tilawat']['values']), backgroundColor: primary + 'cc', borderRadius: 4 }] },
    options: baseOpts(false),
  });

  new Chart(document.getElementById('usersChart'), {
    type: 'bar',
    data: { labels, datasets: [{ data: @json($series['users']['values']), backgroundColor: '#60a5facc', borderRadius: 4 }] },
    options: baseOpts(false),
  });
})();
</script>
@endpush
