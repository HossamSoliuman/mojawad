<a href="{{ route('qaris.show', $q) }}" wire:navigate class="marquee-item" title="{{ $q->name }}">
  <img src="{{ $q->image_url }}" alt="{{ $q->name }}" loading="lazy">
  <div class="marquee-info">
    <div class="marquee-name">{{ $q->name }}</div>
    <div class="marquee-count"><i class="fas fa-microphone"></i> {{ $q->tilawat_count }} {{ __('tilawat') }}</div>
  </div>
</a>
