@extends('layouts.admin')
@section('title', __('Shorts'))
@section('page-title', __('Shorts'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Shorts') }}@endsection
@section('topbar-actions')
<a href="{{ route('admin.shorts.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('Add Short') }}</a>
@endsection
@section('content')

<div x-data="{ preview: null }">

<form method="GET" class="filter-bar">
  <div class="f-search">
    <i class="fas fa-magnifying-glass"></i>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search shorts…') }}">
  </div>
  <select name="type" class="f-select" onchange="this.form.submit()">
    <option value="">{{ __('Type') }}: {{ __('All') }}</option>
    <option value="video" {{ request('type')==='video' ? 'selected':'' }}>{{ __('Video') }}</option>
    <option value="audio" {{ request('type')==='audio' ? 'selected':'' }}>{{ __('Audio') }}</option>
  </select>
  <select name="status" class="f-select" onchange="this.form.submit()">
    <option value="">{{ __('Status') }}: {{ __('All') }}</option>
    <option value="active"   {{ request('status')==='active'   ? 'selected':'' }}>{{ __('Active') }}</option>
    <option value="inactive" {{ request('status')==='inactive' ? 'selected':'' }}>{{ __('Inactive') }}</option>
  </select>
  @if(request('search') || request('status') || request('type'))
  <a href="{{ route('admin.shorts.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-xmark"></i> {{ __('Clear') }}</a>
  @endif
</form>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr>
      <th>{{ __('Short') }}</th><th>{{ __('Type') }}</th><th>{{ __('Order') }}</th>
      <th>{{ __('Status') }}</th><th>{{ __('Added') }}</th><th>{{ __('Actions') }}</th>
    </tr></thead>
    <tbody>
      @forelse($shorts as $s)
      <tr>
        <td>
          @php($canPreview = $s->media_path && $s->type === 'video')
          <div style="display:flex;align-items:center;gap:.72rem">
            <span @if($canPreview) role="button" tabindex="0" @click="preview='{{ $s->media_url }}'" title="{{ __('Preview') }}" @endif
                  style="position:relative;width:36px;height:48px;border-radius:8px;background:var(--surface2,var(--surface));display:grid;place-items:center;border:1px solid var(--border2);flex-shrink:0;overflow:hidden;{{ $canPreview ? 'cursor:pointer' : '' }}">
              @if($s->poster_url)
              <img src="{{ $s->poster_url }}" style="width:100%;height:100%;object-fit:cover" alt="">
              @elseif($canPreview)
              <video src="{{ $s->media_url }}#t=0.1" muted preload="metadata" style="width:100%;height:100%;object-fit:cover"></video>
              @else
              <i class="fas {{ $s->type === 'video' ? 'fa-clapperboard' : 'fa-music' }}" style="color:var(--gold)"></i>
              @endif
              @if($canPreview)
              <span style="position:absolute;inset:0;display:grid;place-items:center;background:rgba(0,0,0,.28);color:#fff;font-size:.7rem"><i class="fas fa-play"></i></span>
              @endif
            </span>
            <div>
              <div style="font-weight:600;font-size:.88rem">{{ $s->title }}</div>
              @if($s->qari)
              <div style="font-size:.76rem;color:var(--text2)"><i class="fas fa-microphone-lines"></i> {{ $s->qari->name }}</div>
              @endif
            </div>
          </div>
        </td>
        <td>
          <span class="badge badge-muted">
            <i class="fas {{ $s->type === 'video' ? 'fa-video' : 'fa-volume-high' }}"></i>
            {{ $s->type === 'video' ? __('Video') : __('Audio') }}
          </span>
        </td>
        <td><span class="badge badge-muted">{{ $s->sort_order }}</span></td>
        <td>
          @if(in_array($s->import_status, ['pending','processing'], true))
          <span class="badge badge-amber" title="{{ __('Downloading from TikTok…') }}">
            <i class="fas fa-spinner fa-spin"></i> {{ __('Downloading') }}
          </span>
          @elseif($s->import_status === 'failed')
          <span class="badge badge-red" title="{{ $s->import_error }}">
            <i class="fas fa-triangle-exclamation"></i> {{ __('Failed') }}
          </span>
          @else
          <span class="badge {{ $s->status === 'active' ? 'badge-green' : 'badge-muted' }}">
            {{ $s->status === 'active' ? __('Active') : __('Inactive') }}
          </span>
          @endif
        </td>
        <td style="font-size:.8rem;color:var(--text2)">{{ $s->created_at->format('d M Y') }}</td>
        <td>
          <div style="display:flex;gap:.18rem">
            <a href="{{ route('admin.shorts.edit',$s) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="fas fa-pen-to-square"></i></a>
            <form method="POST" action="{{ route('admin.shorts.destroy',$s) }}" onsubmit="return confirm(@json(__('Delete this short?')))">
              @csrf @method('DELETE')
              <button type="submit" class="btn-icon" style="color:var(--red)" title="{{ __('Delete') }}"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text2)">
        <i class="fas fa-clapperboard" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.4"></i> {{ __('No shorts found.') }}
      </td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div>{{ $shorts->links('vendor.pagination.custom') }}</div>

<div x-show="preview" x-cloak @click.self="preview=null" @keydown.escape.window="preview=null"
     style="position:fixed;inset:0;z-index:60;background:rgba(0,0,0,.75);display:grid;place-items:center;padding:1.5rem">
  <div style="position:relative;width:100%;max-width:340px">
    <button type="button" @click="preview=null" title="{{ __('Close') }}"
            style="position:absolute;top:-2.6rem;inset-inline-end:0;background:none;border:none;color:#fff;font-size:1.4rem;cursor:pointer"><i class="fas fa-xmark"></i></button>
    <template x-if="preview">
      <video :src="preview" controls autoplay playsinline
             style="width:100%;aspect-ratio:9/16;border-radius:12px;background:#000;border:1px solid var(--border2)"></video>
    </template>
  </div>
</div>

</div>
@endsection
