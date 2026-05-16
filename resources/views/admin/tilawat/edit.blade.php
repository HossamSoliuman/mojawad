@extends('layouts.admin')
@section('title','Edit Tilawa')
@section('page-title','Edit Tilawa')
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">Dashboard</a> › <a href="{{ route('admin.tilawat.index') }}">Tilawat</a> › Edit @endsection
@section('content')

<div style="max-width:700px">
  <form method="POST" action="{{ route('admin.tilawat.update',$tilawa) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    @if($errors->any())
    <div class="alert alert-error" style="margin-bottom:1.25rem">
      @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
    </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="form-group" style="grid-column:1/-1">
        <label class="form-label"><i class="fas fa-heading"></i> Title <span style="color:var(--red)">*</span></label>
        <input type="text" name="title" class="form-control" value="{{ old('title',$tilawa->title) }}" required>
        @error('title')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-microphone"></i> Qari <span style="color:var(--red)">*</span></label>
        <select name="qari_id" class="form-control" required>
          @foreach($qaris as $q)
          <option value="{{ $q->id }}" {{ old('qari_id',$tilawa->qari_id)==$q->id ? 'selected':'' }}>{{ $q->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-circle-check"></i> Status</label>
        <select name="status" class="form-control">
          <option value="pending"  {{ old('status',$tilawa->status)==='pending'  ? 'selected':'' }}>Pending</option>
          <option value="active"   {{ old('status',$tilawa->status)==='active'   ? 'selected':'' }}>Active</option>
          <option value="inactive" {{ old('status',$tilawa->status)==='inactive' ? 'selected':'' }}>Inactive</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-calendar"></i> Recorded Date</label>
        <input type="date" name="recorded_at" class="form-control" value="{{ old('recorded_at',$tilawa->recorded_at?->format('Y-m-d')) }}">
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-location-dot"></i> Recorded Place</label>
        <input type="text" name="recorded_place" class="form-control" value="{{ old('recorded_place',$tilawa->recorded_place) }}" placeholder="Makkah, Saudi Arabia">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
      <textarea name="description" class="form-control" rows="4">{{ old('description',$tilawa->description) }}</textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="form-group">
        <label class="form-label"><i class="fas fa-file-audio"></i> Replace Audio</label>
        <div style="font-size:.78rem;color:var(--green);margin-bottom:.45rem">
          <i class="fas fa-check-circle"></i> Audio uploaded · {{ $tilawa->formatted_duration }}
        </div>
        <input type="file" name="audio" accept="audio/mp3,audio/mpeg,audio/ogg,audio/wav" class="form-control">
        <span class="form-hint">Leave empty to keep current</span>
        @error('audio')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-image"></i> Replace Cover</label>
        @if($tilawa->cover_image)
        <img src="{{ $tilawa->cover_url }}" style="width:44px;height:44px;border-radius:7px;object-fit:cover;margin-bottom:.45rem" alt="">
        @endif
        <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="form-control">
        <span class="form-hint">Leave empty to keep current</span>
        @error('cover_image')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>

    <div class="form-group">
      <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.9rem;color:var(--text2)">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured',$tilawa->is_featured) ? 'checked':'' }} style="accent-color:var(--gold);width:15px;height:15px">
        <i class="fas fa-star" style="color:var(--gold)"></i> Featured Tilawa
      </label>
    </div>

    <div style="display:flex;gap:.65rem;margin-top:1.4rem">
      <button type="submit" class="btn btn-primary" id="submit-btn"><i class="fas fa-floppy-disk"></i> Update Tilawa</button>
      <a href="{{ route('admin.tilawat.index') }}" class="btn btn-ghost"><i class="fas fa-xmark"></i> Cancel</a>
    </div>
  </form>
</div>

<div id="upload-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center;flex-direction:column;gap:1.25rem">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:2.5rem 3rem;text-align:center;max-width:400px;width:90%">
    <div style="font-size:1rem;font-weight:600;color:var(--text1);margin-bottom:.35rem" id="overlay-title">Saving file…</div>
    <div style="font-size:.85rem;color:var(--text2);margin-bottom:1.5rem">Please wait while your file is being saved. You will be redirected automatically.</div>
    <div style="background:var(--bg);border-radius:99px;height:10px;overflow:hidden;border:1px solid var(--border)">
      <div style="height:100%;width:100%;border-radius:99px;background:linear-gradient(90deg,var(--gold),#f97316);animation:indeterminate 1.5s ease-in-out infinite"></div>
    </div>
  </div>
</div>

<style>
@keyframes indeterminate {
  0%   { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}
</style>

<script>
document.querySelector('form').addEventListener('submit', function () {
  var audioInput = document.querySelector('input[name="audio"]');
  if (audioInput && audioInput.files && audioInput.files.length > 0) {
    var overlay = document.getElementById('upload-overlay');
    overlay.style.display = 'flex';
    document.getElementById('submit-btn').disabled = true;
  }
});
</script>
@endsection

