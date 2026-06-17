<?php

namespace App\Http\Controllers;

use App\Models\Qari;
use App\Models\Short;
use App\Models\Tilawa;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke()
    {
        $hero_shorts = Cache::remember(
            'active_hero_shorts',
            600,
            fn () => Short::active()->orderBy('sort_order')->latest()->get()
                ->map(fn (Short $short) => $short->heroPayload())->values()->all()
        );

        $data = Cache::remember('homepage_data', 600, fn () => [
            'featured_tilawat' => Tilawa::with('qari')->where('status', 'active')->where('is_featured', true)->latest()->take(6)->get(),
            'hero_qaris' => Qari::where('status', 'active')->whereNotNull('image')->withCount(['tilawat' => fn ($q) => $q->where('status', 'active')])->inRandomOrder()->take(12)->get(),
            'top_qaris' => Qari::where('status', 'active')->withCount(['tilawat' => fn ($q) => $q->where('status', 'active')])->orderByDesc('is_featured')->take(12)->get(),
            'recent_tilawat' => Tilawa::with('qari')->where('status', 'active')->latest()->take(12)->get(),
            'popular_tilawat' => Tilawa::with('qari')->where('status', 'active')->orderByDesc('likes_count')->take(12)->get(),
        ]);

        return view('pages.home', [...$data, 'hero_shorts' => $hero_shorts]);
    }
}
