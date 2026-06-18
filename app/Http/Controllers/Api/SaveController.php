<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedTilawa;
use App\Models\Tilawa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaveController extends Controller
{
    public function ids(): JsonResponse
    {
        return response()->json([
            'ids' => SavedTilawa::where('user_id', auth()->id())->pluck('tilawa_id'),
        ]);
    }

    public function status(Tilawa $tilawa): JsonResponse
    {
        return response()->json([
            'saved' => SavedTilawa::where('user_id', auth()->id())->where('tilawa_id', $tilawa->id)->exists(),
        ]);
    }

    public function toggle(Request $request, Tilawa $tilawa): JsonResponse
    {
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $existing = SavedTilawa::where('user_id', auth()->id())->where('tilawa_id', $tilawa->id)->first();

        if ($existing) {
            $existing->delete();
            $saved = false;
        } else {
            SavedTilawa::create(['user_id' => auth()->id(), 'tilawa_id' => $tilawa->id]);
            $saved = true;
        }

        return response()->json(['saved' => $saved]);
    }

    /**
     * Merge tilawat a visitor saved while logged out into their account.
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

        foreach (Tilawa::whereIn('id', $ids)->pluck('id') as $tilawaId) {
            $save = SavedTilawa::firstOrCreate(['user_id' => $userId, 'tilawa_id' => $tilawaId]);
            if ($save->wasRecentlyCreated) {
                $synced++;
            }
        }

        return response()->json(['synced' => $synced]);
    }
}
