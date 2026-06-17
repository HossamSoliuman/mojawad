<div>
  @if($history->isEmpty())
  <div style="text-align:center;padding:4rem 1rem;color:var(--text2)">
    <i class="fas fa-clock-rotate-left" style="font-size:2.5rem;display:block;margin-bottom:.65rem"></i>
    {{ __('No history yet.') }}
  </div>
  @else
  <div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
    <button type="button" class="btn btn-ghost btn-xs" wire:click="clearAll"
      onclick="return confirm(@js(__('Clear history')) + '?')">
      <i class="fas fa-trash"></i> {{ __('Clear history') }}
    </button>
  </div>

  <div class="grid-tilawat" data-queue>
    @foreach($history as $h)
    @php($t = $h->tilawa)
    <div class="t-card" data-track-id="{{ $t->id }}" wire:key="history-{{ $t->id }}">
      <div class="t-card-img">
        <img src="{{ $t->cover_url }}" alt="{{ $t->title }}" loading="lazy">
        <button class="t-play-btn" data-track="{{ json_encode($t->playerPayload()) }}">
          <i class="fas fa-play"></i>
        </button>
        <button type="button" class="card-dismiss" wire:click="remove({{ $t->id }})"
          title="{{ __('Remove from history') }}">
          <i class="fas fa-xmark"></i>
        </button>
        @if($h->completed)
        <span class="history-badge"><i class="fas fa-circle-check"></i> {{ __('Completed') }}</span>
        @endif
        <div class="t-card-progress">
          <div class="t-card-progress-fill" style="width:{{ $h->completed ? 100 : $h->progress_percent }}%"></div>
        </div>
      </div>
      <div class="t-card-body">
        <a href="{{ route('tilawa.show', $t) }}" wire:navigate>
          <div class="t-card-title">{{ $t->title }}</div>
        </a>
        <a href="{{ route('qaris.show', $t->qari) }}" wire:navigate class="t-card-qari">
          {{ $t->qari->name }}
        </a>
        <div class="t-card-meta">
          <span><i class="fas fa-clock"></i> {{ $t->formatted_duration }}</span>
          <span>@if(!$h->completed){{ $h->progress_percent }}%@endif</span>
        </div>
      </div>
    </div>
    @endforeach
  </div>

  @if($hasMore)
  <div
    wire:key="sentinel-history-{{ $perPage }}"
    x-data
    x-init="
      const io = new IntersectionObserver(([e]) => {
        if (e.isIntersecting) { io.disconnect(); $wire.loadMore(); }
      }, { rootMargin: '200px' });
      io.observe($el);
    "
    style="display:flex;justify-content:center;padding:1.5rem"
  >
    <i class="fas fa-spinner fa-spin" style="color:var(--gold);font-size:1.2rem"></i>
  </div>
  @endif
  @endif

  <div wire:loading style="text-align:center;padding:1rem">
    <i class="fas fa-circle-notch fa-spin" style="color:var(--gold);font-size:1rem;opacity:.6"></i>
  </div>
</div>
