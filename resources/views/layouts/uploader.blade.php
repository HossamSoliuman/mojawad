<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', __('Upload')) — {{ __('Tilawa') }}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body x-data>
<div class="bg-dots"></div>

<div class="up-shell z1">
  <header class="up-topbar">
    <a href="{{ route('admin.upload') }}" class="navbar-brand" style="font-size:1.15rem">
      <i class="fas fa-book-open-reader"></i> {{ __('Tilawa') }}
    </a>
    <div style="display:flex;align-items:center;gap:.6rem">
      <div x-data="{ open: false }" style="position:relative">
        <button class="user-pill" @click="open=!open" @click.away="open=false" type="button">
          <i class="fas fa-globe" style="font-size:.9rem;color:var(--gold)"></i>
          <span class="user-pill-name" style="color:var(--text1)">{{ app()->getLocale() == 'ar' ? 'العربية' : 'English' }}</span>
        </button>
        <div class="dropdown" x-show="open" x-transition style="display:none">
          <a href="{{ route('lang', 'en') }}">English</a>
          <a href="{{ route('lang', 'ar') }}">العربية</a>
        </div>
      </div>
      <div class="up-user">
        <img src="{{ auth()->user()->avatar_url }}" class="avatar" width="30" height="30" alt="">
        <span class="up-user-name">{{ Str::limit(auth()->user()->name, 14) }}</span>
      </div>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button type="submit" class="btn-icon" style="color:var(--red)" title="{{ __('Logout') }}">
          <i class="fas fa-arrow-right-from-bracket"></i>
        </button>
      </form>
    </div>
  </header>

  <main class="up-main">
    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1.2rem"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @yield('content')
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@stack('scripts')
</body>
</html>
