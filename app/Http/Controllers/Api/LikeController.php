<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Tilawa;

class LikeController extends Controller
{
    public function toggle(Tilawa $tilawa)
    {
        $existing = Like::where('user_id', auth()->id())
            ->where('tilawa_id', $tilawa->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $tilawa->decrement('likes_count');
            $liked = false;
        } else {
            Like::create(['user_id' => auth()->id(), 'tilawa_id' => $tilawa->id]);
            $tilawa->increment('likes_count');
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'count' => $tilawa->fresh()->likes_count,
        ]);
    }
}
