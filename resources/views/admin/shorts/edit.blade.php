@extends('layouts.admin')
@section('title', __('Edit Short'))
@section('page-title', __('Edit Short'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › <a href="{{ route('admin.shorts.index') }}">{{ __('Shorts') }}</a> › {{ __('Edit') }}@endsection
@section('content')

<div style="max-width:660px" x-data="{ type: '{{ old('type',$short->type) }}' }">
  <form method="POST" action="{{ route('admin.shorts.update',$short) }}">
    @csrf @method('PUT')

    @if($errors->any())
    <div class="alert alert-error" style="margin-bottom:1.25rem">
      @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
    </div>
    @endif

    <div class="admin-form-grid">
      <div class="form-group admin-form-grid" style="grid-column:1/-1">
        <div>
          <label class="form-label"><i class="fas fa-heading"></i> {{ __('Title (Arabic)') }} <span style="color:var(--red)">*</span></label>
          <input type="text" name="title_ar" class="form-control" value="{{ old('title_ar', $short->title_ar) }}" required dir="rtl">
          @error('title_ar')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div>
          <label class="form-label"><i class="fas fa-heading"></i> {{ __('Title (English)') }}</label>
          <input type="text" name="title_en" class="form-control" value="{{ old('title_en', $short->title_en) }}" dir="ltr">
          @error('title_en')<span class="form-error">{{ $message }}</span>@enderror
        </div>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-clapperboard"></i> {{ __('Type') }} <span style="color:var(--red)">*</span></label>
        <select name="type" class="form-control" x-model="type">
          <option value="video" {{ old('type',$short->type)==='video' ? 'selected':'' }}>{{ __('Video') }}</option>
          <option value="audio" {{ old('type',$short->type)==='audio' ? 'selected':'' }}>{{ __('Audio') }}</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-microphone-lines"></i> {{ __('Qari') }}</label>
        <select name="qari_id" class="form-control">
          <option value="">{{ __('No qari') }}</option>
          @foreach($qaris as $q)
          <option value="{{ $q->id }}" {{ old('qari_id', $short->qari_id) == $q->id ? 'selected' : '' }}>{{ $q->name }}</option>
          @endforeach
        </select>
        @error('qari_id')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-sort"></i> {{ __('Sort Order') }}</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $short->sort_order) }}" min="0">
        <span class="form-hint">{{ __('Lower numbers appear first.') }}</span>
        @error('sort_order')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-circle-check"></i> {{ __('Status') }}</label>
        <select name="status" class="form-control">
          <option value="active"   {{ old('status',$short->status)==='active'   ? 'selected':'' }}>{{ __('Active') }}</option>
          <option value="inactive" {{ old('status',$short->status)==='inactive' ? 'selected':'' }}>{{ __('Inactive') }}</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fas fa-film"></i> {{ __('Media File') }}</label>
      <div style="margin-bottom:.65rem">
        @if(! $short->media_path)
        <div class="badge badge-amber"><i class="fas fa-spinner fa-spin"></i> {{ __('Downloading from TikTok…') }}</div>
        @elseif($short->type === 'video')
        <video src="{{ $short->media_url }}" controls muted preload="metadata" style="max-width:220px;width:100%;border-radius:10px;border:1px solid var(--border2);background:#000"></video>
        @else
        <audio src="{{ $short->media_url }}" controls preload="metadata" style="width:100%;max-width:320px"></audio>
        @endif
      </div>
      <input type="hidden" name="media_tmp" id="media_tmp_input">
      <input type="file" id="media-pond" data-filepond data-pond-type="media" data-pond-token="media_tmp" accept="audio/*,video/*">
      <span class="form-hint">{{ __('Leave empty to keep current media · max 200MB') }}</span>
      @error('media_tmp')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fas fa-image"></i> {{ __('Poster / Cover Image') }}</label>
      @if($short->poster_url)
      <div style="margin-bottom:.65rem">
        <img src="{{ $short->poster_url }}" style="width:80px;height:106px;border-radius:8px;object-fit:cover;border:1px solid var(--border2)" alt="">
      </div>
      @endif
      <input type="hidden" name="poster_tmp" id="poster_tmp_input">
      <input type="file" id="poster-pond" data-filepond data-pond-type="image" data-pond-token="poster_tmp" accept="image/jpeg,image/png,image/webp">
      <span class="form-hint">{{ __('Leave empty to keep current image') }}</span>
      @error('poster_tmp')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div style="display:flex;gap:.65rem;margin-top:1.4rem;flex-wrap:wrap">
      <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> {{ __('Update Short') }}</button>
      <a href="{{ route('admin.shorts.index') }}" class="btn btn-ghost"><i class="fas fa-xmark"></i> {{ __('Cancel') }}</a>
    </div>
  </form>
</div>

<style>
.filepond--root { font-family: inherit; }
.filepond--panel-root { background: var(--surface); border: 1.5px dashed var(--border); border-radius: 10px; }
.filepond--drop-label { color: var(--text2); font-size: .85rem; }
.filepond--label-action { color: var(--gold); text-decoration: underline; cursor: pointer; }
.filepond--item-panel { background: var(--surface2, var(--surface)); }
.filepond--file-action-button { background: var(--gold); }
</style>
@endsection
