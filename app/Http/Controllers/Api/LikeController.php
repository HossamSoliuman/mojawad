<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Tilawa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function status(Tilawa $tilawa): JsonResponse
    {
        return response()->json([
            'liked' => Like::where('user_id', auth()->id())->where('tilawa_id', $tilawa->id)->exists(),
        ]);
    }

    public function toggle(Request $request, Tilawa $tilawa)
    {
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $existing = Like::where('user_id', auth()->id())->where('tilawa_id', $tilawa->id)->first();
        if ($existing) {
            $existing->delete();
            $tilawa->decrement('likes_count');
            $liked = false;
        } else {
            Like::create(['user_id' => auth()->id(), 'tilawa_id' => $tilawa->id]);
            $tilawa->increment('likes_count');
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'count' => $tilawa->fresh()->likes_count]);
    }

    /**
     * Merge likes a visitor made while logged out into their account.
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['array'],
            'ids.*' => ['integer'],
        ]);

        $userId = auth()->id();
        $ids = array_values(array_unique($validated['ids'] ?? []));
        $synced = 0;

        foreach (Tilawa::whereIn('id', $ids)->get() as $tilawa) {
            $like = Like::firstOrCreate(['user_id' => $userId, 'tilawa_id' => $tilawa->id]);
            if ($like->wasRecentlyCreated) {
                $tilawa->increment('likes_count');
                $synced++;
            }
        }

        return response()->json(['synced' => $synced]);
    }
}
