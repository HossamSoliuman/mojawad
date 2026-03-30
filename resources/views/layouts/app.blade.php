<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mojawad &mdash; @yield('title', 'Quran Recitations')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>

<body class="bg-gray-50 text-gray-900 min-h-screen">

    @php
        $dashboardUser = auth()->check() && (auth()->user()->canModerate() || auth()->user()->isAdmin());
    @endphp

    @if (!$dashboardUser)
        @include('layouts.partials.navbar')
    @endif

    <div class="{{ $dashboardUser ? 'flex' : '' }}">

        @if ($dashboardUser)
            @include('layouts.partials.sidebar')
        @endif

        <main class="{{ $dashboardUser ? 'flex-1 p-8' : 'max-w-7xl mx-auto px-4 py-8 w-full' }}">
            @foreach (['success' => 'emerald', 'info' => 'blue', 'error' => 'red'] as $type => $color)
                @if (session($type))
                    <div
                        class="mb-6 bg-{{ $color }}-50 border border-{{ $color }}-200 text-{{ $color }}-800 px-4 py-3 rounded-lg text-sm">
                        {{ session($type) }}
                    </div>
                @endif
            @endforeach

            @yield('content')
        </main>

    </div>

    @livewireScripts
</body>


</html>
