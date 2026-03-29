<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Video;

class QueueController extends Controller
{
    public function index()
    {
        $videos = Video::pending()->with(['qari', 'surah', 'uploader'])->latest()->paginate(20);
        return view('creator.queue', compact('videos'));
    }
}
