<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Qari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function ids(): JsonResponse
    {
        return response()->json([
            'ids' => auth()->user()->followedQaris()->pluck('qaris.id'),
        ]);
    }

    public function toggle(Qari $qari): JsonResponse
    {
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $changed = auth()->user()->followedQaris()->toggle($qari->id);
        $following = in_array($qari->id, $changed['attached'], true);

        return response()->json([
            'following' => $following,
            'followers' => $qari->followers()->count(),
        ]);
    }

    /**
     * Merge follows a visitor made while logged out into their account.
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['array'],
            'ids.*' => ['integer'],
        ]);

        $ids = Qari::whereIn('id', array_values(array_unique($validated['ids'] ?? [])))->pluck('id');
        auth()->user()->followedQaris()->syncWithoutDetaching($ids);

        return response()->json(['synced' => $ids->count()]);
    }
}
