<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tilawa;
use Illuminate\Http\JsonResponse;

class PlayController extends Controller
{
    /**
     * Lightweight play-count increment, fired once per track playback start.
     */
    public function increment(Tilawa $tilawa): JsonResponse
    {
        $tilawa->increment('plays_count');

        return response()->json(['plays' => $tilawa->plays_count]);
    }
}
