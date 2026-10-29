{{-- Where a recitation went live on YouTube and Facebook. Expects $tilawa with its publications loaded. --}}
@php($pubs = $tilawa->publications->where('status', 'completed')->keyBy('platform'))
<div style="display:flex;flex-direction:column;gap:.3rem">
    @foreach(['youtube' => __('YouTube'), 'facebook' => __('Facebook')] as $platform => $plabel)
        @php($pub = $pubs->get($platform))
        <div style="display:flex;align-items:center;gap:.3rem;flex-wrap:wrap">
            <span style="font-size:.72rem;color:var(--text3);width:70px">{{ $plabel }}</span>
            @if($pub && $pub->external_url)
            <a href="{{ $pub->external_url }}" target="_blank" rel="noopener" class="badge badge-green" style="text-decoration:none"><i class="fas fa-circle-check"></i> {{ __('Posted') }} <i class="fas fa-arrow-up-right-from-square" style="font-size:.6rem"></i></a>
            @elseif($pub)
            <span class="badge badge-green"><i class="fas fa-circle-check"></i> {{ __('Posted') }}</span>
            @else
            <span class="badge badge-muted">{{ __('Not published yet') }}</span>
            @endif
            @if($pub?->published_at)
            <span style="font-size:.7rem;color:var(--text3)">{{ $pub->published_at->diffForHumans() }}</span>
            @endif
        </div>
    @endforeach
</div>
