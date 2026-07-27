<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="utf-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;700&family=Reem+Kufi:wght@400..700&family=Tajawal:wght@400;500;700&display=swap">
{{--
  $layer selects what gets painted so ffmpeg can composite the card in motion:
  - full:    the whole card (previews, posters, static covers)
  - overlay: everything except the qari photo — the photo window is transparent
             and the fade gradients stay, so they darken the moving photo below
  - text:    only the extra-text block, same layout → aligns at overlay=0:0
  $holdTextSpace keeps the extra text's layout slot while hiding it (the text
  animates in as its own layer). $animatePreview adds CSS motion that mirrors
  the ffmpeg animation for the admin live preview.
--}}
@php($layer = $layer ?? 'full')
@php($tilawaTitle = trim((string) ($tilawaTitle ?? '')))
@php($surahName = trim((string) ($surahName ?? '')))
@php($qariName = trim((string) ($qariName ?? '')))
@php($rareBadge = trim((string) ($rareBadge ?? '')))
@php($headline = $surahName !== '' ? 'ما تيسر من سورة '.$surahName : $tilawaTitle)
@php($qariDisplayName = $qariName !== '' && ! str_starts_with($qariName, 'الشيخ') ? 'الشيخ '.$qariName : $qariName)
@php($holdTextSpace = $holdTextSpace ?? false)
@php($animatePreview = $animatePreview ?? false)
@php($animatePhoto = $animatePhoto ?? true)
@php($animateText = $animateText ?? true)
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  html, body { width: {{ $width }}px; height: {{ $height }}px; background: {{ $layer === 'full' ? '#070705' : 'transparent' }}; }
  .card {
    position: relative;
    direction: ltr;
    display: flex; flex-direction: column;
    width: {{ $width }}px; height: {{ $height }}px;
    background: {{ $layer === 'full' ? '#070705' : 'transparent' }};
    overflow: hidden;
    font-family: 'Tajawal', sans-serif;
    color: #fff;
  }
  .card::before {
    content: '';
    position: absolute; z-index: 0; inset: 0;
    pointer-events: none;
    background:
      radial-gradient(circle at 23% 34%, rgba(210,166,58,.13), transparent 29%),
      linear-gradient(115deg, rgba(255,255,255,.035) 0%, transparent 22%, transparent 78%, rgba(210,166,58,.055) 100%);
  }
  .badge {
    position: relative; z-index: 5;
    align-self: flex-start;
    direction: rtl;
    display: block;
    color: #e1b84f;
    font-family: 'Reem Kufi', sans-serif; font-weight: 700;
    font-size: {{ (int) round($height * 0.052) }}px;
    line-height: 1.25;
    text-shadow: 0 0 {{ (int) round($height * 0.022) }}px rgba(225,184,79,.18);
  }
  .main { position: relative; z-index: 1; display: flex; flex: 1; min-height: 0; }
  .text {
    position: relative; z-index: 3;
    direction: rtl;
    width: 57%;
    display: flex; flex-direction: column; justify-content: center;
    gap: {{ (int) round($height * 0.025) }}px;
    text-align: right;
    padding: {{ (int) round($height * 0.07) }}px {{ (int) round($width * 0.058) }}px {{ (int) round($height * 0.065) }}px {{ (int) round($width * 0.035) }}px;
    background: {{ $layer === 'overlay' ? '#070705' : 'transparent' }};
  }
  .title {
    max-width: {{ (int) round($width * 0.47) }}px;
    font-family: 'Aref Ruqaa', serif; font-weight: 700;
    font-size: {{ (int) round($height * 0.102) }}px;
    line-height: 1.3;
    color: #fffdf7;
    text-wrap: balance;
    text-shadow: 0 {{ (int) round($height * 0.008) }}px {{ (int) round($height * 0.025) }}px rgba(0,0,0,.68);
  }
  .rule {
    position: relative;
    width: {{ (int) round($width * 0.12) }}px; height: 3px;
    background: linear-gradient(90deg, transparent, #d8ad43); border-radius: 4px;
  }
  .rule::after {
    content: '';
    position: absolute; right: 0; top: 50%;
    width: {{ (int) round($height * 0.013) }}px; height: {{ (int) round($height * 0.013) }}px;
    border-radius: 50%; background: #f0d27d;
    transform: translate(50%, -50%);
    box-shadow: 0 0 {{ (int) round($height * 0.018) }}px rgba(240,210,125,.65);
  }
  .qari-block {
    display: flex; flex-direction: column;
    gap: {{ (int) round($height * 0.006) }}px;
  }
  .qari-kicker {
    font-family: 'Tajawal', sans-serif; font-weight: 500;
    font-size: {{ (int) round($height * 0.034) }}px;
    color: #d8ad43;
  }
  .qari {
    font-family: 'Reem Kufi', sans-serif; font-weight: 700;
    font-size: {{ (int) round($height * 0.06) }}px;
    line-height: 1.35;
    color: #fff;
  }
  .extra {
    font-family: 'Tajawal', sans-serif; font-weight: 400;
    max-width: {{ (int) round($width * 0.43) }}px;
    font-size: {{ (int) round($height * 0.03) }}px;
    color: rgba(255,255,255,.68);
    line-height: 1.7;
  }
  .photo {
    position: relative; width: 43%;
    background: {{ $layer === 'full' ? '#0a0a08' : 'transparent' }};
    border-left: 1px solid rgba(216,173,67,.24);
  }
  .photo::before {
    content: '';
    position: absolute; z-index: 3;
    top: {{ (int) round($height * 0.038) }}px; right: {{ (int) round($height * 0.038) }}px; bottom: {{ (int) round($height * 0.038) }}px; left: {{ (int) round($height * 0.038) }}px;
    border: 1px solid rgba(236,204,116,.36);
    pointer-events: none;
  }
  .photo img {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    object-position: center top;
    filter: grayscale(1) sepia(.12) contrast(1.12) brightness(.82);
  }
  .photo .fade {
    position: absolute; z-index: 2; inset: 0;
    background:
      linear-gradient(90deg, #070705 0%, rgba(7,7,5,.62) 12%, rgba(7,7,5,0) 38%),
      linear-gradient(0deg, rgba(7,7,5,.9) 0%, rgba(7,7,5,0) 32%),
      linear-gradient(180deg, rgba(7,7,5,.42) 0%, transparent 24%);
  }
  .footer {
    position: relative; z-index: 4; direction: rtl;
    height: {{ (int) round($height * 0.1) }}px;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    gap: {{ (int) round($width * 0.028) }}px;
    border-top: 1px solid rgba(216,173,67,.22);
    background: #050504;
  }
  .soc {
    display: flex; align-items: center;
    gap: {{ (int) round($width * 0.007) }}px;
    color: #cfd6dd;
    font-family: 'Tajawal', sans-serif; font-weight: 500;
    font-size: {{ (int) round($height * 0.026) }}px;
  }
  .soc svg { width: {{ (int) round($height * 0.034) }}px; height: {{ (int) round($height * 0.034) }}px; }
  .soc.yt svg { fill: #FF0000; }
  .soc.fb svg { fill: #1877F2; }
  .soc.ig svg { fill: url(#igGrad); }
  @if($layer === 'text')
  .card::before { display: none; }
  .title, .rule, .qari-block, .photo, .footer, .badge { visibility: hidden; }
  @elseif($layer === 'overlay' && $holdTextSpace)
  .extra { visibility: hidden; }
  @endif
  @if($animatePreview && $layer === 'full')
  @if($animatePhoto)
  @keyframes cardPhotoDrift { 0%, 100% { transform: translateY(-{{ (int) round($height * 0.022) }}px); } 50% { transform: translateY({{ (int) round($height * 0.022) }}px); } }
  .photo img { animation: cardPhotoDrift 12s ease-in-out infinite; }
  @endif
  @if($animateText)
  @keyframes cardTextRise { from { opacity: 0; transform: translateY({{ (int) round($height * 0.083) }}px); } to { opacity: 1; transform: translateY(0); } }
  .extra { animation: cardTextRise 2.4s cubic-bezier(.22,.61,.36,1) both; animation-delay: .3s; }
  @endif
  @endif
</style>
</head>
<body>
  <div class="card">
    <div class="main">
      <div class="text">
        @if($rareBadge !== '')
          <div class="badge">{{ $rareBadge }}</div>
        @endif
        @if($headline !== '')
          <div class="title">{{ $headline }}</div>
        @endif
        <div class="rule"></div>
        @if($qariDisplayName !== '')
          <div class="qari-block">
            <div class="qari-kicker">لأسطورة التلاوة</div>
            <div class="qari">{{ $qariDisplayName }}</div>
          </div>
        @endif
        @if($extraText)
          <div class="extra">{{ $extraText }}</div>
        @endif
      </div>
      <div class="photo">
        @if($qariImage && $layer !== 'overlay')
          <img src="{{ $qariImage }}" alt="">
        @endif
        <div class="fade"></div>
      </div>
    </div>
    <div class="footer">
      @if(!empty($social['youtube']))
        <span class="soc yt"><svg viewBox="0 0 576 512"><path d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z"/></svg>{{ $social['youtube'] }}</span>
      @endif
      @if(!empty($social['facebook']))
        <span class="soc fb"><svg viewBox="0 0 320 512"><path d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"/></svg>{{ $social['facebook'] }}</span>
      @endif
      @if(!empty($social['website']))
        <span class="soc web"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#e9c46a" stroke-width="2"/><path d="M2 12h20M12 2c3 3 3 17 0 20M12 2c-3 3-3 17 0 20" stroke="#e9c46a" stroke-width="2"/></svg>{{ $social['website'] }}</span>
      @endif
      @if(!empty($social['instagram']))
        <span class="soc ig"><svg viewBox="0 0 448 512"><defs><linearGradient id="igGrad" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="#feda75"/><stop offset=".3" stop-color="#fa7e1e"/><stop offset=".55" stop-color="#d62976"/><stop offset=".8" stop-color="#962fbf"/><stop offset="1" stop-color="#4f5bd5"/></linearGradient></defs><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>{{ $social['instagram'] }}</span>
      @endif
    </div>
  </div>
</body>
</html>
