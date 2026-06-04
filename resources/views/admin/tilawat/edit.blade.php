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

    @if($tilawa->review_status === 'rejected' && $tilawa->rejection_note)
    <div class="rejection-banner" style="margin-bottom:1.25rem">
      <i class="fas fa-circle-exclamation" style="margin-top:.15rem"></i>
      <div>
        <div style="font-weight:700;text-transform:uppercase;letter-spacing:.06em;font-size:.72rem">{{ __('Returned for changes') }}</div>
        <div style="margin-top:.3rem;color:var(--text)">{{ $tilawa->rejection_note }}</div>
        <div style="margin-top:.3rem;font-size:.78rem;color:var(--text2)">{{ __('Fix the issue above and save to resubmit for review.') }}</div>
      </div>
    </div>
    @elseif($tilawa->review_status === 'pending' && ! auth()->user()->hasRole('admin'))
    <div class="alert" style="margin-bottom:1.25rem;background:rgba(212,134,10,.1);border:1px solid rgba(212,134,10,.25);color:#f5a832">
      <i class="fas fa-clock"></i> {{ __('This tilawa is awaiting review.') }}
    </div>
    @endif

    <div class="admin-form-grid">
      <div class="form-group admin-form-grid" style="grid-column:1/-1">
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
      <div class="form-group" style="grid-column:1/-1">
        <label class="form-label"><i class="fas fa-microphone"></i> {{ __('Qari') }} <span style="color:var(--red)">*</span></label>
        <x-card-select name="qari_id" :selected="old('qari_id', $tilawa->qari_id)" grid :options="$qaris->map(fn($q) => [
          'value' => $q->id,
          'label' => $q->name,
          'image' => $q->image_url,
        ])->all()" />
      </div>
      @role('admin')
      <div class="form-group" style="grid-column:1/-1">
        <label class="form-label"><i class="fas fa-circle-check"></i> {{ __('Status') }}</label>
        <x-card-select name="status" :selected="old('status', $tilawa->status)" :options="[
          ['value' => 'active', 'label' => __('Active'), 'icon' => 'fa-circle-check'],
          ['value' => 'pending', 'label' => __('Pending'), 'icon' => 'fa-clock'],
          ['value' => 'inactive', 'label' => __('Inactive'), 'icon' => 'fa-ban'],
        ]" />
      </div>
      @endrole
      <div class="form-group">
        <label class="form-label"><i class="fas fa-calendar"></i> {{ __('Recorded Date') }}</label>
        <input type="date" name="recorded_at" class="form-control" value="{{ old('recorded_at',$tilawa->recorded_at?->format('Y-m-d')) }}">
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-location-dot"></i> {{ __('Recorded Place') }}</label>
        <input type="text" name="recorded_place" class="form-control" value="{{ old('recorded_place',$tilawa->recorded_place) }}" placeholder="Makkah, Saudi Arabia">
      </div>
    </div>

    <div class="admin-form-grid" style="margin-bottom:1rem">
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

    <div class="admin-form-grid">
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

    <div style="display:flex;gap:.65rem;margin-top:1.4rem;flex-wrap:wrap">
      @if(! auth()->user()->hasRole('admin') && in_array($tilawa->review_status, ['rejected', 'pending']))
      <button type="submit" class="btn btn-primary" id="submit-btn"><i class="fas fa-paper-plane"></i> {{ __('Save & Resubmit for Review') }}</button>
      @else
      <button type="submit" class="btn btn-primary" id="submit-btn"><i class="fas fa-floppy-disk"></i> {{ __('Update Tilawa') }}</button>
      @endif
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
