<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Qari;
use App\Models\Tilawa;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['qaris' => [], 'tilawat' => []]);
        }

        $qaris = Qari::where('status', 'active')
            ->where('name', 'like', '%' . $q . '%')
            ->take(4)
            ->get(['id', 'name', 'slug', 'image'])
            ->map(fn ($r) => [
                'id'        => $r->id,
                'name'      => $r->name,
                'slug'      => $r->slug,
                'image_url' => $r->image_url,
            ]);

        $tilawat = Tilawa::with('qari:id,name,slug')
            ->where('status', 'active')
            ->where('title', 'like', '%' . $q . '%')
            ->take(4)
            ->get()
            ->map(fn ($r) => [
                'id'         => $r->id,
                'title'      => $r->title,
                'slug'       => $r->slug,
                'qari'       => $r->qari->name,
                'qari_slug'  => $r->qari->slug,
                'cover_url'  => $r->cover_url,
                'duration'   => $r->formatted_duration,
            ]);

        return response()->json(['qaris' => $qaris, 'tilawat' => $tilawat]);
    }
}
