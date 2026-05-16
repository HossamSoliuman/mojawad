@extends('layouts.admin')
@section('title','Uploading Tilawa')
@section('page-title','Uploading to Archive.org')
@section('breadcrumb')<a href="{{ route('admin.dashboard') }}">Dashboard</a> › <a href="{{ route('admin.tilawat.index') }}">Tilawat</a> › Uploading @endsection
@section('content')

<div style="max-width:600px;margin:0 auto;padding:2rem 0">

  <div style="background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:2.5rem;text-align:center">

    <div style="font-size:1.1rem;font-weight:600;color:var(--text1);margin-bottom:.4rem" id="status-title">
      Uploading to Archive.org
    </div>
    <div style="font-size:.875rem;color:var(--text2);margin-bottom:2rem" id="status-subtitle">
      <strong>{{ $tilawa->title }}</strong> is being uploaded in the background.
      This page will update automatically.
    </div>

    <div style="background:var(--bg);border-radius:99px;height:12px;overflow:hidden;margin-bottom:1.25rem;border:1px solid var(--border)">
      <div id="progress-bar" style="height:100%;width:15%;border-radius:99px;background:linear-gradient(90deg,var(--gold),#f97316);transition:width .6s ease;position:relative;overflow:hidden">
        <div id="shimmer" style="position:absolute;inset:0;background:linear-gradient(90deg,transparent 0%,rgba(255,255,255,.35) 50%,transparent 100%);animation:shimmer 1.4s infinite"></div>
      </div>
    </div>

    <div id="status-badge" style="display:inline-flex;align-items:center;gap:.45rem;font-size:.8rem;font-weight:600;padding:.35rem .9rem;border-radius:99px;background:rgba(var(--gold-rgb),.12);color:var(--gold)">
      <i class="fas fa-circle-notch fa-spin"></i> Pending
    </div>

    <div id="error-box" style="display:none;margin-top:1.5rem;background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.25);border-radius:10px;padding:1rem;color:#ef4444;font-size:.85rem;text-align:left"></div>

    <div style="margin-top:2rem;display:flex;gap:.65rem;justify-content:center">
      <a href="{{ route('admin.tilawat.index') }}" class="btn btn-ghost" style="font-size:.85rem">
        <i class="fas fa-list"></i> Back to List
      </a>
      <a href="{{ route('admin.tilawat.edit', $tilawa) }}" class="btn btn-ghost" style="font-size:.85rem" id="edit-link">
        <i class="fas fa-pen"></i> Edit Details
      </a>
    </div>
  </div>

</div>

<style>
@keyframes shimmer {
  0%   { transform: translateX(-100%); }
  100% { transform: translateX(200%); }
}
</style>

<script>
(function () {
  const statusUrl = '{{ route('admin.tilawat.upload-status', $tilawa) }}';
  const indexUrl  = '{{ route('admin.tilawat.index') }}';

  const bar       = document.getElementById('progress-bar');
  const shimmer   = document.getElementById('shimmer');
  const badge     = document.getElementById('status-badge');
  const title     = document.getElementById('status-title');
  const subtitle  = document.getElementById('status-subtitle');
  const errorBox  = document.getElementById('error-box');

  const states = {
    pending:   { width: '15%',  label: 'Pending',   icon: 'fa-circle-notch fa-spin', color: 'var(--gold)',   bg: 'rgba(234,179,8,.12)' },
    uploading: { width: '65%',  label: 'Uploading', icon: 'fa-cloud-arrow-up',        color: '#3b82f6',       bg: 'rgba(59,130,246,.12)' },
    done:      { width: '100%', label: 'Done',       icon: 'fa-circle-check',          color: 'var(--green)',  bg: 'rgba(34,197,94,.12)' },
    failed:    { width: '100%', label: 'Failed',     icon: 'fa-circle-xmark',          color: '#ef4444',       bg: 'rgba(239,68,68,.12)'  },
  };

  function applyState(s) {
    const cfg = states[s] || states.pending;
    bar.style.width = cfg.width;
    badge.innerHTML = `<i class="fas ${cfg.icon}"></i> ${cfg.label}`;
    badge.style.color      = cfg.color;
    badge.style.background = cfg.bg;
    if (s === 'done' || s === 'failed') {
      shimmer.style.animation = 'none';
    }
  }

  function poll() {
    fetch(statusUrl)
      .then(r => r.json())
      .then(data => {
        applyState(data.status);

        if (data.status === 'done') {
          title.textContent  = 'Upload Complete!';
          subtitle.innerHTML = 'Redirecting to tilawat list…';
          setTimeout(() => { window.location.href = indexUrl + '?uploaded=1'; }, 1800);
          return;
        }

        if (data.status === 'failed') {
          title.textContent    = 'Upload Failed';
          subtitle.textContent = 'The file could not be uploaded to Archive.org.';
          errorBox.style.display = 'block';
          errorBox.textContent   = data.error || 'Unknown error.';
          return;
        }

        if (data.status === 'uploading') {
          title.textContent   = 'Uploading to Archive.org…';
          subtitle.textContent = 'Please wait, this can take a few minutes for large files.';
        }

        setTimeout(poll, 3000);
      })
      .catch(() => setTimeout(poll, 5000));
  }

  poll();
})();
</script>

@endsection
