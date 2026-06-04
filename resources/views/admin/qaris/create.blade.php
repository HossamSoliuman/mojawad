@extends('layouts.admin')
@section('title','Add Qari')
@section('page-title','Add New Qari')
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">Dashboard</a> › <a href="{{ route('admin.qaris.index') }}">Qaris</a> › Create @endsection
@section('content')

<div style="max-width:660px">
  <form method="POST" action="{{ route('admin.qaris.store') }}">
    @csrf

    @if($errors->any())
    <div class="alert alert-error" style="margin-bottom:1.25rem">
      @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
    </div>
    @endif

    <div class="admin-form-grid">
      <div class="form-group admin-form-grid" style="grid-column:1/-1">
        <div>
          <label class="form-label"><i class="fas fa-user"></i> {{ __('Qari Name (Arabic)') }} <span style="color:var(--red)">*</span></label>
          <input type="text" name="name_ar" class="form-control" value="{{ old('name_ar') }}" required placeholder="{{ __('الاسم بالعربية') }}" autofocus dir="rtl">
          @error('name_ar')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div>
          <label class="form-label"><i class="fas fa-user"></i> {{ __('Qari Name (English)') }}</label>
          <input type="text" name="name_en" class="form-control" value="{{ old('name_en') }}" placeholder="{{ __('Name in English') }}" dir="ltr">
          @error('name_en')<span class="form-error">{{ $message }}</span>@enderror
        </div>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-circle-check"></i> {{ __('Status') }}</label>
        <select name="status" class="form-control">
          <option value="pending"  {{ old('status','pending')==='pending'  ? 'selected':'' }}>{{ __('Pending') }}</option>
          <option value="active"   {{ old('status')==='active'   ? 'selected':'' }}>{{ __('Active') }}</option>
          <option value="inactive" {{ old('status')==='inactive' ? 'selected':'' }}>{{ __('Inactive') }}</option>
        </select>
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:.2rem">
        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.9rem;color:var(--text2)">
          <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked':'' }} style="accent-color:var(--gold);width:15px;height:15px">
          <i class="fas fa-star" style="color:var(--gold)"></i> {{ __('Featured Qari') }}
        </label>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fas fa-image"></i> {{ __('Qari Image') }}</label>
      <input type="hidden" name="image_tmp" id="image_tmp_input">
      <input type="file" id="image-pond" data-filepond data-pond-type="image" data-pond-token="image_tmp" accept="image/jpeg,image/png,image/webp">
      <span class="form-hint">{{ __('JPG, PNG or WEBP · max 20MB · optional') }}</span>
      @error('image_tmp')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div class="admin-form-grid" style="margin-bottom:1rem">
      <div class="form-group">
        <label class="form-label"><i class="fas fa-align-left"></i> {{ __('Biography (Arabic)') }}</label>
        <textarea name="biography_ar" class="form-control" rows="8" placeholder="{{ __('السيرة الذاتية...') }}" dir="rtl">{{ old('biography_ar') }}</textarea>
        @error('biography_ar')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-align-left"></i> {{ __('Biography (English)') }}</label>
        <textarea name="biography_en" class="form-control" rows="8" placeholder="{{ __('Biography...') }}" dir="ltr">{{ old('biography_en') }}</textarea>
        @error('biography_en')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>

    <div style="display:flex;gap:.65rem;margin-top:1.4rem;flex-wrap:wrap">
      <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> {{ __('Create Qari') }}</button>
      <a href="{{ route('admin.qaris.index') }}" class="btn btn-ghost"><i class="fas fa-xmark"></i> {{ __('Cancel') }}</a>
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
