<?php

namespace App\Http\Controllers;

use App\Models\Qari;
use App\Models\Surah;
use App\Models\Video;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');
        $videos = collect();
        $qaris = collect();

        if (strlen($query) >= 2) {
            $qaris = Qari::where('name_en', 'like', "%{$query}%")
                ->orWhere('name_ar', 'like', "%{$query}%")
                ->get();

            $surah = Surah::where('name_en', 'like', "%{$query}%")
                ->orWhere('name_ar', 'like', "%{$query}%")
                ->first();

            $videoQuery = Video::approved()->ranked()->with(['qari', 'surah']);

            if ($surah || $qaris->isNotEmpty()) {
                $videoQuery->where(function ($q) use ($surah, $qaris) {
                    if ($surah) $q->where('surah_id', $surah->id);
                    if ($qaris->isNotEmpty()) $q->orWhereIn('qari_id', $qaris->pluck('id'));
                });
            }

            $videos = $videoQuery->paginate(12);
        }

        return view('search.index', compact('videos', 'qaris', 'query'));
    }
}
