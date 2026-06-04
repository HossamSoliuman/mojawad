@extends('layouts.admin')
@section('title','Add Tilawa')
@section('page-title','Add New Tilawa')
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">Dashboard</a> › <a href="{{ route('admin.tilawat.index') }}">Tilawat</a> › Create @endsection
@section('content')

<div style="max-width:700px">
  <form method="POST" action="{{ route('admin.tilawat.store') }}" id="tilawa-form">
    @csrf

    @if($errors->any())
    <div class="alert alert-error" style="margin-bottom:1.25rem">
      @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
    </div>
    @endif

    <div class="admin-form-grid">
      <div class="form-group admin-form-grid" style="grid-column:1/-1">
        <div>
          <label class="form-label"><i class="fas fa-heading"></i> {{ __('Title (Arabic)') }} <span style="color:var(--red)">*</span></label>
          <input type="text" name="title_ar" class="form-control" value="{{ old('title_ar') }}" required placeholder="{{ __('سورة الفاتحة') }}" autofocus dir="rtl">
          @error('title_ar')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div>
          <label class="form-label"><i class="fas fa-heading"></i> {{ __('Title (English)') }}</label>
          <input type="text" name="title_en" class="form-control" value="{{ old('title_en') }}" placeholder="{{ __('Surah Al-Fatiha') }}" dir="ltr">
          @error('title_en')<span class="form-error">{{ $message }}</span>@enderror
        </div>
      </div>
      <div class="form-group" style="grid-column:1/-1">
        <label class="form-label"><i class="fas fa-microphone"></i> {{ __('Qari') }} <span style="color:var(--red)">*</span></label>
        <x-card-select name="qari_id" :selected="old('qari_id')" grid :options="$qaris->map(fn($q) => [
          'value' => $q->id,
          'label' => $q->name,
          'image' => $q->image_url,
        ])->all()" />
        @error('qari_id')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      @role('admin')
      <div class="form-group" style="grid-column:1/-1">
        <label class="form-label"><i class="fas fa-circle-check"></i> {{ __('Status') }}</label>
        <x-card-select name="status" :selected="old('status','active')" :options="[
          ['value' => 'active', 'label' => __('Active'), 'icon' => 'fa-circle-check'],
          ['value' => 'pending', 'label' => __('Pending'), 'icon' => 'fa-clock'],
          ['value' => 'inactive', 'label' => __('Inactive'), 'icon' => 'fa-ban'],
        ]" />
      </div>
      @endrole
      <div class="form-group">
        <label class="form-label"><i class="fas fa-calendar"></i> {{ __('Recorded Date') }}</label>
        <input type="date" name="recorded_at" class="form-control" value="{{ old('recorded_at') }}">
        @error('recorded_at')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-location-dot"></i> {{ __('Recorded Place') }}</label>
        <input type="text" name="recorded_place" class="form-control" value="{{ old('recorded_place') }}" placeholder="Makkah, Saudi Arabia">
      </div>
    </div>

    <div class="admin-form-grid" style="margin-bottom:1rem">
      <div class="form-group">
        <label class="form-label"><i class="fas fa-align-left"></i> {{ __('Description (Arabic)') }}</label>
        <textarea name="description_ar" class="form-control" rows="4" placeholder="{{ __('وصف اختياري...') }}" dir="rtl">{{ old('description_ar') }}</textarea>
        @error('description_ar')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-align-left"></i> {{ __('Description (English)') }}</label>
        <textarea name="description_en" class="form-control" rows="4" placeholder="{{ __('Optional description…') }}" dir="ltr">{{ old('description_en') }}</textarea>
        @error('description_en')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>

    <div class="admin-form-grid">
      <div class="form-group">
        <label class="form-label"><i class="fas fa-file-audio"></i> {{ __('Audio File') }} <span style="color:var(--red)">*</span></label>
        <input type="hidden" name="audio_tmp" id="audio_tmp_input">
        <input type="file" id="audio-pond" data-filepond data-pond-type="audio" data-pond-token="audio_tmp" data-pond-required="true" accept="audio/mp3,audio/mpeg,audio/ogg,audio/wav">
        <span class="form-hint">{{ __('MP3, OGG or WAV · max 200MB') }}</span>
        @error('audio_tmp')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-image"></i> {{ __('Cover Image') }}</label>
        <input type="hidden" name="cover_image_tmp" id="cover_image_tmp_input">
        <input type="file" id="cover-pond" data-filepond data-pond-type="cover" data-pond-token="cover_image_tmp" accept="image/jpeg,image/png,image/webp">
        <span class="form-hint">{{ __('Optional · defaults to qari image') }}</span>
        @error('cover_image_tmp')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>

    <div class="form-group">
      <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.9rem;color:var(--text2)">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked':'' }} style="accent-color:var(--gold);width:15px;height:15px">
        <i class="fas fa-star" style="color:var(--gold)"></i> {{ __('Featured Tilawa') }}
      </label>
    </div>

    <div style="display:flex;gap:.65rem;margin-top:1.4rem;flex-wrap:wrap">
      <button type="submit" class="btn btn-primary" id="submit-btn"><i class="fas fa-floppy-disk"></i> {{ __('Create Tilawa') }}</button>
      <a href="{{ route('admin.tilawat.index') }}" class="btn btn-ghost"><i class="fas fa-xmark"></i> {{ __('Cancel') }}</a>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var submitBtn = document.getElementById('submit-btn');
    var form      = document.getElementById('tilawa-form');

    form.addEventListener('submit', function (e) {
        var audioToken = document.getElementById('audio_tmp_input').value;
        if (!audioToken) {
            e.preventDefault();
            alert('Please wait for the audio file to finish uploading before submitting.');
        }
    });
});
</script>
@endsection
