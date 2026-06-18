@extends('layouts.admin')
@section('title', __('Studio'))
@section('page-title', __('Studio'))
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a> › {{ __('Studio') }}@endsection
@section('content')

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:1.25rem">
  @foreach($errors->all() as $e)<div><i class="fas fa-circle-exclamation"></i> {{ $e }}</div>@endforeach
</div>
@endif

<div style="max-width:760px;margin-bottom:2.2rem" x-data="{ mode: 'paste' }">

  <div style="margin-bottom:1.4rem">
    <h2 style="font-size:1.1rem;font-weight:700;margin:0 0 .25rem">{{ __('Import from YouTube') }}</h2>
    <p style="color:var(--text2);font-size:.86rem;margin:0">{{ __('Paste YouTube links and pick a qari — we download the audio and create a tilawa for each in the background.') }}</p>
  </div>

  {{-- Mode toggle --}}
  <div style="display:flex;gap:.4rem;margin-bottom:1.2rem">
    <button type="button" class="btn btn-sm" :class="mode==='paste' ? 'btn-primary' : 'btn-ghost'" @click="mode='paste'">
      <i class="fas fa-paste"></i> {{ __('Paste links') }}
    </button>
    <button type="button" class="btn btn-sm" :class="mode==='file' ? 'btn-primary' : 'btn-ghost'" @click="mode='file'">
      <i class="fas fa-file-arrow-up"></i> {{ __('Upload a link list') }}
    </button>
  </div>

  {{-- Paste / single --}}
  <form method="POST" action="{{ route('admin.studio.import') }}" x-show="mode==='paste'">
    @csrf
    <div class="form-group">
      <label class="form-label"><i class="fas fa-microphone-lines"></i> {{ __('Qari') }} <span style="color:var(--red)">*</span></label>
      <select name="qari_id" class="form-control" required>
        <option value="">{{ __('Select a qari…') }}</option>
        @foreach($qaris as $q)
        <option value="{{ $q->id }}" {{ old('qari_id') == $q->id ? 'selected' : '' }}>{{ $q->name }}</option>
        @endforeach
      </select>
      @error('qari_id')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fab fa-youtube"></i> {{ __('Paste YouTube links') }} <span style="color:var(--red)">*</span></label>
      <textarea name="urls" class="form-control" rows="6" dir="ltr" placeholder="https://www.youtube.com/watch?v=…&#10;https://youtu.be/…">{{ old('urls') }}</textarea>
      <span class="form-hint">{{ __('One link per line.') }}</span>
      @error('urls')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-cloud-arrow-down"></i> {{ __('Import') }}</button>
  </form>

  {{-- File upload --}}
  <form method="POST" action="{{ route('admin.studio.import-file') }}" enctype="multipart/form-data" x-show="mode==='file'" style="display:none">
    @csrf
    <div class="form-group">
      <label class="form-label"><i class="fas fa-microphone-lines"></i> {{ __('Qari') }} <span style="color:var(--red)">*</span></label>
      <select name="qari_id" class="form-control" required>
        <option value="">{{ __('Select a qari…') }}</option>
        @foreach($qaris as $q)
        <option value="{{ $q->id }}" {{ old('qari_id') == $q->id ? 'selected' : '' }}>{{ $q->name }}</option>
        @endforeach
      </select>
      @error('qari_id')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fas fa-file-lines"></i> {{ __('Upload a link list') }} <span style="color:var(--red)">*</span></label>
      <input type="file" name="file" class="form-control" accept=".txt,.csv" required>
      <span class="form-hint">{{ __('A .txt or .csv file with one YouTube link per line.') }}</span>
      @error('file')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-cloud-arrow-down"></i> {{ __('Import') }}</button>
  </form>

</div>

<livewire:admin.studio-queue />

@endsection
