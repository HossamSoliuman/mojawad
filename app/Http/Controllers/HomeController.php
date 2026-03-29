<?php

namespace App\Http\Controllers;

use App\Models\Qari;
use App\Models\Video;

class HomeController extends Controller
{
    public function index()
    {
        $videos = Video::approved()
            ->ranked()
            ->with(['qari', 'surah'])
            ->paginate(12);

        $qaris = Qari::withCount(['allVideos as approved_count' => fn($q) => $q->where('status', 'approved')])
            ->orderByDesc('approved_count')
            ->take(6)
            ->get();

        return view('home.index', compact('videos', 'qaris'));
    }
}
