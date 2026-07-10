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

@if(request('uploaded'))
<div class="alert alert-success" style="margin-bottom:1.4rem"><i class="fas fa-check-circle"></i> {{ __('Tilawa created successfully.') }}</div>
@endif

<form method="GET" class="filter-bar">
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
  <select name="review" class="f-select" onchange="this.form.submit()">
    <option value="">{{ __('Review') }}: {{ __('All') }}</option>
    <option value="pending"  {{ request('review') === 'pending'  ? 'selected' : '' }}>{{ __('In Review') }}</option>
    <option value="approved" {{ request('review') === 'approved' ? 'selected' : '' }}>{{ __('Approved') }}</option>
    <option value="rejected" {{ request('review') === 'rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
  </select>
  <select name="status" class="f-select" onchange="this.form.submit()">
    <option value="">{{ __('Status') }}: {{ __('All') }}</option>
    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>{{ __('Active') }}</option>
    <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>{{ __('Pending') }}</option>
    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
  </select>
  @if(request()->hasAny(['search', 'review', 'status', 'qari']))
  <a href="{{ route('admin.tilawat.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-xmark"></i> {{ __('Clear') }}</a>
  @endif
</form>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr>
      <th>{{ __('Tilawa') }}</th><th>{{ __('Qari') }}</th><th>{{ __('By') }}</th><th>{{ __('Status') }}</th>
      <th>{{ __('Review') }}</th><th>{{ __('Featured') }}</th><th>{{ __('Stats') }}</th><th>{{ __('Actions') }}</th>
    </tr></thead>
    <tbody>
      @forelse($tilawat as $t)
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:.72rem">
            <img src="{{ $t->cover_url }}" style="width:36px;height:36px;border-radius:7px;object-fit:cover;flex-shrink:0" alt="">
            <div>
              <div style="font-weight:600;font-size:.88rem">{{ $t->title }}</div>
              <div style="font-size:.72rem;color:var(--text3);font-family:monospace">{{ $t->formatted_duration }}</div>
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
            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:1.05rem;color:{{ $t->is_featured?'var(--gold)':'var(--text3)' }}" title="{{ __('Toggle featured') }}">
              <i class="fas fa-star"></i>
            </button>
          </form>
          @else
          <i class="fas fa-star" style="font-size:1.05rem;color:{{ $t->is_featured?'var(--gold)':'var(--text3)' }}"></i>
          @endrole
        </td>
        <td>
          <div style="font-size:.76rem;color:var(--text2);display:flex;flex-direction:column;gap:.12rem">
            <span><i class="fas fa-heart" style="color:var(--gold)"></i> {{ number_format($t->likes_count) }}</span>
            <span><i class="fas fa-download"></i> {{ number_format($t->downloads_count) }}</span>
          </div>
        </td>
        <td>
          <div style="display:flex;gap:.18rem;align-items:center">
            <button class="btn-icon" title="{{ __('Play') }}"
              onclick="playTilawa({{ $t->id }},'{{ $t->audio_url }}',{{ json_encode($t->title) }},{{ json_encode($t->qari->name) }},'{{ $t->cover_url }}',{{ $t->duration }})">
              <i class="fas fa-play"></i>
            </button>
            @if($t->review_status === 'rejected')
            <a href="{{ route('admin.tilawat.edit',$t) }}" class="btn btn-primary btn-xs" title="{{ __('Fix & Resubmit') }}"><i class="fas fa-paper-plane"></i> {{ __('Fix') }}</a>
            @else
            <a href="{{ route('admin.tilawat.edit',$t) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="fas fa-pen-to-square"></i></a>
            @endif
            <form method="POST" action="{{ route('admin.tilawat.destroy',$t) }}" onsubmit="return confirm(@json(__('Delete this tilawa?')))">
              @csrf @method('DELETE')
              <button type="submit" class="btn-icon" style="color:var(--red)" title="{{ __('Delete') }}"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="8" style="text-align:center;padding:3rem;color:var(--text2)">
        <i class="fas fa-music" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.4"></i> {{ __('No tilawat match your filters.') }}
      </td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div>{{ $tilawat->links('vendor.pagination.custom') }}</div>

<style>
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
});
</script>
@endpush
