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

    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
            <a href="{{ route('home') }}" class="text-xl font-bold text-emerald-700 tracking-tight">مُجَوَّد</a>
            <div class="flex items-center gap-5 text-sm">
                <a href="{{ route('home') }}" class="text-gray-600 hover:text-emerald-700">Home</a>
                <a href="{{ route('search') }}" class="text-gray-600 hover:text-emerald-700">Search</a>
                @auth
                    <a href="{{ route('library') }}" class="text-gray-600 hover:text-emerald-700">Library</a>
                    @if (auth()->user()->canModerate())
                        <a href="{{ route('creator.queue') }}" class="text-gray-600 hover:text-emerald-700">Queue</a>
                    @endif
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.applications') }}" class="text-gray-600 hover:text-emerald-700">Admin</a>
                    @endif
                    
                    <a href="{{ route('videos.create') }}"
                        class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">Upload</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-gray-700">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-emerald-700">Login</a>
                    <a href="{{ route('register') }}"
                        class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8">
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

    @livewireScripts
</body>

</html>
