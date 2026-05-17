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

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="form-group" style="grid-column:1/-1; display:grid; grid-template-columns:1fr 1fr; gap:1rem">
        <div>
          <label class="form-label"><i class="fas fa-heading"></i> Title (Arabic) <span style="color:var(--red)">*</span></label>
          <input type="text" name="title_ar" class="form-control" value="{{ old('title_ar') }}" required placeholder="سورة الفاتحة" autofocus>
          @error('title_ar')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div>
          <label class="form-label"><i class="fas fa-heading"></i> Title (English)</label>
          <input type="text" name="title_en" class="form-control" value="{{ old('title_en') }}" placeholder="Surah Al-Fatiha">
          @error('title_en')<span class="form-error">{{ $message }}</span>@enderror
        </div>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-microphone"></i> Qari <span style="color:var(--red)">*</span></label>
        <select name="qari_id" class="form-control" required>
          @foreach($qaris as $q)
          <option value="{{ $q->id }}"  {{ old('qari_id')==$q->id ? 'selected':'' }}>{{ $q->name }}</option>
          @endforeach
        </select>
        @error('qari_id')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-circle-check"></i> Status</label>
        <select name="status" class="form-control">
          <option value="pending"  {{ old('status')==='pending'  ? 'selected':'' }}>Pending</option>
          <option value="active"   {{ old('status','active')==='active'   ? 'selected':'' }}>Active</option>
          <option value="inactive" {{ old('status')==='inactive' ? 'selected':'' }}>Inactive</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-calendar"></i> Recorded Date</label>
        <input type="date" name="recorded_at" class="form-control" value="{{ old('recorded_at') }}">
        @error('recorded_at')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-location-dot"></i> Recorded Place</label>
        <input type="text" name="recorded_place" class="form-control" value="{{ old('recorded_place') }}" placeholder="Makkah, Saudi Arabia">
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
      <div class="form-group">
        <label class="form-label"><i class="fas fa-align-left"></i> Description (Arabic)</label>
        <textarea name="description_ar" class="form-control" rows="4" placeholder="وصف اختياري...">{{ old('description_ar') }}</textarea>
        @error('description_ar')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-align-left"></i> Description (English)</label>
        <textarea name="description_en" class="form-control" rows="4" placeholder="Optional description…">{{ old('description_en') }}</textarea>
        @error('description_en')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="form-group">
        <label class="form-label"><i class="fas fa-file-audio"></i> Audio File <span style="color:var(--red)">*</span></label>
        <input type="hidden" name="audio_tmp" id="audio_tmp_input">
        <input type="file" id="audio-pond" data-filepond data-pond-type="audio" data-pond-token="audio_tmp" data-pond-required="true" accept="audio/mp3,audio/mpeg,audio/ogg,audio/wav">
        <span class="form-hint">MP3, OGG or WAV · max 200MB</span>
        @error('audio_tmp')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-image"></i> Cover Image</label>
        <input type="hidden" name="cover_image_tmp" id="cover_image_tmp_input">
        <input type="file" id="cover-pond" data-filepond data-pond-type="cover" data-pond-token="cover_image_tmp" accept="image/jpeg,image/png,image/webp">
        <span class="form-hint">Optional · defaults to qari image</span>
        @error('cover_image_tmp')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>

    <div class="form-group">
      <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.9rem;color:var(--text2)">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked':'' }} style="accent-color:var(--gold);width:15px;height:15px">
        <i class="fas fa-star" style="color:var(--gold)"></i> Featured Tilawa
      </label>
    </div>

    <div style="display:flex;gap:.65rem;margin-top:1.4rem">
      <button type="submit" class="btn btn-primary" id="submit-btn"><i class="fas fa-floppy-disk"></i> Create Tilawa</button>
      <a href="{{ route('admin.tilawat.index') }}" class="btn btn-ghost"><i class="fas fa-xmark"></i> Cancel</a>
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
