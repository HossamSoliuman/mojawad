@props(['size' => 24])

{{--
    Mojawad brand mark — the "acoustic mihrab": a prayer niche (mihrab) with a
    soundwave rising inside it. Emerald arch (the niche / Islam) + gold soundwave
    (the recitation, illuminated). Under 40px it renders a simplified 3-bar variant
    so it stays legible at favicon / nav sizes.
--}}
@php($mini = (int) $size < 40)

<svg {{ $attributes->merge(['class' => 'brand-mark']) }}
     width="{{ $size }}" height="{{ round((int) $size * 1.25) }}"
     viewBox="0 0 120 150" fill="none" role="img"
     data-variant="{{ $mini ? 'mini' : 'full' }}"
     aria-label="{{ config('app.name') }}"
     xmlns="http://www.w3.org/2000/svg">
    <path d="M22 134 L22 62 Q22 24 60 12 Q98 24 98 62 L98 134 Z"
          fill="rgba(29,185,84,.12)" stroke="#1DB954" stroke-width="6" stroke-linejoin="round"/>
    @if ($mini)
        <g fill="#E9C46A">
            <rect x="48" y="84" width="6" height="38" rx="3"/>
            <rect x="57" y="64" width="6" height="58" rx="3"/>
            <rect x="66" y="84" width="6" height="38" rx="3"/>
        </g>
    @else
        <g fill="#E9C46A">
            <rect x="41.5" y="98" width="5" height="24" rx="2.5"/>
            <rect x="50.5" y="82" width="5" height="40" rx="2.5"/>
            <rect x="59.5" y="66" width="5" height="56" rx="2.5"/>
            <rect x="68.5" y="82" width="5" height="40" rx="2.5"/>
            <rect x="77.5" y="98" width="5" height="24" rx="2.5"/>
        </g>
    @endif
</svg>
