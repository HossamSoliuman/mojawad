@extends('layouts.admin')
@section('title','Tilawat')
@section('page-title','Tilawat')
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">Dashboard</a> › Tilawat @endsection
@section('topbar-actions')
<a href="{{ route('admin.tilawat.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Tilawa</a>
@endsection
@section('content')

@if(request('uploaded'))
<div class="alert alert-success" style="margin-bottom:1.4rem"><i class="fas fa-check-circle"></i> Tilawa created and uploaded to Archive.org successfully.</div>
@endif

<form method="GET" class="admin-filter-bar" id="tilawat-filter" style="display:flex;align-items:center;gap:.65rem;margin-bottom:1.4rem;flex-wrap:wrap">
  <div style="position:relative">
    <i class="fas fa-magnifying-glass" style="position:absolute;left:.82rem;top:50%;transform:translateY(-50%);color:var(--text3);font-size:.78rem;pointer-events:none"></i>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tilawat…"
      style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--r);color:var(--text);padding:.58rem 1rem .58rem 2.2rem;font-size:.86rem;width:235px;outline:none;font-family:'Crimson Pro',serif"
      onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='var(--border)'">
  </div>
  <x-card-select name="review" :selected="request('review', '')" :options="[
    ['value' => '', 'label' => __('All'), 'icon' => 'fa-list'],
    ['value' => 'pending', 'label' => __('In Review'), 'icon' => 'fa-clock'],
    ['value' => 'approved', 'label' => __('Approved'), 'icon' => 'fa-circle-check'],
    ['value' => 'rejected', 'label' => __('Rejected'), 'icon' => 'fa-xmark'],
  ]" />
  @if(request('search') || request('review') || request('status'))
  <a href="{{ route('admin.tilawat.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-xmark"></i> Clear</a>
  @endif
</form>
<script>
  document.querySelectorAll('#tilawat-filter input[name="review"]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });
</script>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>Tilawa</th><th>Qari</th><th>Duration</th><th>Status</th><th>Review</th><th>Featured</th><th>Stats</th><th>Actions</th></tr></thead>
    <tbody>
      @forelse($tilawat as $t)
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:.72rem">
            <img src="{{ $t->cover_url }}" style="width:36px;height:36px;border-radius:7px;object-fit:cover;flex-shrink:0" alt="">
            <span style="font-weight:600;font-size:.88rem">{{ $t->title }}</span>
          </div>
        </td>
        <td style="color:var(--gold);font-size:.85rem">{{ $t->qari->name }}</td>
        <td style="font-size:.8rem;color:var(--text2);font-family:monospace">{{ $t->formatted_duration }}</td>
        <td>
          @role('admin')
          <form method="POST" action="{{ route('admin.tilawat.quick-update',$t) }}" style="display:inline">
            @csrf @method('PUT')
            <select name="status" onchange="this.form.submit()"
              style="background:var(--surface2);border:1px solid var(--border);border-radius:7px;padding:.26rem .55rem;color:var(--text);font-size:.8rem;cursor:pointer">
              <option value="active"   {{ $t->status==='active'  ?'selected':'' }}>Active</option>
              <option value="pending"  {{ $t->status==='pending' ?'selected':'' }}>Pending</option>
              <option value="inactive" {{ $t->status==='inactive'?'selected':'' }}>Inactive</option>
            </select>
          </form>
          @else
          <span class="badge {{ $t->status==='active'?'badge-green':($t->status==='pending'?'badge-amber':'badge-muted') }}">{{ ucfirst($t->status) }}</span>
          @endrole
        </td>
        <td>
          @php($reviewBadge = ['pending'=>'badge-amber','approved'=>'badge-green','rejected'=>'badge-red'][$t->review_status] ?? 'badge-muted')
          @php($reviewLabel = ['pending'=>__('In Review'),'approved'=>__('Approved'),'rejected'=>__('Rejected')][$t->review_status] ?? $t->review_status)
          <span class="badge {{ $reviewBadge }}">{{ $reviewLabel }}</span>
          @if($t->review_status === 'rejected' && $t->rejection_note)
          <div style="font-size:.74rem;color:#e86060;margin-top:.35rem;max-width:220px" title="{{ $t->rejection_note }}">
            <i class="fas fa-comment-dots"></i> {{ Str::limit($t->rejection_note, 60) }}
          </div>
          @endif
        </td>
        <td>
          @role('admin')
          <form method="POST" action="{{ route('admin.tilawat.quick-update',$t) }}">
            @csrf @method('PUT')
            <input type="hidden" name="is_featured" value="{{ $t->is_featured ? '0':'1' }}">
            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:1.05rem;color:{{ $t->is_featured?'var(--gold)':'var(--text3)' }}" title="Toggle featured">
              <i class="fas fa-star"></i>
            </button>
          </form>
          @else
          <i class="fas fa-star" style="font-size:1.05rem;color:{{ $t->is_featured?'var(--gold)':'var(--text3)' }}"></i>
          @endrole
        </td>
        <td>
          <div style="font-size:.76rem;color:var(--text2);display:flex;flex-direction:column;gap:.12rem">
            <span><i class="fas fa-heart" style="color:var(--gold)"></i> {{ number_format($t->likes_count) }}</span>
            <span><i class="fas fa-download"></i> {{ number_format($t->downloads_count) }}</span>
          </div>
        </td>
        <td>
          <div style="display:flex;gap:.18rem;align-items:center">
            @if(in_array($t->upload_status, ['pending','uploading']))
              <a href="{{ route('admin.tilawat.uploading', $t) }}" class="btn-icon" title="Upload in progress" style="color:#3b82f6">
                <i class="fas fa-cloud-arrow-up"></i>
              </a>
            @else
              <button class="btn-icon" title="Preview"
                onclick="playTilawa({{ $t->id }},'{{ $t->audio_url }}',{{ json_encode($t->title) }},{{ json_encode($t->qari->name) }},'{{ $t->cover_url }}',{{ $t->duration }},'{{ route('tilawa.download', $t) }}')">
                <i class="fas fa-play"></i>
              </button>
            @endif
            @if($t->review_status === 'rejected')
            <a href="{{ route('admin.tilawat.edit',$t) }}" class="btn btn-primary btn-xs" title="{{ __('Fix & Resubmit') }}"><i class="fas fa-paper-plane"></i> {{ __('Fix') }}</a>
            @else
            <a href="{{ route('admin.tilawat.edit',$t) }}" class="btn-icon" title="Edit"><i class="fas fa-pen-to-square"></i></a>
            @endif
            <form method="POST" action="{{ route('admin.tilawat.destroy',$t) }}" onsubmit="return confirm('Delete {{ addslashes($t->title) }}?')">
              @csrf @method('DELETE')
              <button type="submit" class="btn-icon" style="color:var(--red)" title="Delete"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="8" style="text-align:center;padding:3rem;color:var(--text2)">
        <i class="fas fa-music-slash" style="font-size:1.5rem;display:block;margin-bottom:.5rem"></i> No tilawat found.
      </td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div>{{ $tilawat->links('vendor.pagination.custom') }}</div>
@endsection
