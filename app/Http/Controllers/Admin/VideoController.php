<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;

class VideoController extends Controller
{
    public function destroy(Video $video)
    {
        $video->update(['status' => 'rejected']);
        return back()->with('success', 'Video removed from platform.');
    }
}
