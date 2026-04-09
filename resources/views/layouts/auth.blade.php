<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title') — Tilawa</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@vite(['resources/css/app.css'])
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1.5rem">
<div class="bg-dots"></div>
<div style="position:relative;z-index:1;width:100%;max-width:405px">
  <div style="text-align:center;margin-bottom:1.85rem">
    <a href="{{ route('home') }}" class="navbar-brand" style="justify-content:center;font-size:1.45rem">
      <i class="fas fa-book-open-reader"></i> Tilawa
    </a>
  </div>
  <div class="card" style="padding:2.2rem">@yield('content')</div>
</div>
</body>
</html>
