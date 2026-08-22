<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\WatchHistory;
use App\Services\ShortSelector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __construct(private ShortSelector $selector) {}

    public function __invoke(Request $request)
    {
        $viewerKey = $this->selector->viewerKey($request);
        $short = $this->selector->forViewer($viewerKey);

        $data = Cache::remember('homepage_data', 600, fn () => [
            'featured_tilawat' => Tilawa::with('qari')->where('status', 'active')->where('is_featured', true)->latest()->take(6)->get(),
            'hero_qaris' => Qari::where('status', 'active')->whereNotNull('image')->withCount(['tilawat' => fn ($q) => $q->where('status', 'active')])->inRandomOrder()->take(12)->get(),
            'top_qaris' => Qari::where('status', 'active')->withCount(['tilawat' => fn ($q) => $q->where('status', 'active')])->orderByDesc('is_featured')->take(12)->get(),
            'recent_tilawat' => Tilawa::with('qari')->where('status', 'active')->latest()->take(12)->get(),
            'popular_tilawat' => Tilawa::with('qari')->where('status', 'active')->orderByDesc('likes_count')->take(12)->get(),
            'trending_tilawat' => $this->trendingTilawat(),
            'collections' => Collection::active()
                ->with(['tilawat' => fn ($q) => $q->where('status', 'active')->limit(1)])
                ->withCount(['tilawat as tilawat_count' => fn ($q) => $q->where('status', 'active')])
                ->orderBy('sort_order')->latest()->take(8)->get(),
        ]);

        return view('pages.home', [
            ...$data,
            'hero_short' => $short?->heroPayload(),
            'followed_new' => $this->newFromFollowed(),
        ]);
    }

    private function newFromFollowed(): \Illuminate\Support\Collection
    {
        if (! auth()->check()) {
            return collect();
        }

        $followedIds = auth()->user()->followedQaris()->pluck('qaris.id');

        if ($followedIds->isEmpty()) {
            return collect();
        }

        return Tilawa::with('qari')
            ->where('status', 'active')
            ->whereIn('qari_id', $followedIds)
            ->latest()
            ->take(12)
            ->get();
    }

    private function trendingTilawat(): \Illuminate\Support\Collection
    {
        $ranked = WatchHistory::query()
            ->where('last_watched_at', '>=', now()->subDays(7))
            ->select('tilawa_id')
            ->selectRaw('COUNT(*) as plays')
            ->groupBy('tilawa_id')
            ->orderByDesc('plays')
            ->limit(12)
            ->pluck('tilawa_id');

        if ($ranked->isEmpty()) {
            return collect();
        }

        return Tilawa::with('qari')
            ->where('status', 'active')
            ->whereIn('id', $ranked)
            ->get()
            ->sortBy(fn (Tilawa $t) => $ranked->search($t->id))
            ->values();
    }
}
