@extends('layouts.admin')
@section('title','Edit Tilawa')
@section('page-title','Edit Tilawa')
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">Dashboard</a> › <a href="{{ route('admin.tilawat.index') }}">Tilawat</a> › Edit @endsection
@section('content')

<div style="max-width:700px">
  <form method="POST" action="{{ route('admin.tilawat.update',$tilawa) }}" id="tilawa-form">
    @csrf @method('PUT')

    @if($errors->any())
    <div class="alert alert-error" style="margin-bottom:1.25rem">
      @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
    </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="form-group" style="grid-column:1/-1; display:grid; grid-template-columns:1fr 1fr; gap:1rem">
        <div>
          <label class="form-label"><i class="fas fa-heading"></i> {{ __('Title (Arabic)') }} <span style="color:var(--red)">*</span></label>
          <input type="text" name="title_ar" class="form-control" value="{{ old('title_ar', $tilawa->title_ar) }}" required placeholder="{{ __('سورة الفاتحة') }}" dir="rtl">
          @error('title_ar')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div>
          <label class="form-label"><i class="fas fa-heading"></i> {{ __('Title (English)') }}</label>
          <input type="text" name="title_en" class="form-control" value="{{ old('title_en', $tilawa->title_en) }}" placeholder="{{ __('Surah Al-Fatiha') }}" dir="ltr">
          @error('title_en')<span class="form-error">{{ $message }}</span>@enderror
        </div>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-microphone"></i> {{ __('Qari') }} <span style="color:var(--red)">*</span></label>
        <select name="qari_id" class="form-control" required>
          @foreach($qaris as $q)
          <option value="{{ $q->id }}" {{ old('qari_id',$tilawa->qari_id)==$q->id ? 'selected':'' }}>{{ $q->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-circle-check"></i> {{ __('Status') }}</label>
        <select name="status" class="form-control">
          <option value="pending"  {{ old('status',$tilawa->status)==='pending'  ? 'selected':'' }}>{{ __('Pending') }}</option>
          <option value="active"   {{ old('status',$tilawa->status)==='active'   ? 'selected':'' }}>{{ __('Active') }}</option>
          <option value="inactive" {{ old('status',$tilawa->status)==='inactive' ? 'selected':'' }}>{{ __('Inactive') }}</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-calendar"></i> {{ __('Recorded Date') }}</label>
        <input type="date" name="recorded_at" class="form-control" value="{{ old('recorded_at',$tilawa->recorded_at?->format('Y-m-d')) }}">
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-location-dot"></i> {{ __('Recorded Place') }}</label>
        <input type="text" name="recorded_place" class="form-control" value="{{ old('recorded_place',$tilawa->recorded_place) }}" placeholder="Makkah, Saudi Arabia">
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
      <div class="form-group">
        <label class="form-label"><i class="fas fa-align-left"></i> {{ __('Description (Arabic)') }}</label>
        <textarea name="description_ar" class="form-control" rows="4" dir="rtl">{{ old('description_ar', $tilawa->description_ar) }}</textarea>
        @error('description_ar')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-align-left"></i> {{ __('Description (English)') }}</label>
        <textarea name="description_en" class="form-control" rows="4" dir="ltr">{{ old('description_en', $tilawa->description_en) }}</textarea>
        @error('description_en')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="form-group">
        <label class="form-label"><i class="fas fa-file-audio"></i> {{ __('Replace Audio') }}</label>
        <div style="font-size:.78rem;color:var(--green);margin-bottom:.6rem">
          <i class="fas fa-check-circle"></i> {{ __('Current:') }} {{ $tilawa->formatted_duration }}
        </div>
        <input type="hidden" name="audio_tmp" id="audio_tmp_input">
        <input type="file" id="audio-pond" data-filepond data-pond-type="audio" data-pond-token="audio_tmp" accept="audio/mp3,audio/mpeg,audio/ogg,audio/wav">
        <span class="form-hint">{{ __('Leave empty to keep current audio') }}</span>
        @error('audio_tmp')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-image"></i> {{ __('Replace Cover') }}</label>
        @if($tilawa->cover_image)
        <img src="{{ $tilawa->cover_url }}" style="width:44px;height:44px;border-radius:7px;object-fit:cover;margin-bottom:.6rem" alt="">
        @endif
        <input type="hidden" name="cover_image_tmp" id="cover_image_tmp_input">
        <input type="file" id="cover-pond" data-filepond data-pond-type="cover" data-pond-token="cover_image_tmp" accept="image/jpeg,image/png,image/webp">
        <span class="form-hint">{{ __('Leave empty to keep current cover') }}</span>
        @error('cover_image_tmp')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>

    <div class="form-group">
      <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.9rem;color:var(--text2)">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured',$tilawa->is_featured) ? 'checked':'' }} style="accent-color:var(--gold);width:15px;height:15px">
        <i class="fas fa-star" style="color:var(--gold)"></i> {{ __('Featured Tilawa') }}
      </label>
    </div>

    <div style="display:flex;gap:.65rem;margin-top:1.4rem">
      <button type="submit" class="btn btn-primary" id="submit-btn"><i class="fas fa-floppy-disk"></i> {{ __('Update Tilawa') }}</button>
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
@endsection
