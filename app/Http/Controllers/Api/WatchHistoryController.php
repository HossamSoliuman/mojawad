<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tilawa;
use App\Models\WatchHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WatchHistoryController extends Controller
{
    /**
     * In-progress feed for the "Continue listening" rail.
     */
    public function index(): JsonResponse
    {
        $items = WatchHistory::where('user_id', auth()->id())
            ->where('completed', false)
            ->with('tilawa.qari')
            ->whereHas('tilawa', fn ($q) => $q->where('status', 'active'))
            ->orderByDesc('last_watched_at')
            ->take(20)
            ->get()
            ->map(fn (WatchHistory $h): array => $h->tilawa->playerPayload() + [
                'position' => $h->position,
                'completed' => false,
            ])
            ->values();

        return response()->json(['items' => $items]);
    }

    /**
     * Upsert a single watch record from the player.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:tilawat,id'],
            'position' => ['required', 'integer', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'completed' => ['boolean'],
        ]);

        WatchHistory::updateOrCreate(
            ['user_id' => auth()->id(), 'tilawa_id' => $validated['id']],
            [
                'position' => $validated['position'],
                'duration' => $validated['duration'] ?? 0,
                'completed' => $validated['completed'] ?? false,
                'last_watched_at' => now(),
            ],
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Merge the history a visitor built while logged out into their account.
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.position' => ['required', 'integer', 'min:0'],
            'items.*.duration' => ['nullable', 'integer', 'min:0'],
            'items.*.completed' => ['boolean'],
            'items.*.updated_at' => ['nullable', 'numeric'],
        ]);

        $userId = auth()->id();
        $items = collect($validated['items'] ?? [])->keyBy('id');
        $validIds = Tilawa::whereIn('id', $items->keys())->pluck('id');
        $synced = 0;

        foreach ($validIds as $tilawaId) {
            $item = $items->get($tilawaId);
            $watchedAt = isset($item['updated_at'])
                ? Carbon::createFromTimestampMs((int) $item['updated_at'])
                : now();

            $history = WatchHistory::firstOrNew(['user_id' => $userId, 'tilawa_id' => $tilawaId]);

            $history->position = max($history->position ?? 0, (int) $item['position']);
            $history->duration = max($history->duration ?? 0, (int) ($item['duration'] ?? 0));
            $history->completed = (bool) ($history->completed || ($item['completed'] ?? false));
            $history->last_watched_at = max($history->last_watched_at, $watchedAt);
            $history->save();
            $synced++;
        }

        return response()->json(['synced' => $synced]);
    }

    public function destroy(Tilawa $tilawa): JsonResponse
    {
        WatchHistory::where('user_id', auth()->id())->where('tilawa_id', $tilawa->id)->delete();

        return response()->json(['ok' => true]);
    }

    public function clear(): JsonResponse
    {
        WatchHistory::where('user_id', auth()->id())->delete();

        return response()->json(['ok' => true]);
    }
}
