<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Qari;
use App\Models\Tilawa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['qaris' => [], 'tilawat' => []]);
        }

        $qaris = Qari::where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('name_ar', 'like', '%'.$q.'%')
                    ->orWhere('name_en', 'like', '%'.$q.'%');
            })
            ->take(5)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'image' => $r->image_url,
                'url' => route('qaris.show', $r->slug),
            ]);

        $surahNumbers = $this->matchSurahNumbers($q);

        $tilawat = Tilawa::with('qari')
            ->where('status', 'active')
            ->where(function ($query) use ($q, $surahNumbers) {
                $query->where('title_ar', 'like', '%'.$q.'%')
                    ->orWhere('title_en', 'like', '%'.$q.'%')
                    ->orWhereHas('qari', function ($sub) use ($q) {
                        $sub->where('name_ar', 'like', '%'.$q.'%')
                            ->orWhere('name_en', 'like', '%'.$q.'%');
                    });

                if ($surahNumbers !== []) {
                    $query->orWhereIn('surah_number', $surahNumbers);
                }
            })
            ->take(6)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'qari' => $r->qari->name,
                'cover' => $r->cover_url,
                'url' => route('tilawa.show', $r->slug),
            ]);

        return response()->json(['qaris' => $qaris, 'tilawat' => $tilawat]);
    }

    /**
     * Resolve surah numbers whose Arabic name matches the query.
     *
     * @return array<int, int>
     */
    private function matchSurahNumbers(string $q): array
    {
        $needle = ltrim(mb_strtolower($q), 'ال');

        return collect(config('surahs', []))
            ->filter(function (string $name) use ($q, $needle) {
                $haystack = ltrim(mb_strtolower($name), 'ال');

                return mb_strpos($name, $q) !== false || mb_strpos($haystack, $needle) !== false;
            })
            ->keys()
            ->map(fn ($n) => (int) $n)
            ->all();
    }
}
