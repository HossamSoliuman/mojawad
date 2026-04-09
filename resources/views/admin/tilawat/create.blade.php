@extends('layouts.admin')
@section('title','Add Tilawa')
@section('page-title','Add New Tilawa')
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">Dashboard</a> › <a href="{{ route('admin.tilawat.index') }}">Tilawat</a> › Create@endsection
@section('content')

<div style="max-width:700px">
  <form method="POST" action="{{ route('admin.tilawat.store') }}" enctype="multipart/form-data">
    @csrf

    @if($errors->any())
    <div class="alert alert-error" style="margin-bottom:1.25rem">
      @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
    </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="form-group" style="grid-column:1/-1">
        <label class="form-label"><i class="fas fa-heading"></i> Title <span style="color:var(--red)">*</span></label>
        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required placeholder="Surah Al-Fatiha" autofocus>
        @error('title')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-microphone"></i> Qari <span style="color:var(--red)">*</span></label>
        <select name="qari_id" class="form-control" required>
          <option value="">Select Qari…</option>
          @foreach($qaris as $q)
          <option value="{{ $q->id }}" {{ old('qari_id')==$q->id ? 'selected':'' }}>{{ $q->name }}</option>
          @endforeach
        </select>
        @error('qari_id')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-circle-check"></i> Status</label>
        <select name="status" class="form-control">
          <option value="pending"  {{ old('status','pending')==='pending'  ? 'selected':'' }}>Pending</option>
          <option value="active"   {{ old('status')==='active'   ? 'selected':'' }}>Active</option>
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

    <div class="form-group">
      <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
      <textarea name="description" class="form-control" rows="4" placeholder="Optional description…">{{ old('description') }}</textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="form-group">
        <label class="form-label"><i class="fas fa-file-audio"></i> Audio File <span style="color:var(--red)">*</span></label>
        <input type="file" name="audio" accept="audio/mp3,audio/mpeg,audio/ogg,audio/wav" class="form-control" required>
        <span class="form-hint">MP3, OGG or WAV · max 200MB</span>
        @error('audio')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-image"></i> Cover Image</label>
        <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="form-control">
        <span class="form-hint">Optional · defaults to qari image</span>
        @error('cover_image')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>

    <div class="form-group">
      <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.9rem;color:var(--text2)">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked':'' }} style="accent-color:var(--gold);width:15px;height:15px">
        <i class="fas fa-star" style="color:var(--gold)"></i> Featured Tilawa
      </label>
    </div>

    <div style="display:flex;gap:.65rem;margin-top:1.4rem">
      <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Create Tilawa</button>
      <a href="{{ route('admin.tilawat.index') }}" class="btn btn-ghost"><i class="fas fa-xmark"></i> Cancel</a>
    </div>
  </form>
</div>
@endsection
