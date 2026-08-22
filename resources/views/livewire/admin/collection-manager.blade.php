<div>

  @if(session('collection-saved'))
  <div class="alert alert-success" style="margin-bottom:1.25rem">
    <i class="fas fa-circle-check"></i> {{ session('collection-saved') }}
  </div>
  @endif

  {{-- ── Editor ──────────────────────────────────────────────────────────── --}}
  @if($showForm)
  <form wire:submit="save" class="card cm-form">
    <div class="cm-form-hd">
      <h2><i class="fas fa-layer-group gold"></i> {{ $editingId ? __('Edit collection') : __('New collection') }}</h2>
      <button type="button" class="btn btn-ghost btn-sm" wire:click="cancel">
        <i class="fas fa-xmark"></i> {{ __('Cancel') }}
      </button>
    </div>

    <div class="cm-grid">
      <div class="form-group">
        <label class="form-label" for="cm-title-ar">{{ __('Arabic title') }} <span style="color:var(--red)">*</span></label>
        <input id="cm-title-ar" type="text" class="form-control" wire:model="title_ar">
        @error('title_ar')<span class="form-error">{{ $message }}</span>@enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="cm-title-en">{{ __('English title') }}</label>
        <input id="cm-title-en" type="text" class="form-control" dir="ltr" wire:model="title_en">
        @error('title_en')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="cm-desc">{{ __('Description') }}</label>
      <textarea id="cm-desc" class="form-control" rows="2" wire:model="description_ar"></textarea>
      @error('description_ar')<span class="form-error">{{ $message }}</span>@enderror
    </div>

    {{-- Manual vs auto --}}
    <div class="form-group">
      <label class="form-label">{{ __('How is this collection filled?') }}</label>
      <div class="cm-type">
        <button type="button" class="cm-type-opt{{ $type === 'manual' ? ' is-on' : '' }}" wire:click="$set('type','manual')">
          <i class="fas fa-hand-pointer"></i>
          <span>
            <strong>{{ __('Hand-picked') }}</strong>
            <small>{{ __('You choose every recitation and its order.') }}</small>
          </span>
        </button>
        <button type="button" class="cm-type-opt{{ $type === 'auto' ? ' is-on' : '' }}" wire:click="$set('type','auto')">
          <i class="fas fa-wand-magic-sparkles"></i>
          <span>
            <strong>{{ __('Automatic') }}</strong>
            <small>{{ __('Filled from live stats and refreshed on its own.') }}</small>
          </span>
        </button>
      </div>
    </div>

    @if($type === 'auto')
    <div class="cm-grid">
      <div class="form-group">
        <label class="form-label" for="cm-rule">{{ __('Rule') }} <span style="color:var(--red)">*</span></label>
        <select id="cm-rule" class="form-control" wire:model.live="auto_rule">
          @foreach($this->ruleOptions as $key => $rule)
          <option value="{{ $key }}">{{ $rule['label'] }}</option>
          @endforeach
        </select>
        <span class="form-hint">{{ $this->ruleOptions[$auto_rule]['hint'] ?? '' }}</span>
        @error('auto_rule')<span class="form-error">{{ $message }}</span>@enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="cm-limit">{{ __('How many recitations') }}</label>
        <input id="cm-limit" type="number" min="1" max="100" class="form-control" wire:model.live.debounce.400ms="auto_limit">
        @error('auto_limit')<span class="form-error">{{ $message }}</span>@enderror
      </div>
    </div>

    {{-- Live preview of what the rule resolves to right now --}}
    <div class="form-group">
      <label class="form-label"><i class="fas fa-eye"></i> {{ __('Preview') }}
        <span wire:loading wire:target="auto_rule,auto_limit"><i class="fas fa-circle-notch fa-spin"></i></span>
      </label>
      @if($this->autoPreview->isEmpty())
      <div class="cm-empty">{{ __('Nothing matches this rule yet.') }}</div>
      @else
      <ol class="cm-preview">
        @foreach($this->autoPreview as $t)
        <li>
          <span class="cm-preview-num">{{ $loop->iteration }}</span>
          <span class="cm-preview-title">{{ $t->title }}</span>
          <span class="cm-preview-qari">{{ $t->qari?->name }}</span>
          <span class="cm-preview-stat"><i class="fas fa-headphones"></i> {{ number_format($t->plays_count) }}</span>
          <span class="cm-preview-stat"><i class="fas fa-heart"></i> {{ number_format($t->likes_count) }}</span>
        </li>
        @endforeach
      </ol>
      @endif
    </div>
    @else
    {{-- Manual picker --}}
    <div class="form-group">
      <label class="form-label" for="cm-search"><i class="fas fa-magnifying-glass"></i> {{ __('Add recitations') }}</label>
      <input id="cm-search" type="text" class="form-control" placeholder="{{ __('Search by recitation or qari…') }}"
        wire:model.live.debounce.300ms="search" autocomplete="off">

      @if($this->searchResults->isNotEmpty())
      <div class="cm-results">
        @foreach($this->searchResults as $t)
        <button type="button" class="cm-result" wire:click="addTilawa({{ $t->id }})" wire:key="res-{{ $t->id }}">
          <i class="fas fa-plus gold"></i>
          <span class="cm-result-title">{{ $t->title }}</span>
          <span class="cm-result-qari">{{ $t->qari?->name }}</span>
        </button>
        @endforeach
      </div>
      @elseif(trim($search) !== '')
      <div class="cm-empty">{{ __('No recitations found.') }}</div>
      @endif
    </div>

    <div class="form-group">
      <label class="form-label">{{ __('In this collection') }} ({{ count($picked) }})</label>
      @if($this->pickedTilawat->isEmpty())
      <div class="cm-empty">{{ __('No recitations added yet.') }}</div>
      @else
      <ul class="cm-picked">
        @foreach($this->pickedTilawat as $i => $t)
        <li wire:key="picked-{{ $t->id }}">
          <span class="cm-preview-num">{{ $i + 1 }}</span>
          <img src="{{ $t->cover_url }}" alt="" class="cm-picked-img">
          <span class="cm-preview-title">{{ $t->title }}</span>
          <span class="cm-preview-qari">{{ $t->qari?->name }}</span>
          <button type="button" class="btn-icon" wire:click="moveUp({{ $i }})" @disabled($i === 0) title="{{ __('Move up') }}">
            <i class="fas fa-arrow-up"></i>
          </button>
          <button type="button" class="btn-icon" wire:click="moveDown({{ $i }})" @disabled($i === count($picked) - 1) title="{{ __('Move down') }}">
            <i class="fas fa-arrow-down"></i>
          </button>
          <button type="button" class="btn-icon" style="color:var(--red)" wire:click="removeTilawa({{ $t->id }})" title="{{ __('Remove') }}">
            <i class="fas fa-xmark"></i>
          </button>
        </li>
        @endforeach
      </ul>
      @endif
    </div>
    @endif

    <div class="cm-grid">
      <div class="form-group">
        <label class="form-label" for="cm-cover">{{ __('Cover image') }}</label>
        <input id="cm-cover" type="file" class="form-control" accept="image/*" wire:model="cover">
        <span class="form-hint">{{ __('Optional — falls back to the first recitation cover.') }}</span>
        @error('cover')<span class="form-error">{{ $message }}</span>@enderror
        @if($cover)
        <img src="{{ $cover->temporaryUrl() }}" alt="" class="cm-cover-preview">
        @elseif($currentCover)
        <img src="{{ asset('storage/'.$currentCover) }}" alt="" class="cm-cover-preview">
        @endif
      </div>

      <div class="form-group">
        <label class="form-label" for="cm-order">{{ __('Display order') }}</label>
        <input id="cm-order" type="number" min="0" max="65535" class="form-control" wire:model="sort_order">
        @error('sort_order')<span class="form-error">{{ $message }}</span>@enderror

        <label class="cm-check">
          <input type="checkbox" wire:model="is_active">
          <span>{{ __('Visible on the site') }}</span>
        </label>
      </div>
    </div>

    <div style="display:flex;gap:.6rem">
      <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save,cover">
        <i class="fas fa-floppy-disk" wire:loading.remove wire:target="save"></i>
        <i class="fas fa-circle-notch fa-spin" wire:loading wire:target="save"></i>
        {{ __('Save collection') }}
      </button>
      <button type="button" class="btn btn-ghost" wire:click="cancel">{{ __('Cancel') }}</button>
    </div>
  </form>
  @else
  <div style="margin-bottom:1.4rem">
    <button type="button" class="btn btn-primary" wire:click="create">
      <i class="fas fa-plus"></i> {{ __('New collection') }}
    </button>
  </div>
  @endif

  {{-- ── List ────────────────────────────────────────────────────────────── --}}
  <div class="cm-list">
    @forelse($this->collections as $c)
    <div class="cm-row{{ $c->is_active ? '' : ' is-off' }}" wire:key="col-{{ $c->id }}">
      <img src="{{ $c->cover_url }}" alt="" class="cm-row-img">

      <div class="cm-row-main">
        <div class="cm-row-title">
          {{ $c->title_ar }}
          @if($c->isAuto())
          <span class="badge badge-gold"><i class="fas {{ $c->icon_class }}"></i> {{ \App\Services\AutoCollectionResolver::label($c->auto_rule) ?? __('Automatic') }}</span>
          @else
          <span class="badge badge-muted"><i class="fas fa-hand-pointer"></i> {{ __('Hand-picked') }}</span>
          @endif
        </div>
        <div class="cm-row-meta">
          <span><i class="fas fa-music"></i> {{ $c->itemsCount() }} {{ __('tilawat') }}</span>
          <span><i class="fas fa-arrow-down-1-9"></i> {{ __('Order') }} {{ $c->sort_order }}</span>
          <span dir="ltr">/collections/{{ $c->slug }}</span>
        </div>
      </div>

      <button type="button" class="status-toggle{{ $c->is_active ? ' is-on' : '' }}" role="switch"
        aria-checked="{{ $c->is_active ? 'true' : 'false' }}" wire:click="toggleActive({{ $c->id }})"
        title="{{ $c->is_active ? __('Hide') : __('Show') }}">
        <span class="status-toggle-track"><span class="status-toggle-knob"></span></span>
        <span class="status-toggle-label">{{ $c->is_active ? __('Active') : __('Not Active') }}</span>
      </button>

      <div class="cm-row-actions">
        <a href="{{ route('collections.show', $c) }}" target="_blank" class="btn-icon" title="{{ __('View Site') }}">
          <i class="fas fa-arrow-up-right-from-square"></i>
        </a>
        <button type="button" class="btn-icon" wire:click="edit({{ $c->id }})" title="{{ __('Edit') }}">
          <i class="fas fa-pen-to-square"></i>
        </button>
        <button type="button" class="btn-icon" style="color:var(--red)" wire:click="confirmDelete({{ $c->id }})" title="{{ __('Delete') }}">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </div>
    @empty
    <div class="cm-empty" style="padding:3rem;text-align:center">
      <i class="fas fa-layer-group" style="font-size:1.6rem;display:block;margin-bottom:.5rem;opacity:.4"></i>
      {{ __('No collections yet.') }}
    </div>
    @endforelse
  </div>

  {{-- Delete confirmation — an in-page modal, never the browser confirm() dialog --}}
  @if($confirmingDeleteId !== null)
  <div class="modal-backdrop" wire:click.self="cancelDelete" x-data @keydown.escape.window="$wire.cancelDelete()">
    <div class="modal" style="max-width:520px">
      <div class="modal-title"><i class="fas fa-trash" style="color:var(--red)"></i> {{ __('Confirm deletion') }}</div>
      <p style="color:var(--text2);font-size:.9rem;margin:0 0 1.4rem">
        {{ __('Delete this collection? The recitations themselves are not removed.') }}
      </p>
      <div style="display:flex;gap:.6rem">
        <button type="button" wire:click="cancelDelete" class="btn btn-ghost" style="flex:1;justify-content:center">{{ __('Cancel') }}</button>
        <button type="button" wire:click="performDelete" class="btn cm-delete" style="flex:1;justify-content:center"
          wire:loading.attr="disabled" wire:target="performDelete">
          <i class="fas fa-trash" wire:loading.remove wire:target="performDelete"></i>
          <i class="fas fa-circle-notch fa-spin" wire:loading wire:target="performDelete"></i>
          {{ __('Delete') }}
        </button>
      </div>
    </div>
  </div>
  @endif

  <style>
    .cm-form{padding:1.5rem;margin-bottom:2rem}
    .cm-form-hd{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.4rem}
    .cm-form-hd h2{font-size:1.05rem;font-weight:700;margin:0}
    .cm-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:0 1rem}
    .cm-type{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.6rem}
    .cm-type-opt{display:flex;align-items:flex-start;gap:.7rem;padding:.85rem 1rem;text-align:start;border:1px solid var(--border);border-radius:var(--r2);background:none;color:var(--text1);cursor:pointer;font-family:inherit;transition:.15s}
    .cm-type-opt:hover{border-color:var(--border2)}
    .cm-type-opt.is-on{border-color:var(--gold);background:color-mix(in srgb,var(--gold) 8%,transparent)}
    .cm-type-opt i{color:var(--gold);margin-top:.15rem}
    .cm-type-opt strong{display:block;font-size:.9rem}
    .cm-type-opt small{display:block;color:var(--text2);font-size:.76rem;margin-top:.15rem}
    .cm-results{margin-top:.5rem;border:1px solid var(--border);border-radius:var(--r2);max-height:270px;overflow:auto}
    .cm-result{display:flex;align-items:center;gap:.6rem;width:100%;padding:.55rem .75rem;border:0;border-bottom:1px solid var(--border);background:none;color:var(--text1);cursor:pointer;font-family:inherit;font-size:.84rem;text-align:start}
    .cm-result:last-child{border-bottom:0}
    .cm-result:hover{background:color-mix(in srgb,var(--gold) 8%,transparent)}
    .cm-result-title{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .cm-result-qari{color:var(--text2);font-size:.76rem;flex-shrink:0}
    .cm-preview,.cm-picked{list-style:none;margin:0;padding:0;border:1px solid var(--border);border-radius:var(--r2);overflow:hidden}
    .cm-preview li,.cm-picked li{display:flex;align-items:center;gap:.6rem;padding:.5rem .75rem;border-bottom:1px solid var(--border);font-size:.84rem}
    .cm-preview li:last-child,.cm-picked li:last-child{border-bottom:0}
    .cm-preview-num{width:1.5rem;color:var(--text3);font-family:monospace;font-size:.78rem;flex-shrink:0}
    .cm-preview-title{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .cm-preview-qari{color:var(--text2);font-size:.76rem;flex-shrink:0}
    .cm-preview-stat{color:var(--text3);font-size:.74rem;font-family:monospace;flex-shrink:0}
    .cm-picked-img{width:30px;height:30px;border-radius:6px;object-fit:cover;flex-shrink:0}
    .cm-empty{padding:.9rem;border:1px dashed var(--border);border-radius:var(--r2);color:var(--text2);font-size:.84rem}
    .cm-cover-preview{margin-top:.6rem;width:150px;aspect-ratio:16/10;object-fit:cover;border-radius:var(--r2);border:1px solid var(--border)}
    .cm-check{display:flex;align-items:center;gap:.5rem;margin-top:.9rem;font-size:.86rem;cursor:pointer}
    .cm-list{display:flex;flex-direction:column;gap:.5rem}
    .cm-row{display:flex;align-items:center;gap:.9rem;padding:.7rem .9rem;border:1px solid var(--border);border-radius:var(--r2);flex-wrap:wrap}
    .cm-row.is-off{opacity:.55}
    .cm-row-img{width:64px;height:40px;border-radius:6px;object-fit:cover;flex-shrink:0}
    .cm-row-main{flex:1;min-width:180px}
    .cm-row-title{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;font-weight:600;font-size:.92rem}
    .cm-row-meta{display:flex;gap:.9rem;flex-wrap:wrap;color:var(--text2);font-size:.75rem;margin-top:.25rem}
    .cm-row-actions{display:flex;align-items:center;gap:.25rem}
    .cm-delete{background:var(--red);color:#fff}
  </style>
</div>
