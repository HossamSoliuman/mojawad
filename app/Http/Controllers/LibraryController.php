<?php

namespace App\Http\Controllers;

class LibraryController extends Controller
{
    public function index()
    {
        $videos = auth()->user()
            ->savedVideos()
            ->with(['qari', 'surah'])
            ->orderByPivot('created_at', 'desc')
            ->paginate(12);

        return view('library.index', compact('videos'));
    }
}
