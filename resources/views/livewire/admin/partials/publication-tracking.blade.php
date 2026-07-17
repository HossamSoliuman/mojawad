{{-- Per-platform publication status for a recitation. Expects $tilawa with its publications loaded. --}}
@php($pubs = $tilawa->publications->keyBy('platform'))
<div style="display:flex;flex-direction:column;gap:.3rem">
    @foreach(['podcast' => __('Podcast (Spotify / Anghami)'), 'youtube' => __('YouTube'), 'facebook' => __('Facebook')] as $platform => $plabel)
        @php($pub = $pubs->get($platform))
        @if($pub)
        @php($pmap = [
            'pending'    => ['badge-muted', __('Pending'),     'fa-clock'],
            'processing' => ['badge-amber', __('Publishing…'), 'fa-spinner fa-spin'],
            'completed'  => ['badge-green', __('Published'),   'fa-circle-check'],
            'failed'     => ['badge-red',   __('Failed'),      'fa-circle-exclamation'],
        ])
        @php([$pcls, $plbl, $pic] = $pmap[$pub->status] ?? ['badge-muted', $pub->status, 'fa-question'])
        <div style="display:flex;align-items:center;gap:.3rem;flex-wrap:wrap">
            <span style="font-size:.72rem;color:var(--text3);width:112px">{{ $plabel }}</span>
            @if($pub->status === 'completed' && $pub->external_url)
            <a href="{{ $pub->external_url }}" target="_blank" rel="noopener" class="badge {{ $pcls }}" style="text-decoration:none"><i class="fas {{ $pic }}"></i> {{ $plbl }} <i class="fas fa-arrow-up-right-from-square" style="font-size:.6rem"></i></a>
            @else
            <span class="badge {{ $pcls }}"><i class="fas {{ $pic }}"></i> {{ $plbl }}</span>
            @endif
            @if($pub->status === 'completed' && $pub->published_at)
            <span style="font-size:.7rem;color:var(--text3)">{{ $pub->published_at->diffForHumans() }}</span>
            @endif
            @if($pub->status === 'failed')
            <button type="button" wire:click="retryPublication({{ $pub->id }})" class="btn-icon" title="{{ __('Retry') }}"><i class="fas fa-rotate-right"></i></button>
            @if($pub->error)
            <span style="font-size:.7rem;color:#e86060;max-width:180px" title="{{ $pub->error }}">{{ Str::limit($pub->error, 40) }}</span>
            @endif
            @endif
        </div>
        @endif
    @endforeach
    @if($pubs->isEmpty())
    <span style="font-size:.74rem;color:var(--text3)">{{ __('Not published yet') }}</span>
    @endif
</div>
