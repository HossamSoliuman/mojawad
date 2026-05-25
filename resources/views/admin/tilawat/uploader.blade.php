@extends('layouts.uploader')
@section('title', __('Upload Tilawat'))
@section('content')

<div class="up-head">
  <h1>{{ __('Upload Tilawat') }}</h1>
  <p>{{ __('Pick a reciter, drop your audio files, and they go live after a quick review.') }}</p>
</div>

{{-- STEP 1 · QARI --}}
<section class="up-card">
  <div class="up-step-lbl"><span class="up-step-no">1</span> {{ __('Choose the reciter') }}</div>

  <div id="qariSearchWrap">
    <div class="qari-search">
      <i class="fas fa-magnifying-glass"></i>
      <input type="text" id="qariSearch" placeholder="{{ __('Search qari by name…') }}" autocomplete="off">
    </div>
    <div class="qari-results" id="qariResults"></div>
  </div>

  <div id="qariCardWrap" style="display:none">
    <div class="qari-card">
      <img id="qariCardImg" src="" alt="" width="54" height="54">
      <div class="qari-card-info">
        <div class="qari-card-name" id="qariCardName"></div>
        <div class="qari-card-sub"><i class="fas fa-thumbtack"></i> {{ __('Default reciter — used for every upload until you change it') }}</div>
      </div>
      <button type="button" class="btn btn-ghost" id="qariChangeBtn"><i class="fas fa-rotate"></i> {{ __('Change') }}</button>
    </div>
  </div>
</section>

{{-- STEP 2 · TITLE MODE --}}
<section class="up-card">
  <div class="up-step-lbl"><span class="up-step-no">2</span> {{ __('How should files be named?') }}</div>
  <div class="mode-cards">
    <button type="button" class="mode-card" data-mode="filename">
      <i class="fas fa-file-signature"></i>
      <div><strong>{{ __('Use file name') }}</strong><span>{{ __('Auto-saves each file instantly') }}</span></div>
    </button>
    <button type="button" class="mode-card" data-mode="manual">
      <i class="fas fa-keyboard"></i>
      <div><strong>{{ __('Type a name') }}</strong><span>{{ __('Name each file, then save') }}</span></div>
    </button>
  </div>
</section>

{{-- STEP 3 · FILES --}}
<section class="up-card">
  <div class="up-step-lbl"><span class="up-step-no">3</span> {{ __('Drop your audio files') }}</div>

  <div class="dropzone" id="dropzone">
    <input type="file" id="fileInput" multiple accept="audio/mpeg,audio/mp3,audio/ogg,audio/wav,.mp3,.ogg,.wav" hidden>
    <i class="fas fa-cloud-arrow-up"></i>
    <div class="dz-title">{{ __('Drag & drop audio files here') }}</div>
    <div class="dz-sub">{{ __('or') }} <span class="dz-browse">{{ __('browse') }}</span> · {{ __('MP3, OGG, WAV · max 200MB each') }}</div>
    <div class="dz-lock" id="dzLock"><i class="fas fa-lock"></i> {{ __('Select a reciter first') }}</div>
  </div>

  <div class="queue" id="queue"></div>

  <div id="saveAllWrap" style="display:none;margin-top:1rem">
    <button type="button" class="btn btn-primary" id="saveAllBtn"><i class="fas fa-floppy-disk"></i> {{ __('Save all named files') }}</button>
  </div>
</section>

<div class="up-foot"><i class="fas fa-circle-info"></i> {{ __('Every upload is submitted for review before appearing on the site.') }}</div>

<style>
.up-shell{max-width:760px;margin:0 auto;padding:0 1rem 6rem}
.up-topbar{display:flex;align-items:center;justify-content:space-between;padding:1.1rem 0;margin-bottom:.5rem}
.up-user{display:flex;align-items:center;gap:.45rem}
.up-user-name{font-size:.82rem;color:var(--text2)}
.up-head{text-align:center;margin:1rem 0 2rem}
.up-head h1{font-size:1.75rem;color:var(--text1);margin:0 0 .35rem;font-weight:700}
.up-head p{color:var(--text2);font-size:.92rem;margin:0}
.up-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:1.4rem 1.5rem;margin-bottom:1.15rem}
.up-step-lbl{display:flex;align-items:center;gap:.6rem;font-weight:600;color:var(--text1);font-size:1rem;margin-bottom:1.1rem}
.up-step-no{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:rgba(var(--gold-rgb),.15);color:var(--gold);font-size:.85rem;font-weight:700}
.up-foot{text-align:center;color:var(--text2);font-size:.82rem;margin-top:.5rem}
.up-foot i{color:var(--gold)}

/* Qari search */
.qari-search{display:flex;align-items:center;gap:.6rem;background:var(--bg);border:1.5px solid var(--border);border-radius:11px;padding:.7rem .95rem}
.qari-search i{color:var(--text2)}
.qari-search input{flex:1;background:none;border:none;outline:none;color:var(--text1);font-size:.95rem;font-family:inherit}
.qari-results{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:.55rem;margin-top:.85rem;max-height:340px;overflow-y:auto}
.qari-opt{display:flex;align-items:center;gap:.65rem;padding:.5rem .65rem;border:1.5px solid var(--border);border-radius:11px;background:var(--bg);cursor:pointer;transition:.15s;text-align:start}
.qari-opt:hover{border-color:var(--gold);transform:translateY(-1px)}
.qari-opt img{width:42px;height:42px;border-radius:50%;object-fit:cover;flex-shrink:0}
.qari-opt span{font-size:.88rem;color:var(--text1);font-weight:500;line-height:1.25}
.qari-empty{grid-column:1/-1;text-align:center;color:var(--text2);font-size:.85rem;padding:1rem}

/* Qari card */
.qari-card{display:flex;align-items:center;gap:.9rem;padding:.85rem 1rem;border:1.5px solid var(--gold);border-radius:13px;background:rgba(var(--gold-rgb),.06)}
.qari-card img{width:54px;height:54px;border-radius:50%;object-fit:cover;flex-shrink:0}
.qari-card-info{flex:1;min-width:0}
.qari-card-name{font-size:1.05rem;font-weight:700;color:var(--text1)}
.qari-card-sub{font-size:.76rem;color:var(--text2);margin-top:.15rem}
.qari-card-sub i{color:var(--gold)}

/* Mode cards */
.mode-cards{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.mode-card{display:flex;align-items:center;gap:.8rem;padding:1rem 1.1rem;border:1.5px solid var(--border);border-radius:13px;background:var(--bg);cursor:pointer;transition:.15s;text-align:start;font-family:inherit}
.mode-card:hover{border-color:var(--gold)}
.mode-card.active{border-color:var(--gold);background:rgba(var(--gold-rgb),.08)}
.mode-card i{font-size:1.35rem;color:var(--gold)}
.mode-card strong{display:block;font-size:.95rem;color:var(--text1)}
.mode-card span{display:block;font-size:.75rem;color:var(--text2);margin-top:.12rem}

/* Dropzone */
.dropzone{position:relative;border:2px dashed var(--border);border-radius:14px;padding:2.4rem 1rem;text-align:center;cursor:pointer;transition:.18s}
.dropzone:hover{border-color:var(--gold)}
.dropzone.drag{border-color:var(--gold);background:rgba(var(--gold-rgb),.07)}
.dropzone > i{font-size:2.3rem;color:var(--gold);margin-bottom:.7rem}
.dz-title{font-size:1.02rem;font-weight:600;color:var(--text1)}
.dz-sub{font-size:.82rem;color:var(--text2);margin-top:.3rem}
.dz-browse{color:var(--gold);text-decoration:underline}
.dropzone.locked{cursor:not-allowed;opacity:.55}
.dropzone.locked .dz-title,.dropzone.locked .dz-sub,.dropzone.locked>i{filter:grayscale(1)}
.dz-lock{display:none;margin-top:.8rem;font-size:.82rem;color:var(--red);font-weight:600}
.dropzone.locked .dz-lock{display:block}

/* Queue */
.queue{margin-top:1.1rem;display:flex;flex-direction:column;gap:.6rem}
.qrow{border:1px solid var(--border);border-radius:11px;padding:.7rem .85rem;background:var(--bg)}
.qrow-main{display:flex;align-items:center;gap:.75rem}
.qrow-ic{color:var(--gold);font-size:1.05rem;flex-shrink:0}
.qrow-body{flex:1;min-width:0}
.qrow-name{font-size:.88rem;color:var(--text1);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.qrow-title{width:100%;background:var(--surface);border:1.5px solid var(--border);border-radius:8px;padding:.4rem .6rem;color:var(--text1);font-size:.88rem;font-family:inherit;outline:none}
.qrow-title:focus{border-color:var(--gold)}
.qrow-bar{height:5px;border-radius:99px;background:var(--border);overflow:hidden;margin-top:.5rem}
.qrow-fill{height:100%;width:0;background:linear-gradient(90deg,var(--gold),#f97316);transition:width .25s}
.qrow-status{font-size:.76rem;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:.35rem}
.qrow-x{background:none;border:none;color:var(--text2);cursor:pointer;font-size:1rem;padding:.2rem .4rem;border-radius:6px}
.qrow-x:hover{color:var(--red);background:rgba(239,68,68,.1)}
.qrow-save{font-size:.78rem;padding:.35rem .8rem}
.st-up{color:#3b82f6}.st-ok{color:var(--green)}.st-err{color:var(--red)}.st-wait{color:var(--text2)}
</style>

@push('scripts')
<script>
(function(){
  const CFG = {
    tmpUrl:    @json(route('admin.upload.tmp')) + '?type=audio',
    tmpBase:   @json(url('/admin/upload/tmp')),
    quickStore:@json(route('admin.tilawat.quick-store')),
    setQari:   @json(route('admin.uploader.qari')),
    csrf:      document.querySelector('meta[name="csrf-token"]').content,
    maxSize:   200 * 1024 * 1024,
    exts:      ['mp3','ogg','wav','mpeg'],
    t: {
      noQari:      @json(__('No qari found.')),
      badType:     @json(__('Unsupported file type')),
      tooBig:      @json(__('File exceeds 200MB')),
      title:       @json(__('Title')),
      remove:      @json(__('Remove')),
      uploading:   @json(__('Uploading…')),
      save:        @json(__('Save')),
      saving:      @json(__('Saving…')),
      saved:       @json(__('Saved')),
      networkErr:  @json(__('Network error')),
      saveFailed:  @json(__('Save failed')),
    },
  };

  let currentQari = @json($defaultQari);
  let titleMode   = @json($titleMode);

  const QARIS = @json($qaris);

  const $ = id => document.getElementById(id);
  const searchWrap = $('qariSearchWrap'), cardWrap = $('qariCardWrap'),
        searchInput = $('qariSearch'), results = $('qariResults'),
        cardImg = $('qariCardImg'), cardName = $('qariCardName'),
        dropzone = $('dropzone'), fileInput = $('fileInput'),
        queue = $('queue'), saveAllWrap = $('saveAllWrap');

  const rows = new Map();
  const stripExt = n => n.replace(/\.[^/.]+$/, '');

  /* ---------- Qari picker ---------- */
  function renderResults(filter){
    const f = (filter||'').trim().toLowerCase();
    const list = f ? QARIS.filter(q => q.name.toLowerCase().includes(f)) : QARIS;
    if(!list.length){ results.innerHTML = `<div class="qari-empty">${escapeHtml(CFG.t.noQari)}</div>`; return; }
    results.innerHTML = list.map(q =>
      `<button type="button" class="qari-opt" data-id="${q.id}">
         <img src="${q.image}" alt=""><span>${q.name}</span>
       </button>`).join('');
  }

  results.addEventListener('click', e => {
    const btn = e.target.closest('.qari-opt');
    if(btn) selectQari(QARIS.find(q => q.id == btn.dataset.id));
  });
  searchInput.addEventListener('input', () => renderResults(searchInput.value));

  function showCard(q){
    cardImg.src = q.image; cardName.textContent = q.name;
    searchWrap.style.display = 'none'; cardWrap.style.display = 'block';
    updateLock();
  }
  function showSearch(){
    cardWrap.style.display = 'none'; searchWrap.style.display = 'block';
    searchInput.value = ''; renderResults(''); searchInput.focus(); updateLock();
  }

  function selectQari(q){
    if(!q) return;
    currentQari = q;
    showCard(q);
    fetch(CFG.setQari, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CFG.csrf,'Accept':'application/json','Content-Type':'application/json'},
      body: JSON.stringify({ qari_id: q.id })
    }).catch(()=>{});
  }

  $('qariChangeBtn').addEventListener('click', showSearch);

  /* ---------- Title mode ---------- */
  function applyMode(mode){
    titleMode = mode;
    document.querySelectorAll('.mode-card').forEach(c =>
      c.classList.toggle('active', c.dataset.mode === mode));
  }
  document.querySelectorAll('.mode-card').forEach(c => c.addEventListener('click', () => {
    applyMode(c.dataset.mode);
    fetch(CFG.setQari, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CFG.csrf,'Accept':'application/json','Content-Type':'application/json'},
      body: JSON.stringify({ title_mode: titleMode })
    }).catch(()=>{});
    refreshSaveAll();
  }));

  /* ---------- Lock ---------- */
  function updateLock(){
    const locked = !currentQari;
    dropzone.classList.toggle('locked', locked);
  }

  /* ---------- Dropzone ---------- */
  dropzone.addEventListener('click', () => { if(currentQari) fileInput.click(); });
  ['dragover','dragenter'].forEach(ev => dropzone.addEventListener(ev, e => {
    e.preventDefault(); if(currentQari) dropzone.classList.add('drag');
  }));
  ['dragleave','drop'].forEach(ev => dropzone.addEventListener(ev, e => {
    e.preventDefault(); dropzone.classList.remove('drag');
  }));
  dropzone.addEventListener('drop', e => { if(currentQari) addFiles(e.dataTransfer.files); });
  fileInput.addEventListener('change', () => { addFiles(fileInput.files); fileInput.value=''; });

  function validFile(file){
    const ext = (file.name.split('.').pop() || '').toLowerCase();
    if(!CFG.exts.includes(ext)) return CFG.t.badType;
    if(file.size > CFG.maxSize)  return CFG.t.tooBig;
    return null;
  }

  function addFiles(fileList){
    if(!currentQari) return;
    [...fileList].forEach(file => {
      const uid = 'r' + Math.random().toString(36).slice(2);
      const err = validFile(file);
      const row = { uid, file, title: stripExt(file.name), token:null, status: err ? 'error' : 'queued' };
      rows.set(uid, row);
      renderRow(row, err);
      if(!err) processRow(row);
    });
  }

  function renderRow(row, err){
    const el = document.createElement('div');
    el.className = 'qrow'; el.dataset.uid = row.uid;
    const titleHtml = titleMode === 'manual'
      ? `<input class="qrow-title" value="${escapeAttr(row.title)}" placeholder="${escapeAttr(CFG.t.title)}">`
      : `<div class="qrow-name">${escapeHtml(row.title)}</div>`;
    el.innerHTML =
      `<div class="qrow-main">
         <i class="fas fa-music qrow-ic"></i>
         <div class="qrow-body">${titleHtml}<div class="qrow-bar"><div class="qrow-fill"></div></div></div>
         <div class="qrow-status"></div>
         <button type="button" class="qrow-x" title="${escapeAttr(CFG.t.remove)}">&times;</button>
       </div>`;
    queue.appendChild(el);
    el.querySelector('.qrow-x').addEventListener('click', () => removeRow(row.uid));
    const titleInput = el.querySelector('.qrow-title');
    if(titleInput) titleInput.addEventListener('input', e => { row.title = e.target.value; });
    if(err) setStatus(row.uid, 'err', `<i class="fas fa-circle-xmark"></i> ${err}`);
  }

  function setStatus(uid, cls, html){
    const el = queue.querySelector(`[data-uid="${uid}"] .qrow-status`);
    if(el) el.className = 'qrow-status st-' + cls, el.innerHTML = html;
  }
  function setProgress(uid, pct){
    const fill = queue.querySelector(`[data-uid="${uid}"] .qrow-fill`);
    if(fill) fill.style.width = pct + '%';
  }

  async function processRow(row){
    setStatus(row.uid, 'up', `<i class="fas fa-arrow-up-from-bracket"></i> ${escapeHtml(CFG.t.uploading)}`);
    try {
      row.token = await uploadToTmp(row.file, p => setProgress(row.uid, p));
    } catch(e){
      setStatus(row.uid, 'err', `<i class="fas fa-circle-xmark"></i> ${escapeHtml(e.message)}`);
      return;
    }
    setProgress(row.uid, 100);
    if(titleMode === 'manual'){
      row.status = 'ready';
      const stEl = queue.querySelector(`[data-uid="${row.uid}"] .qrow-status`);
      stEl.className = 'qrow-status';
      stEl.innerHTML = `<button type="button" class="btn btn-primary qrow-save">${escapeHtml(CFG.t.save)}</button>`;
      stEl.querySelector('.qrow-save').addEventListener('click', () => saveRow(row));
      refreshSaveAll();
    } else {
      saveRow(row);
    }
  }

  async function saveRow(row){
    if(!row.token) return;
    const title = (row.title || '').trim() || stripExt(row.file.name);
    setStatus(row.uid, 'up', `<i class="fas fa-circle-notch fa-spin"></i> ${escapeHtml(CFG.t.saving)}`);
    try {
      await createTilawa(row.token, title);
      row.status = 'saved';
      setStatus(row.uid, 'ok', `<i class="fas fa-circle-check"></i> ${escapeHtml(CFG.t.saved)}`);
      const x = queue.querySelector(`[data-uid="${row.uid}"] .qrow-x`);
      if(x) x.remove();
      const ti = queue.querySelector(`[data-uid="${row.uid}"] .qrow-title`);
      if(ti) ti.disabled = true;
    } catch(e){
      setStatus(row.uid, 'err', `<i class="fas fa-circle-xmark"></i> ${escapeHtml(e.message)}`);
    }
    refreshSaveAll();
  }

  function removeRow(uid){
    const row = rows.get(uid);
    if(row && row.token && row.status !== 'saved'){
      fetch(`${CFG.tmpBase}/${row.token}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':CFG.csrf}, keepalive:true }).catch(()=>{});
    }
    rows.delete(uid);
    const el = queue.querySelector(`[data-uid="${uid}"]`);
    if(el) el.remove();
    refreshSaveAll();
  }

  function refreshSaveAll(){
    const ready = [...rows.values()].filter(r => r.status === 'ready');
    saveAllWrap.style.display = (titleMode === 'manual' && ready.length > 1) ? 'block' : 'none';
  }
  $('saveAllBtn').addEventListener('click', () => {
    [...rows.values()].filter(r => r.status === 'ready').forEach(saveRow);
  });

  /* ---------- Network ---------- */
  function uploadToTmp(file, onProgress){
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', CFG.tmpUrl);
      xhr.setRequestHeader('X-CSRF-TOKEN', CFG.csrf);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.upload.onprogress = e => { if(e.lengthComputable) onProgress(Math.round(e.loaded/e.total*100)); };
      xhr.onload  = () => (xhr.status>=200 && xhr.status<300)
        ? resolve(xhr.responseText.trim())
        : reject(new Error(parseErr(xhr.responseText) || ('HTTP '+xhr.status)));
      xhr.onerror = () => reject(new Error(CFG.t.networkErr));
      const fd = new FormData(); fd.append('file', file);
      xhr.send(fd);
    });
  }
  async function createTilawa(token, title){
    const res = await fetch(CFG.quickStore, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CFG.csrf,'Accept':'application/json','Content-Type':'application/json'},
      body: JSON.stringify({ qari_id: currentQari.id, audio_tmp: token, title })
    });
    if(!res.ok){
      let msg = CFG.t.saveFailed;
      try { const j = await res.json(); msg = j.message || msg; } catch(_){}
      throw new Error(msg);
    }
    return res.json();
  }
  function parseErr(txt){ try { return JSON.parse(txt).message; } catch(_){ return txt; } }
  function escapeHtml(s){ const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
  function escapeAttr(s){ return escapeHtml(s).replace(/"/g,'&quot;'); }

  /* ---------- Init ---------- */
  renderResults('');
  applyMode(titleMode);
  if(currentQari) showCard(currentQari); else updateLock();
})();
</script>
@endpush
@endsection
