<div>

  <div style="margin-bottom:1.4rem">
    <h2 style="font-size:1.1rem;font-weight:700;margin:0 0 .25rem">{{ __('Import from library') }}</h2>
    <p style="color:var(--text2);font-size:.86rem;margin:0">{{ __('Pick a reciter, choose one of their folders, and every audio file inside is queued for cleaning & ingest — no uploads needed.') }}</p>
  </div>

  @if($this->imported !== null)
  <div class="alert alert-success" style="margin-bottom:1.25rem">
    <i class="fas fa-circle-check"></i>
    @if($this->imported > 0)
      {{ trans_choice('Queued :count file for cleaning & ingest.|Queued :count files for cleaning & ingest.', $this->imported, ['count' => $this->imported]) }}
    @else
      {{ __('Every file in that folder is already imported.') }}
    @endif
  </div>
  @endif

  {{-- STEP 1 · QARI --}}
  <section class="fi-card">
    <div class="fi-step"><span class="fi-step-no">1</span> {{ __('Choose the reciter') }}</div>

    @if($this->selectedQari)
      <div class="fi-qari-card">
        <img src="{{ $this->selectedQari['image'] }}" alt="" width="54" height="54">
        <div style="flex:1;min-width:0">
          <div class="fi-qari-name">{{ $this->selectedQari['name'] }}</div>
          <div class="fi-qari-sub"><i class="fas fa-thumbtack"></i> {{ __('Files will be imported under this reciter') }}</div>
        </div>
        <button type="button" class="btn btn-ghost" wire:click="clearQari"><i class="fas fa-rotate"></i> {{ __('Change') }}</button>
      </div>
    @else
      <div class="fi-search">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" wire:model.live.debounce.200ms="qariSearch" placeholder="{{ __('Search qari by name…') }}" autocomplete="off">
      </div>
      <div class="fi-qari-grid">
        @forelse($this->qaris->filter(fn($q) => $qariSearch === '' || str_contains(mb_strtolower($q['name']), mb_strtolower($qariSearch))) as $q)
          <button type="button" class="fi-qari-opt" wire:key="qari-{{ $q['id'] }}" wire:click="selectQari({{ $q['id'] }})" title="{{ $q['name'] }}">
            <img src="{{ $q['image'] }}" alt=""><span>{{ $q['name'] }}</span>
          </button>
        @empty
          <div class="fi-empty">{{ __('No qari found.') }}</div>
        @endforelse
      </div>
    @endif
    @error('qariId')<span class="form-error" style="display:block;margin-top:.5rem">{{ $message }}</span>@enderror
  </section>

  {{-- STEP 2 · FOLDER --}}
  <section class="fi-card @if(! $this->selectedQari) fi-locked @endif">
    <div class="fi-step"><span class="fi-step-no">2</span> {{ __('Choose a folder to import') }}</div>

    @if(empty($this->folders))
      <div class="fi-empty" style="padding:1.4rem">
        <i class="fas fa-folder-open" style="opacity:.4"></i>
        {{ __('No recitation folders found in the library.') }}
      </div>
    @else
      <div class="fi-folder-grid">
        @foreach($this->folders as $f)
          <button type="button" wire:key="folder-{{ $loop->index }}"
                  class="fi-folder @if($folder === $f['name']) is-active @endif"
                  wire:click="selectFolder(@js($f['name']))"
                  @if(! $this->selectedQari) disabled @endif>
            <i class="fas fa-folder"></i>
            <span class="fi-folder-name">{{ $f['name'] }}</span>
            <span class="badge badge-muted">{{ trans_choice(':count file|:count files', $f['count'], ['count' => $f['count']]) }}</span>
          </button>
        @endforeach
      </div>
    @endif
    @error('folder')<span class="form-error" style="display:block;margin-top:.5rem">{{ $message }}</span>@enderror

    @if(! $this->selectedQari)
      <div class="fi-lock-hint"><i class="fas fa-lock"></i> {{ __('Select a reciter first') }}</div>
    @endif
  </section>

  <button type="button" class="btn btn-primary" wire:click="import" wire:loading.attr="disabled"
          @disabled(! $this->selectedQari || ! $folder)>
    <span wire:loading.remove wire:target="import"><i class="fas fa-industry"></i> {{ __('Import folder') }}</span>
    <span wire:loading wire:target="import"><i class="fas fa-circle-notch fa-spin"></i> {{ __('Queuing…') }}</span>
  </button>

  <style>
    .fi-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:1.3rem 1.4rem;margin-bottom:1.1rem}
    .fi-card.fi-locked{opacity:.65}
    .fi-step{display:flex;align-items:center;gap:.6rem;font-weight:600;color:var(--text1);font-size:.98rem;margin-bottom:1.05rem}
    .fi-step-no{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:rgba(var(--gold-rgb),.15);color:var(--gold);font-size:.8rem;font-weight:700}

    .fi-search{display:flex;align-items:center;gap:.6rem;background:var(--bg);border:1.5px solid var(--border);border-radius:10px;padding:.6rem .9rem}
    .fi-search i{color:var(--text2)}
    .fi-search input{flex:1;background:none;border:none;outline:none;color:var(--text1);font-size:.92rem;font-family:inherit}
    .fi-qari-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:.55rem;margin-top:.85rem;max-height:320px;overflow-y:auto}
    .fi-qari-opt{display:flex;align-items:center;gap:.65rem;padding:.5rem .65rem;border:1.5px solid var(--border);border-radius:10px;background:var(--bg);cursor:pointer;transition:.15s;text-align:start}
    .fi-qari-opt:hover{border-color:var(--gold);transform:translateY(-1px)}
    .fi-qari-opt img{width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0}
    .fi-qari-opt span{font-size:.86rem;color:var(--text1);font-weight:500;line-height:1.25;min-width:0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}

    .fi-qari-card{display:flex;align-items:center;gap:.9rem;padding:.8rem 1rem;border:1.5px solid var(--gold);border-radius:12px;background:rgba(var(--gold-rgb),.06)}
    .fi-qari-card img{width:54px;height:54px;border-radius:50%;object-fit:cover;flex-shrink:0}
    .fi-qari-name{font-size:1.02rem;font-weight:700;color:var(--text1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .fi-qari-sub{font-size:.75rem;color:var(--text2);margin-top:.15rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .fi-qari-sub i{color:var(--gold)}

    .fi-folder-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.6rem}
    .fi-folder{display:flex;align-items:center;gap:.65rem;padding:.75rem .85rem;border:1.5px solid var(--border);border-radius:11px;background:var(--bg);cursor:pointer;transition:.15s;text-align:start;font-family:inherit}
    .fi-folder:hover:not(:disabled){border-color:var(--gold);transform:translateY(-1px)}
    .fi-folder:disabled{cursor:not-allowed}
    .fi-folder.is-active{border-color:var(--gold);background:rgba(var(--gold-rgb),.08);box-shadow:0 0 0 1px var(--gold) inset}
    .fi-folder > i{color:var(--gold);font-size:1.05rem;flex-shrink:0}
    .fi-folder-name{flex:1;min-width:0;font-size:.86rem;color:var(--text1);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

    .fi-empty{grid-column:1/-1;text-align:center;color:var(--text2);font-size:.86rem;padding:1rem;display:flex;flex-direction:column;gap:.5rem;align-items:center}
    .fi-empty i{font-size:1.6rem}
    .fi-lock-hint{margin-top:.85rem;font-size:.82rem;color:var(--red);font-weight:600}
    .fi-lock-hint i{margin-inline-end:.3rem}
  </style>
</div>
