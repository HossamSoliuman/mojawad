@extends('layouts.admin')
@section('title','Edit Qari')
@section('page-title','Edit Qari')
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">Dashboard</a> › <a href="{{ route('admin.qaris.index') }}">Qaris</a> › Edit @endsection
@section('content')

<div style="max-width:660px">
  <form method="POST" action="{{ route('admin.qaris.update',$qari) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    @if($errors->any())
    <div class="alert alert-error" style="margin-bottom:1.25rem">
      @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
    </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="form-group" style="grid-column:1/-1">
        <label class="form-label"><i class="fas fa-user"></i> Qari Name <span style="color:var(--red)">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name',$qari->name) }}" required>
        @error('name')<span class="form-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-circle-check"></i> Status</label>
        <select name="status" class="form-control">
          <option value="pending"  {{ old('status',$qari->status)==='pending'  ? 'selected':'' }}>Pending</option>
          <option value="active"   {{ old('status',$qari->status)==='active'   ? 'selected':'' }}>Active</option>
          <option value="inactive" {{ old('status',$qari->status)==='inactive' ? 'selected':'' }}>Inactive</option>
        </select>
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:.2rem">
        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.9rem;color:var(--text2)">
          <input type="checkbox" name="is_featured" value="1" {{ old('is_featured',$qari->is_featured) ? 'checked':'' }} style="accent-color:var(--gold);width:15px;height:15px">
          <i class="fas fa-star" style="color:var(--gold)"></i> Featured Qari
        </label>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fas fa-image"></i> Qari Image</label>
      @if($qari->image)
      <div style="margin-bottom:.65rem;display:flex;align-items:center;gap:.75rem">
        <img src="{{ $qari->image_url }}" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--border2)" alt="{{ $qari->name }}">
        <span style="font-size:.8rem;color:var(--text2)">Upload a new image to replace</span>
      </div>
      @endif
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control">
      @error('image')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fas fa-align-left"></i> Biography</label>
      <textarea name="biography" class="form-control" rows="8">{{ old('biography',$qari->biography) }}</textarea>
      @error('biography')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div style="display:flex;gap:.65rem;margin-top:1.4rem">
      <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Update Qari</button>
      <a href="{{ route('admin.qaris.index') }}" class="btn btn-ghost"><i class="fas fa-xmark"></i> Cancel</a>
    </div>
  </form>
</div>
@endsection
