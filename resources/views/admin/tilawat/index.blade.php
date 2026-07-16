@extends('layouts.admin')
@section('title', __('Tilawat'))
@section('page-title', __('Tilawat'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Tilawat') }}@endsection
@role('creator')
@section('topbar-actions')
<a href="{{ route('admin.upload') }}" class="btn btn-primary btn-sm"><i class="fas fa-cloud-arrow-up"></i> {{ __('Upload Tilawat') }}</a>
@endsection
@endrole
@section('content')

@php
  $isAdmin   = auth()->user()->hasRole('admin');
  $keep      = request()->only(['search', 'qari', 'sort']);
  $activeTab = request('tab') === 'qaris' ? 'qaris'
             : (request('featured') ? 'featured'
             : (request('review') === 'pending' ? 'review'
             : (request('review') === 'rejected' ? 'rejected'
             : (request('status') === 'active' ? 'active' : 'all'))));
  $qariView    = request('tab') === 'qaris';
  $currentQari = ($qariView && request('qari')) ? collect($qaris)->firstWhere('id', (int) request('qari')) : null;
@endphp

@if(request('uploaded'))
<div class="alert alert-success" style="margin-bottom:1.4rem"><i class="fas fa-check-circle"></i> {{ __('Tilawa created successfully.') }}</div>
@endif

{{-- ===================== STATS ===================== --}}
<div class="kpi-grid tw-kpis">
  <div class="kpi-card tw-kpi tw-kpi-blue">
    <div class="kpi-top">
      <div class="kpi-icon"><i class="fas fa-music"></i></div>
      @if($stats['featured'])
      <span class="kpi-chip"><i class="fas fa-star" style="color:var(--gold)"></i> {{ number_format($stats['featured']) }} {{ __('Featured') }}</span>
      @endif
    </div>
    <div class="kpi-val">{{ number_format($stats['total']) }}</div>
    <div class="kpi-lbl">{{ __('Tilawat') }} · {{ number_format($stats['active']) }} {{ __('active') }}</div>
  </div>

  <a href="{{ route('admin.tilawat.index', $keep + ['review' => 'pending']) }}" class="kpi-card tw-kpi tw-kpi-amber">
    <div class="kpi-top">
      <div class="kpi-icon tw-icon-amber"><i class="fas fa-clipboard-check"></i></div>
      <i class="fas fa-arrow-right tw-kpi-go flip-rtl"></i>
    </div>
    <div class="kpi-val">{{ number_format($stats['in_review']) }}</div>
    <div class="kpi-lbl">{{ __('Awaiting review') }}</div>
  </a>

  <a href="{{ route('admin.tilawat.index', $keep + ['review' => 'rejected']) }}" class="kpi-card tw-kpi tw-kpi-red">
    <div class="kpi-top">
      <div class="kpi-icon tw-icon-red"><i class="fas fa-circle-xmark"></i></div>
      <i class="fas fa-arrow-right tw-kpi-go flip-rtl"></i>
    </div>
    <div class="kpi-val">{{ number_format($stats['rejected']) }}</div>
    <div class="kpi-lbl">{{ __('Rejected') }}</div>
  </a>

  <div class="kpi-card tw-kpi tw-kpi-green">
    <div class="kpi-top"><div class="kpi-icon tw-icon-green"><i class="fas fa-chart-simple"></i></div></div>
    <div class="kpi-val">{{ number_format($stats['plays']) }}</div>
    <div class="kpi-lbl"><i class="fas fa-heart" style="color:#e0626f"></i> {{ number_format($stats['likes']) }} · <i class="fas fa-download" style="color:var(--text3)"></i> {{ number_format($stats['downloads']) }} {{ __('Downloads') }}</div>
  </div>
</div>

<div x-data="tilawatManager({ ids: @js($tilawat->pluck('id')->map(fn ($i) => (string) $i)->values()) })">

  {{-- ===================== QUICK TABS ===================== --}}
  <div class="tw-tabs">
    <a href="{{ route('admin.tilawat.index', $keep) }}" class="tw-tab {{ $activeTab === 'all' ? 'is-active' : '' }}">
      {{ __('All') }} <span class="tw-count">{{ number_format($stats['total']) }}</span>
    </a>
    <a href="{{ route('admin.tilawat.index', $keep + ['status' => 'active']) }}" class="tw-tab {{ $activeTab === 'active' ? 'is-active' : '' }}">
      {{ __('Active') }} <span class="tw-count">{{ number_format($stats['active']) }}</span>
    </a>
    <a href="{{ route('admin.tilawat.index', $keep + ['review' => 'pending']) }}" class="tw-tab {{ $activeTab === 'review' ? 'is-active' : '' }}">
      {{ __('In Review') }} <span class="tw-count tw-count-amber">{{ number_format($stats['in_review']) }}</span>
    </a>
    <a href="{{ route('admin.tilawat.index', $keep + ['review' => 'rejected']) }}" class="tw-tab {{ $activeTab === 'rejected' ? 'is-active' : '' }}">
      {{ __('Rejected') }} <span class="tw-count tw-count-red">{{ number_format($stats['rejected']) }}</span>
    </a>
    <a href="{{ route('admin.tilawat.index', $keep + ['featured' => 1]) }}" class="tw-tab {{ $activeTab === 'featured' ? 'is-active' : '' }}">
      {{ __('Featured') }} <span class="tw-count">{{ number_format($stats['featured']) }}</span>
    </a>
    <a href="{{ route('admin.tilawat.index', ['tab' => 'qaris'] + request()->only('search')) }}" class="tw-tab {{ $activeTab === 'qaris' ? 'is-active' : '' }}">
      <i class="fas fa-microphone-lines"></i> {{ __('By Qari') }} <span class="tw-count">{{ number_format($stats['qaris']) }}</span>
    </a>
  </div>

  @if($showQariGrid)
  {{-- ===================== QARI GRID ===================== --}}
  <div class="tw-toolbar">
    <form method="GET" class="filter-bar">
      <input type="hidden" name="tab" value="qaris">
      <div class="f-search">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search qari…') }}">
      </div>
      @if(request('search'))
      <a href="{{ route('admin.tilawat.index', ['tab' => 'qaris']) }}" class="btn btn-ghost btn-sm"><i class="fas fa-xmark"></i> {{ __('Clear') }}</a>
      @endif
    </form>
  </div>

  <div class="qari-grid">
    @forelse($qariGrid as $q)
    <a href="{{ route('admin.tilawat.index', ['tab' => 'qaris', 'qari' => $q->id]) }}" class="qari-card">
      <img src="{{ $q->image_url }}" alt="" class="qari-card-img">
      <div class="qari-card-name">{{ $q->name }}</div>
      <div class="qari-card-metrics">
        <span title="{{ __('Tilawat') }}"><i class="fas fa-music"></i> {{ number_format($q->tilawat_count) }}</span>
        <span title="{{ __('Plays') }}"><i class="fas fa-play"></i> {{ number_format((int) $q->plays_sum) }}</span>
      </div>
      <span class="qari-card-go"><i class="fas fa-arrow-left flip-rtl"></i></span>
    </a>
    @empty
    <div class="qari-grid-empty">
      <i class="fas fa-microphone-lines"></i>
      <p>{{ __('No qaris found.') }}</p>
    </div>
    @endforelse
  </div>
  @else

  @if($qariView)
  {{-- ===================== QARI DETAIL HEADER ===================== --}}
  <div class="qari-back">
    <a href="{{ route('admin.tilawat.index', ['tab' => 'qaris']) }}" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-right flip-rtl"></i> {{ __('All Qaris') }}</a>
    @if($currentQari)
    <div class="qari-back-info">
      <img src="{{ $currentQari['image'] }}" alt="">
      <span>{{ $currentQari['name'] }}</span>
    </div>
    @endif
  </div>
  @endif

  {{-- ===================== TOOLBAR ===================== --}}
  <div class="tw-toolbar">
    <form method="GET" class="filter-bar">
      @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
      @if(request('review'))<input type="hidden" name="review" value="{{ request('review') }}">@endif
      @if(request('featured'))<input type="hidden" name="featured" value="1">@endif
      @if(request('tab'))<input type="hidden" name="tab" value="{{ request('tab') }}">@endif

      <div class="f-search">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search tilawat…') }}">
      </div>

      <div class="qari-filter" x-data="qariFilter()" @click.outside="open = false" @keydown.escape="open = false">
        <button type="button" class="f-select qf-toggle" @click="toggle()" :class="{ 'qf-active': value, 'qf-open': open }">
          <template x-if="selected">
            <span class="qf-current"><img :src="selected.image" alt=""><span x-text="selected.name"></span></span>
          </template>
          <template x-if="!selected">
            <span class="qf-placeholder"><i class="fas fa-microphone-lines"></i> {{ __('All Qaris') }}</span>
          </template>
          <i class="fas fa-chevron-down qf-caret"></i>
        </button>
        <div class="qf-panel" x-show="open" x-cloak x-transition.origin.top>
          <div class="qf-search">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" x-model="q" x-ref="search" placeholder="{{ __('Search qari…') }}">
          </div>
          <div class="qf-list">
            <button type="button" class="qf-opt qf-opt-all" @click="choose(null)" :class="{ 'qf-sel': !value }">
              <i class="fas fa-layer-group"></i> {{ __('All Qaris') }}
            </button>
            <template x-for="qari in filtered" :key="qari.id">
              <button type="button" class="qf-opt" @click="choose(qari)" :class="{ 'qf-sel': value == qari.id }">
                <img :src="qari.image" alt=""><span x-text="qari.name"></span>
              </button>
            </template>
            <div class="qf-empty" x-show="!filtered.length">{{ __('No qari found.') }}</div>
          </div>
        </div>
        <input type="hidden" name="qari" :value="value">
      </div>

      <select name="sort" class="f-select" onchange="this.form.submit()">
        <option value="">{{ __('Newest') }}</option>
        <option value="oldest"    {{ request('sort') === 'oldest'    ? 'selected' : '' }}>{{ __('Oldest') }}</option>
        <option value="plays"     {{ request('sort') === 'plays'     ? 'selected' : '' }}>{{ __('Most played') }}</option>
        <option value="downloads" {{ request('sort') === 'downloads' ? 'selected' : '' }}>{{ __('Most downloaded') }}</option>
        <option value="likes"     {{ request('sort') === 'likes'     ? 'selected' : '' }}>{{ __('Most liked') }}</option>
      </select>

      @if(request()->hasAny(['search', 'review', 'status', 'qari', 'featured', 'sort']))
      <a href="{{ route('admin.tilawat.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-xmark"></i> {{ __('Clear') }}</a>
      @endif
    </form>

    <div class="tw-view">
      <button type="button" class="tw-view-btn" :class="{ 'is-active': view === 'table' }" @click="setView('table')" title="{{ __('Table view') }}"><i class="fas fa-list"></i></button>
      <button type="button" class="tw-view-btn" :class="{ 'is-active': view === 'grid' }" @click="setView('grid')" title="{{ __('Grid view') }}"><i class="fas fa-table-cells-large"></i></button>
    </div>
  </div>

  {{-- ===================== BULK BAR (admin) ===================== --}}
  @if($isAdmin)
  <div class="bulkbar" x-show="selected.length" x-cloak x-transition>
    <span class="bulkbar-count"><i class="fas fa-check-double"></i> <span x-text="selected.length"></span> {{ __('selected') }}</span>
    <div class="bulkbar-actions">
      <button type="button" class="btn btn-success btn-sm" @click="bulk('approve')"><i class="fas fa-circle-check"></i> {{ __('Approve') }}</button>
      <button type="button" class="btn btn-ghost btn-sm" @click="bulk('feature')"><i class="fas fa-star"></i> {{ __('Feature') }}</button>
      <button type="button" class="btn btn-ghost btn-sm" @click="bulk('unfeature')"><i class="far fa-star"></i> {{ __('Unfeature') }}</button>
      <button type="button" class="btn btn-ghost btn-sm" @click="bulk('deactivate')"><i class="fas fa-eye-slash"></i> {{ __('Deactivate') }}</button>
      <button type="button" class="btn btn-danger btn-sm" @click="bulk('delete')"><i class="fas fa-trash"></i> {{ __('Delete') }}</button>
    </div>
    <button type="button" class="bulkbar-clear" @click="clearSel()"><i class="fas fa-xmark"></i> {{ __('Clear') }}</button>
  </div>

  <form method="POST" action="{{ route('admin.tilawat.bulk') }}" x-ref="bulkForm" class="hidden">
    @csrf @method('PUT')
    <input type="hidden" name="action" :value="submitAction">
    <template x-for="id in selected" :key="id">
      <input type="hidden" name="ids[]" :value="id">
    </template>
  </form>
  @endif

  {{-- ===================== TABLE VIEW ===================== --}}
  <div class="tbl-wrap" x-show="view === 'table'">
    <table class="tbl">
      <thead><tr>
        @if($isAdmin)
        <th style="width:38px"><input type="checkbox" class="tw-cb" @change="toggleAll($event)" :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="{{ __('Select all') }}"></th>
        @endif
        <th>{{ __('Tilawa') }}</th><th>{{ __('Qari') }}</th><th>{{ __('By') }}</th><th>{{ __('Status') }}</th>
        <th>{{ __('Review') }}</th><th>{{ __('Featured') }}</th><th>{{ __('Stats') }}</th><th>{{ __('Actions') }}</th>
      </tr></thead>
      <tbody>
        @forelse($tilawat as $t)
        @php($meta = collect([$t->surah_label, ($t->ayah_from && $t->ayah_to) ? $t->ayah_from.'–'.$t->ayah_to : null, $t->formatted_duration])->filter()->implode(' · '))
        <tr :class="{ 'tw-row-sel': selected.includes('{{ $t->id }}') }">
          @if($isAdmin)
          <td><input type="checkbox" class="tw-cb" x-model="selected" value="{{ $t->id }}" aria-label="{{ __('Select') }}"></td>
          @endif
          <td>
            <div style="display:flex;align-items:center;gap:.72rem">
              <img src="{{ $t->cover_url }}" class="tw-cover" alt="">
              <div>
                <div class="tw-title">{{ $t->title }}</div>
                <div class="tw-meta">{{ $meta }}</div>
              </div>
            </div>
          </td>
          <td style="color:var(--gold);font-size:.85rem">{{ $t->qari->name }}</td>
          <td style="font-size:.8rem;color:var(--text2)">{{ $t->uploader?->name ?? '—' }}</td>
          <td>
            @role('admin')
            <form method="POST" action="{{ route('admin.tilawat.quick-update',$t) }}" style="display:inline">
              @csrf @method('PUT')
              <select name="status" onchange="this.form.submit()" class="f-select f-select-sm">
                <option value="active"   {{ $t->status==='active'  ?'selected':'' }}>{{ __('Active') }}</option>
                <option value="pending"  {{ $t->status==='pending' ?'selected':'' }}>{{ __('Pending') }}</option>
                <option value="inactive" {{ $t->status==='inactive'?'selected':'' }}>{{ __('Inactive') }}</option>
              </select>
            </form>
            @else
            <span class="badge {{ $t->status==='active'?'badge-green':($t->status==='pending'?'badge-amber':'badge-muted') }}">{{ __(ucfirst($t->status)) }}</span>
            @endrole
          </td>
          <td>
            @php($reviewBadge = ['pending'=>'badge-amber','approved'=>'badge-green','rejected'=>'badge-red'][$t->review_status] ?? 'badge-muted')
            @php($reviewLabel = ['pending'=>__('In Review'),'approved'=>__('Approved'),'rejected'=>__('Rejected')][$t->review_status] ?? $t->review_status)
            <span class="badge {{ $reviewBadge }}">{{ $reviewLabel }}</span>
            @if($t->review_status === 'rejected' && $t->rejection_note)
            <div style="font-size:.74rem;color:#e86060;margin-top:.35rem;max-width:220px" title="{{ $t->rejection_note }}">
              <i class="fas fa-comment-dots"></i> {{ Str::limit($t->rejection_note, 60) }}
            </div>
            @endif
          </td>
          <td>
            @role('admin')
            <form method="POST" action="{{ route('admin.tilawat.quick-update',$t) }}">
              @csrf @method('PUT')
              <input type="hidden" name="is_featured" value="{{ $t->is_featured ? '0':'1' }}">
              <button type="submit" class="tw-star" style="color:{{ $t->is_featured?'var(--gold)':'var(--text3)' }}" title="{{ __('Toggle featured') }}">
                <i class="fas fa-star"></i>
              </button>
            </form>
            @else
            <i class="fas fa-star" style="font-size:1.05rem;color:{{ $t->is_featured?'var(--gold)':'var(--text3)' }}"></i>
            @endrole
          </td>
          <td>
            <div class="tw-metrics">
              <span title="{{ __('Plays') }}"><i class="fas fa-play"></i>{{ number_format($t->plays_count) }}</span>
              <span title="{{ __('Likes') }}"><i class="fas fa-heart"></i>{{ number_format($t->likes_count) }}</span>
              <span title="{{ __('Downloads') }}"><i class="fas fa-download"></i>{{ number_format($t->downloads_count) }}</span>
            </div>
          </td>
          <td>
            <div style="display:flex;gap:.18rem;align-items:center">
              <button type="button" class="btn-icon" title="{{ __('Play') }}"
                onclick="playTilawa({{ $t->id }},'{{ $t->audio_url }}',{{ json_encode($t->title) }},{{ json_encode($t->qari->name) }},'{{ $t->cover_url }}',{{ $t->duration }})">
                <i class="fas fa-play"></i>
              </button>
              @if($t->review_status === 'rejected')
              <a href="{{ route('admin.tilawat.edit',$t) }}" class="btn btn-primary btn-xs" title="{{ __('Fix & Resubmit') }}"><i class="fas fa-paper-plane"></i> {{ __('Fix') }}</a>
              @else
              <a href="{{ route('admin.tilawat.edit',$t) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="fas fa-pen-to-square"></i></a>
              @endif
              <button type="button" class="btn-icon" style="color:var(--red)" title="{{ __('Delete') }}" @click="askRowDelete('{{ $t->id }}', @js($t->title))"><i class="fas fa-trash"></i></button>
              <form id="del-{{ $t->id }}" method="POST" action="{{ route('admin.tilawat.destroy',$t) }}" class="hidden">@csrf @method('DELETE')</form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="{{ $isAdmin ? 9 : 8 }}" style="text-align:center;padding:3rem;color:var(--text2)">
          <i class="fas fa-music" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.4"></i> {{ __('No tilawat match your filters.') }}
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ===================== GRID VIEW ===================== --}}
  <div class="tw-grid" x-show="view === 'grid'" x-cloak>
    @forelse($tilawat as $t)
    @php($meta = collect([$t->surah_label, ($t->ayah_from && $t->ayah_to) ? $t->ayah_from.'–'.$t->ayah_to : null, $t->formatted_duration])->filter()->implode(' · '))
    <div class="tw-card" :class="{ 'is-sel': selected.includes('{{ $t->id }}') }">
      @if($isAdmin)
      <input type="checkbox" class="tw-cb tw-card-cb" x-model="selected" value="{{ $t->id }}" aria-label="{{ __('Select') }}">
      @endif
      <div class="tw-card-top">
        <div class="tw-card-cover">
          <img src="{{ $t->cover_url }}" alt="">
          <button type="button" class="tw-card-play" title="{{ __('Play') }}"
            onclick="playTilawa({{ $t->id }},'{{ $t->audio_url }}',{{ json_encode($t->title) }},{{ json_encode($t->qari->name) }},'{{ $t->cover_url }}',{{ $t->duration }})">
            <i class="fas fa-play"></i>
          </button>
        </div>
        <div style="min-width:0">
          <div class="tw-title" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $t->title }}</div>
          <div style="color:var(--gold);font-size:.78rem;margin-top:.1rem">{{ $t->qari->name }}</div>
          <div class="tw-meta">{{ $meta }}</div>
        </div>
      </div>

      <div class="tw-card-badges">
        <span class="badge {{ $t->status==='active'?'badge-green':($t->status==='pending'?'badge-amber':'badge-muted') }}">{{ __(ucfirst($t->status)) }}</span>
        @php($reviewBadge = ['pending'=>'badge-amber','approved'=>'badge-green','rejected'=>'badge-red'][$t->review_status] ?? 'badge-muted')
        @php($reviewLabel = ['pending'=>__('In Review'),'approved'=>__('Approved'),'rejected'=>__('Rejected')][$t->review_status] ?? $t->review_status)
        <span class="badge {{ $reviewBadge }}">{{ $reviewLabel }}</span>
        @if($t->is_featured)<span class="badge badge-gold"><i class="fas fa-star"></i> {{ __('Featured') }}</span>@endif
      </div>

      <div class="tw-card-foot">
        <div class="tw-metrics">
          <span title="{{ __('Plays') }}"><i class="fas fa-play"></i>{{ number_format($t->plays_count) }}</span>
          <span title="{{ __('Likes') }}"><i class="fas fa-heart"></i>{{ number_format($t->likes_count) }}</span>
          <span title="{{ __('Downloads') }}"><i class="fas fa-download"></i>{{ number_format($t->downloads_count) }}</span>
        </div>
        <div style="display:flex;gap:.15rem">
          @if($t->review_status === 'rejected')
          <a href="{{ route('admin.tilawat.edit',$t) }}" class="btn btn-primary btn-xs" title="{{ __('Fix & Resubmit') }}"><i class="fas fa-paper-plane"></i></a>
          @else
          <a href="{{ route('admin.tilawat.edit',$t) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="fas fa-pen-to-square"></i></a>
          @endif
          <button type="button" class="btn-icon" style="color:var(--red)" title="{{ __('Delete') }}" @click="askRowDelete('{{ $t->id }}', @js($t->title))"><i class="fas fa-trash"></i></button>
        </div>
      </div>
    </div>
    @empty
    <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text2)">
      <i class="fas fa-music" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.4"></i> {{ __('No tilawat match your filters.') }}
    </div>
    @endforelse
  </div>

  <div style="margin-top:1.2rem">{{ $tilawat->links('vendor.pagination.custom') }}</div>
  @endif

  {{-- ===================== DELETE CONFIRM MODAL ===================== --}}
  <div class="modal-backdrop" x-show="confirmOpen" x-cloak @click.self="confirmOpen = false" @keydown.escape.window="confirmOpen = false" style="display:none">
    <div class="modal" style="max-width:420px">
      <div class="modal-title"><i class="fas fa-trash" style="color:var(--red)"></i> {{ __('Confirm deletion') }}</div>
      <p style="color:var(--text2);font-size:.9rem;margin:0 0 1.4rem">
        <span x-show="confirmMode === 'single'">{{ __('Delete') }} “<span x-text="confirmTitle"></span>”?</span>
        <span x-show="confirmMode === 'bulk'" x-cloak><span x-text="selected.length"></span> {{ __('selected tilawat will be permanently deleted.') }}</span>
      </p>
      <div style="display:flex;gap:.6rem">
        <button type="button" @click="confirmOpen = false" class="btn btn-ghost" style="flex:1;justify-content:center">{{ __('Cancel') }}</button>
        <button type="button" @click="confirmYes()" class="btn btn-danger" style="flex:1;justify-content:center"><i class="fas fa-trash"></i> {{ __('Delete') }}</button>
      </div>
    </div>
  </div>

</div>

<style>
/* ── Qari filter ── */
.qari-filter { position: relative; }
.qf-toggle { display: inline-flex; align-items: center; gap: .55rem; min-width: 190px; max-width: 240px; text-align: start; cursor: pointer; }
.qf-toggle.qf-active { border-color: var(--gold); }
.qf-current { display: inline-flex; align-items: center; gap: .5rem; min-width: 0; flex: 1; }
.qf-current img { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.qf-current span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.qf-placeholder { flex: 1; display: inline-flex; align-items: center; gap: .45rem; color: var(--text2); }
.qf-placeholder i { color: var(--text3); }
.qf-caret { font-size: .68rem; color: var(--text3); flex-shrink: 0; transition: transform .15s; }
.qf-toggle.qf-open .qf-caret { transform: rotate(180deg); }
.qf-panel { position: absolute; z-index: 40; top: calc(100% + .35rem); inset-inline-start: 0; width: 270px; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,.14); padding: .55rem; }
.qf-search { display: flex; align-items: center; gap: .5rem; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; padding: .45rem .6rem; margin-bottom: .5rem; }
.qf-search i { color: var(--text3); font-size: .8rem; }
.qf-search input { flex: 1; min-width: 0; background: none; border: none; outline: none; color: var(--text); font-size: .85rem; font-family: inherit; }
.qf-list { max-height: 260px; overflow-y: auto; display: flex; flex-direction: column; gap: .1rem; }
.qf-opt { display: flex; align-items: center; gap: .55rem; width: 100%; text-align: start; padding: .45rem .55rem; border: none; background: none; border-radius: 8px; cursor: pointer; color: var(--text); font-size: .85rem; font-family: inherit; }
.qf-opt:hover { background: var(--hover); }
.qf-opt img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.qf-opt span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.qf-opt.qf-sel { background: var(--gold-faint); color: var(--gold); font-weight: 600; }
.qf-opt-all i { width: 28px; text-align: center; color: var(--text3); }
.qf-opt-all.qf-sel i { color: var(--gold); }
.qf-empty { text-align: center; color: var(--text3); font-size: .82rem; padding: .8rem; }

/* ── KPI variants ── */
.tw-kpis { margin-bottom: 1.6rem; }
.tw-kpi { position: relative; overflow: hidden; }
.tw-kpi::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 3px; background: var(--accent, var(--gold)); }
.tw-kpi .kpi-val { font-size: 1.9rem; letter-spacing: -.03em; }
.tw-kpi-blue  { --accent: #2563eb; }
.tw-kpi-amber { --accent: #d97706; }
.tw-kpi-red   { --accent: #dc2626; }
.tw-kpi-green { --accent: #059669; }
.tw-kpis a.kpi-card { text-decoration: none; color: inherit; cursor: pointer; }
.tw-kpis a.kpi-card:hover { border-color: var(--accent); box-shadow: 0 6px 18px rgba(0,0,0,.08); transform: translateY(-2px); }
.tw-kpi-go { margin-inline-start: auto; color: var(--text3); font-size: .8rem; opacity: 0; transform: translateX(-4px); transition: .18s; }
.tw-kpis a.kpi-card:hover .tw-kpi-go { opacity: 1; transform: translateX(0); color: var(--accent); }
.tw-icon-amber { background: #fffbeb; border-color: #fde68a; color: #d97706; }
.tw-icon-red   { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
.tw-icon-green { background: #ecfdf5; border-color: #a7f3d0; color: #059669; }

/* ── Quick tabs ── */
.tw-tabs { display: flex; gap: .25rem; flex-wrap: wrap; border-bottom: 1px solid var(--border); margin-bottom: 1.2rem; }
.tw-tab { display: inline-flex; align-items: center; gap: .5rem; padding: .6rem .9rem; font-size: .84rem; font-weight: 600; color: var(--text2); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; border-radius: 7px 7px 0 0; transition: .15s; }
.tw-tab:hover { color: var(--text1); background: var(--hover); }
.tw-tab.is-active { color: var(--gold); border-bottom-color: var(--gold); }
.tw-count { display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 19px; padding: 0 .38rem; border-radius: 999px; background: var(--surface3); color: var(--text2); font-size: .7rem; font-weight: 700; }
.tw-tab.is-active .tw-count { background: var(--gold-faint); color: var(--gold); }
.tw-count-amber { background: #fffbeb; color: #b45309; }
.tw-count-red   { background: #fef2f2; color: #b91c1c; }

/* ── Toolbar / view toggle ── */
.tw-toolbar { display: flex; align-items: center; gap: .65rem; flex-wrap: wrap; margin-bottom: 1.15rem; }
.tw-toolbar .filter-bar { margin-bottom: 0; flex: 1; }
.tw-view { display: inline-flex; gap: .2rem; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; padding: .2rem; }
.tw-view-btn { width: 32px; height: 30px; border: 0; background: none; border-radius: 6px; color: var(--text3); cursor: pointer; font-size: .82rem; }
.tw-view-btn:hover { color: var(--text1); }
.tw-view-btn.is-active { background: var(--surface); color: var(--gold); box-shadow: 0 1px 2px rgba(0,0,0,.08); }

/* ── Bulk bar ── */
.bulkbar { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; padding: .6rem .95rem; border-radius: 10px; background: var(--gold-faint); border: 1px solid var(--gold-glow); }
.bulkbar-count { font-size: .85rem; font-weight: 700; color: var(--gold); white-space: nowrap; }
.bulkbar-actions { display: flex; gap: .4rem; flex-wrap: wrap; }
.bulkbar-clear { margin-inline-start: auto; background: none; border: 0; color: var(--text2); cursor: pointer; font-size: .8rem; font-weight: 600; }
.bulkbar-clear:hover { color: var(--text1); }

/* ── Table extras ── */
.tw-cb { width: 16px; height: 16px; accent-color: var(--gold); cursor: pointer; }
.tw-cover { width: 40px; height: 40px; border-radius: 9px; object-fit: cover; flex-shrink: 0; border: 1px solid var(--border); box-shadow: 0 1px 2px rgba(0,0,0,.06); }
.tw-title { font-weight: 600; font-size: .88rem; color: var(--text1); }
.tw-meta { font-size: .72rem; color: var(--text3); margin-top: .15rem; }
.tw-metrics { display: flex; gap: .4rem; flex-wrap: wrap; font-size: .74rem; color: var(--text2); }
.tw-metrics span { display: inline-flex; align-items: center; gap: .28rem; background: var(--surface2); border: 1px solid var(--border); border-radius: 6px; padding: .12rem .4rem; }
.tw-metrics i { color: var(--text3); font-size: .68rem; }
.tw-star { background: none; border: 0; cursor: pointer; font-size: 1.05rem; transition: transform .12s; }
.tw-star:hover { transform: scale(1.18); }
.tbl tbody tr.tw-row-sel td { background: var(--gold-faint); box-shadow: inset 3px 0 0 var(--gold); }
.tbl tbody tr { transition: background .12s; }

/* ── Grid view ── */
.tw-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(255px, 1fr)); gap: 1.05rem; }
.tw-card { position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: .9rem; box-shadow: 0 1px 3px rgba(0,0,0,.04); transition: transform .16s, box-shadow .16s, border-color .16s; }
.tw-card:hover { border-color: var(--border2); box-shadow: 0 8px 22px rgba(0,0,0,.09); transform: translateY(-3px); }
.tw-card.is-sel { border-color: var(--gold); box-shadow: 0 0 0 2px var(--gold-glow); }
.tw-card-top { display: flex; gap: .8rem; align-items: flex-start; }
.tw-card-cover { position: relative; width: 62px; height: 62px; flex-shrink: 0; }
.tw-card-cover img { width: 62px; height: 62px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border); }
.tw-card-play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: linear-gradient(160deg, rgba(37,99,235,.55), rgba(15,23,42,.62)); color: #fff; border: 0; border-radius: 12px; opacity: 0; cursor: pointer; transition: opacity .15s; font-size: 1.05rem; }
.tw-card-cover:hover .tw-card-play { opacity: 1; }
.tw-card-cb { position: absolute; top: .65rem; inset-inline-end: .65rem; z-index: 2; }
.tw-card-badges { display: flex; gap: .35rem; flex-wrap: wrap; margin: .75rem 0 .6rem; }
.tw-card-foot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; border-top: 1px solid var(--border); padding-top: .65rem; }

/* ── Qari grid (By Qari tab) ── */
.qari-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 1.05rem; }
.qari-card { position: relative; display: flex; flex-direction: column; align-items: center; text-align: center; text-decoration: none; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 1.4rem 1rem 1.15rem; box-shadow: 0 1px 3px rgba(0,0,0,.04); transition: transform .16s, box-shadow .16s, border-color .16s; }
.qari-card:hover { border-color: var(--gold); box-shadow: 0 8px 22px rgba(0,0,0,.09); transform: translateY(-3px); }
.qari-card-img { width: 74px; height: 74px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border); box-shadow: 0 2px 6px rgba(0,0,0,.08); }
.qari-card-name { margin-top: .8rem; font-weight: 700; font-size: .92rem; color: var(--text1); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }
.qari-card-metrics { display: flex; gap: .45rem; margin-top: .6rem; }
.qari-card-metrics span { display: inline-flex; align-items: center; gap: .3rem; background: var(--surface2); border: 1px solid var(--border); border-radius: 6px; padding: .16rem .48rem; font-size: .74rem; color: var(--text2); }
.qari-card-metrics i { color: var(--text3); font-size: .68rem; }
.qari-card-go { position: absolute; top: .8rem; inset-inline-end: .85rem; color: var(--text3); font-size: .78rem; opacity: 0; transform: translateX(4px); transition: .18s; }
.qari-card:hover .qari-card-go { opacity: 1; transform: translateX(0); color: var(--gold); }
.qari-grid-empty { grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text2); }
.qari-grid-empty i { font-size: 1.6rem; opacity: .4; display: block; margin-bottom: .5rem; }

/* ── Qari detail header ── */
.qari-back { display: flex; align-items: center; gap: .9rem; flex-wrap: wrap; margin-bottom: 1.15rem; }
.qari-back-info { display: inline-flex; align-items: center; gap: .55rem; font-weight: 700; color: var(--gold); }
.qari-back-info img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border); }

@media (max-width: 640px) {
  .tw-toolbar { align-items: stretch; }
  .tw-toolbar .filter-bar { flex-direction: column; align-items: stretch; }
  .tw-view { align-self: flex-end; }
  .tw-tabs { overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
  .tw-tab { white-space: nowrap; }
}
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('qariFilter', () => ({
    open: false,
    q: '',
    qaris: @json($qaris),
    value: @json((string) request('qari', '')),
    get selected() {
      return this.qaris.find(x => String(x.id) === String(this.value)) || null;
    },
    get filtered() {
      const f = this.q.trim().toLowerCase();
      return f ? this.qaris.filter(x => x.name.toLowerCase().includes(f)) : this.qaris;
    },
    toggle() {
      this.open = !this.open;
      if (this.open) {
        this.q = '';
        this.$nextTick(() => this.$refs.search.focus());
      }
    },
    choose(qari) {
      this.value = qari ? String(qari.id) : '';
      this.open = false;
      this.$nextTick(() => this.$root.closest('form').submit());
    },
  }));

  Alpine.data('tilawatManager', (config) => ({
    view: localStorage.getItem('tilawat_view') === 'grid' ? 'grid' : 'table',
    selected: [],
    allIds: config.ids,
    submitAction: '',
    confirmOpen: false,
    confirmMode: 'bulk',
    confirmTitle: '',
    rowFormId: null,
    get allChecked() {
      return this.allIds.length > 0 && this.selected.length === this.allIds.length;
    },
    get someChecked() {
      return this.selected.length > 0 && !this.allChecked;
    },
    setView(v) {
      this.view = v;
      localStorage.setItem('tilawat_view', v);
    },
    toggleAll(e) {
      this.selected = e.target.checked ? [...this.allIds] : [];
    },
    clearSel() {
      this.selected = [];
    },
    bulk(action) {
      if (!this.selected.length) { return; }
      if (action === 'delete') {
        this.confirmMode = 'bulk';
        this.confirmOpen = true;
        return;
      }
      this.submitAction = action;
      this.$nextTick(() => this.$refs.bulkForm.submit());
    },
    askRowDelete(id, title) {
      this.confirmMode = 'single';
      this.rowFormId = 'del-' + id;
      this.confirmTitle = title;
      this.confirmOpen = true;
    },
    confirmYes() {
      if (this.confirmMode === 'single') {
        document.getElementById(this.rowFormId).submit();
        return;
      }
      this.submitAction = 'delete';
      this.$nextTick(() => this.$refs.bulkForm.submit());
    },
  }));
});
</script>
@endpush
