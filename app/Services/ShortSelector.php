<?php

namespace App\Services;

use App\Models\Short;
use App\Models\ShortView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class ShortSelector
{
    private const GUEST_COOKIE = 'mojawad_vid';

    private const COOKIE_MINUTES = 525_600; // 1 year

    public function viewerKey(Request $request): string
    {
        if (auth()->check()) {
            return 'u:'.auth()->id();
        }

        $token = $request->cookie(self::GUEST_COOKIE);

        if (! $token) {
            $token = (string) Str::uuid();
            Cookie::queue(self::GUEST_COOKIE, $token, self::COOKIE_MINUTES, '/', null, null, false);
            // Stash on the request so the same token is available within this request.
            $request->merge(['_mojawad_vid' => $token]);
        }

        return 'g:'.$token;
    }

    public function forViewer(string $viewerKey): ?Short
    {
        // A currently active pinned short takes priority for every viewer.
        $pinned = Short::pinnedNow()
            ->active()
            ->whereNotNull('media_path')
            ->orderBy('pinned_ends_at')
            ->first();

        if ($pinned) {
            return $pinned;
        }

        // Return the short this viewer has seen the fewest times.
        return Short::active()
            ->whereNotNull('media_path')
            ->leftJoin('short_views', function ($join) use ($viewerKey) {
                $join->on('short_views.short_id', '=', 'shorts.id')
                    ->where('short_views.viewer_key', $viewerKey);
            })
            ->orderByRaw('COALESCE(short_views.views, 0) ASC')
            ->orderBy('shorts.sort_order')
            ->orderBy('shorts.id')
            ->select('shorts.*')
            ->first();
    }

    public function recordView(Short $short, string $viewerKey): void
    {
        $row = ShortView::firstOrNew([
            'short_id' => $short->id,
            'viewer_key' => $viewerKey,
        ]);

        $row->views = ($row->views ?? 0) + 1;
        $row->last_viewed_at = now();
        $row->save();
    }
}
