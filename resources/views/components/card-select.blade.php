@props([
    'name',
    'options' => [],
    'selected' => null,
    'grid' => false,
])

{{--
    Clickable card selector — a click-to-pick replacement for <select>.
    Each option: ['value' => ..., 'label' => ..., 'image' => null, 'icon' => null]
--}}
<div {{ $attributes->merge(['class' => 'card-select'.($grid ? ' card-select-grid' : '')]) }}>
    @foreach($options as $opt)
        <label class="card-opt">
            <input type="radio" name="{{ $name }}" value="{{ $opt['value'] }}"
                   {{ (string) $selected === (string) $opt['value'] ? 'checked' : '' }}>
            <span class="card-opt-inner">
                @if(!empty($opt['image']))
                    <img src="{{ $opt['image'] }}" alt="">
                @elseif(!empty($opt['icon']))
                    <i class="fas {{ $opt['icon'] }}"></i>
                @endif
                <span>{{ $opt['label'] }}</span>
                <i class="fas fa-circle-check card-opt-check"></i>
            </span>
        </label>
    @endforeach
</div>
