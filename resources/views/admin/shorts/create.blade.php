@extends('layouts.admin')
@section('title', __('Add Short'))
@section('page-title', __('Add New Short'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › <a href="{{ route('admin.shorts.index') }}">{{ __('Shorts') }}</a> › {{ __('Create') }}@endsection
@section('content')

<div style="max-width:660px" x-data="{ type: '{{ old('type','video') }}', source: '{{ old('source','upload') }}' }">
  <form method="POST" action="{{ route('admin.shorts.store') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="source" :value="source">

    @if($errors->any())
    <div class="alert alert-error" style="margin-bottom:1.25rem">
      @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
    </div>
    @endif

    <div class="form-group">
      <label class="form-label"><i class="fas fa-download"></i> {{ __('Media Source') }}</label>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap">
        <label class="btn" :class="source==='upload' ? 'btn-primary' : 'btn-ghost'" style="cursor:pointer">
          <input type="radio" x-model="source" value="upload" style="display:none">
          <i class="fas fa-upload"></i> {{ __('Upload file') }}
        </label>
        <label class="btn" :class="source==='tiktok' ? 'btn-primary' : 'btn-ghost'" style="cursor:pointer" @click="type='video'">
          <input type="radio" x-model="source" value="tiktok" style="display:none">
          <i class="fab fa-tiktok"></i> {{ __('Import from TikTok') }}
        </label>
      </div>
    </div>

    <div class="admin-form-grid">
      <div class="form-group admin-form-grid" style="grid-column:1/-1" x-show="source==='upload'">
        <div>
          <label class="form-label"><i class="fas fa-heading"></i> {{ __('Title (Arabic)') }} <span style="color:var(--red)">*</span></label>
          <input type="text" name="title_ar" class="form-control" value="{{ old('title_ar') }}" :required="source==='upload'" placeholder="{{ __('العنوان بالعربية') }}" dir="rtl">
          @error('title_ar')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div>
          <label class="form-label"><i class="fas fa-heading"></i> {{ __('Title (English)') }}</label>
          <input type="text" name="title_en" class="form-control" value="{{ old('title_en') }}" placeholder="{{ __('Title in English') }}" dir="ltr">
          @error('title_en')<span class="form-error">{{ $message }}</span>@enderror
        </div>
      </div>
      <div class="form-group" x-show="source==='upload'">
        <label class="form-label"><i class="fas fa-clapperboard"></i> {{ __('Type') }} <span style="color:var(--red)">*</span></label>
        <select name="type" class="form-control" x-model="type">
          <option value="video" {{ old('type','video')==='video' ? 'selected':'' }}>{{ __('Video') }}</option>
          <option value="audio" {{ old('type')==='audio' ? 'selected':'' }}>{{ __('Audio') }}</option>
        </select>
      </div>
      <div class="form-group" x-show="source==='tiktok'" x-cloak>
        <label class="form-label"><i class="fas fa-microphone-lines"></i> {{ __('Qari') }} <span style="color:var(--red)">*</span></label>
        <select name="qari_id" class="form-control" :required="source==='tiktok'">
          <option value="">{{ __('Select a qari…') }}</option>
          @foreach($qaris as $q)
          <option value="{{ $q->id }}" {{ old('qari_id') == $q->id ? 'selected' : '' }}>{{ $q->name }}</option>
          @endforeach
        </select>
        <span class="form-hint">{{ __('All imported shorts will be linked to this qari.') }}</span>
        @error('qari_id')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-sort"></i> {{ __('Sort Order') }}</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
        <span class="form-hint">{{ __('Lower numbers appear first.') }}</span>
        @error('sort_order')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-circle-check"></i> {{ __('Status') }}</label>
        <select name="status" class="form-control">
          <option value="active"   {{ old('status','active')==='active'   ? 'selected':'' }}>{{ __('Active') }}</option>
          <option value="inactive" {{ old('status')==='inactive' ? 'selected':'' }}>{{ __('Inactive') }}</option>
        </select>
      </div>
    </div>

    <div x-show="source==='tiktok'" x-cloak>
      <div class="form-group">
        <label class="form-label"><i class="fab fa-tiktok"></i> {{ __('TikTok Video URLs') }} <span style="color:var(--red)">*</span></label>
        <textarea name="urls" class="form-control" rows="5" dir="ltr" placeholder="https://www.tiktok.com/@user/video/...&#10;https://vm.tiktok.com/...">{{ old('urls') }}</textarea>
        <span class="form-hint">{{ __('One link per line. Each becomes a short — the title comes from the TikTok video.') }}</span>
        @error('urls')<span class="form-error">{{ $message }}</span>@enderror
      </div>

      <div class="form-group">
        <label class="form-label"><i class="fas fa-file-lines"></i> {{ __('Or upload a link list') }}</label>
        <input type="file" name="urls_file" class="form-control" accept=".txt,.csv">
        <span class="form-hint">{{ __('A .txt or .csv file with one TikTok link per line.') }}</span>
        @error('urls_file')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>

    <div class="form-group" x-show="source==='upload'">
      <label class="form-label"><i class="fas fa-film"></i> {{ __('Media File') }} <span style="color:var(--red)">*</span></label>
      <input type="hidden" name="media_tmp" id="media_tmp_input">
      <input type="file" id="media-pond" data-filepond data-pond-type="media" data-pond-token="media_tmp" accept="audio/*,video/*">
      <span class="form-hint">{{ __('MP4, WEBM, MP3 or WAV · max 200MB · short clips work best') }}</span>
      @error('media_tmp')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group" x-show="source==='upload'">
      <label class="form-label"><i class="fas fa-image"></i> {{ __('Poster / Cover Image') }} <span x-show="type==='audio'" style="color:var(--red)">*</span></label>
      <input type="hidden" name="poster_tmp" id="poster_tmp_input">
      <input type="file" id="poster-pond" data-filepond data-pond-type="image" data-pond-token="poster_tmp" accept="image/jpeg,image/png,image/webp">
      <span class="form-hint" x-show="type==='audio'">{{ __('Shown behind audio shorts · recommended for audio.') }}</span>
      <span class="form-hint" x-show="type==='video'" style="display:none">{{ __('Optional fallback thumbnail for video.') }}</span>
      @error('poster_tmp')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div style="display:flex;gap:.65rem;margin-top:1.4rem;flex-wrap:wrap">
      <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> {{ __('Create Short') }}</button>
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
