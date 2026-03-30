<aside class="w-64 bg-white border-r border-gray-200 min-h-screen">
    <div class="p-6 font-bold text-xl text-emerald-700">
        مُجَوَّد
    </div>

    <nav class="px-4 space-y-2 text-sm">

        <a href="{{ route('home') }}" class="block px-3 py-2 rounded hover:bg-gray-100">
            Dashboard
        </a>

        <a href="{{ route('library') }}" class="block px-3 py-2 rounded hover:bg-gray-100">
            Library
        </a>

        <a href="{{ route('creator.queue') }}" class="block px-3 py-2 rounded hover:bg-gray-100">
            Queue
        </a>

        @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.applications') }}" class="block px-3 py-2 rounded hover:bg-gray-100">
                Admin Panel
            </a>
        @endif

        <a href="{{ route('videos.create') }}" class="block px-3 py-2 rounded hover:bg-gray-100">
            Upload
        </a>

        <form method="POST" action="{{ route('logout') }}" class="pt-4">
            @csrf
            <button class="text-gray-500 hover:text-gray-800">
                Logout
            </button>
        </form>

    </nav>
</aside>