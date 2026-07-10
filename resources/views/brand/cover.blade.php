<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@600;700&display=swap">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  html, body { width: {{ $size }}px; height: {{ $size }}px; }
  .cover {
    position: relative;
    width: {{ $size }}px; height: {{ $size }}px;
    background: #0b0f14;
    overflow: hidden;
    font-family: 'Cairo', sans-serif;
    color: #fff;
  }
  .bg {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    filter: brightness(.55) saturate(1.05);
  }
  .grad {
    position: absolute; inset: 0;
    background: linear-gradient(180deg, rgba(11,15,20,.35) 0%, rgba(11,15,20,.15) 40%, rgba(11,15,20,.92) 100%);
  }
  .content {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: flex-end;
    text-align: center;
    padding: {{ (int) round($size * 0.09) }}px;
  }
  .logo { width: {{ (int) round($size * 0.30) }}px; margin-bottom: auto; margin-top: {{ (int) round($size * 0.04) }}px; opacity: .95; }
  .surah {
    font-family: 'Amiri', serif; font-weight: 700;
    font-size: {{ (int) round($size * 0.14) }}px;
    line-height: 1.1;
    text-shadow: 0 4px 30px rgba(0,0,0,.6);
  }
  .range {
    font-family: 'Amiri', serif;
    font-size: {{ (int) round($size * 0.055) }}px;
    opacity: .9;
    margin-top: {{ (int) round($size * 0.02) }}px;
  }
  .qari {
    font-weight: 700;
    font-size: {{ (int) round($size * 0.065) }}px;
    margin-top: {{ (int) round($size * 0.05) }}px;
    color: #e9c46a;
  }
  .rule { width: {{ (int) round($size * 0.16) }}px; height: 3px; background: #e9c46a; margin: {{ (int) round($size * 0.035) }}px auto; border-radius: 3px; }
</style>
</head>
<body>
  <div class="cover">
    @if($qariImage)
      <img class="bg" src="{{ $qariImage }}" alt="">
    @endif
    <div class="grad"></div>
    <div class="content">
      @if($logo)
        <img class="logo" src="{{ $logo }}" alt="">
      @endif
      @if($surahName)
        <div class="surah">{{ __('سورة') }} {{ $surahName }}</div>
      @endif
      @if($ayahFrom && $ayahTo)
        <div class="range">{{ __('الآيات') }} {{ $ayahFrom }} - {{ $ayahTo }}</div>
      @endif
      <div class="rule"></div>
      @if($qariName)
        <div class="qari">{{ $qariName }}</div>
      @endif
    </div>
  </div>
</body>
</html>
