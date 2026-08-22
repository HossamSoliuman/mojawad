<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Contracts\View\View;

class CollectionController extends Controller
{
    public function index(): View
    {
        $collections = Collection::active()
            ->with(['tilawat' => fn ($q) => $q->where('status', 'active')->with('qari')])
            ->withCount(['tilawat as tilawat_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return view('pages.collections.index', compact('collections'));
    }

    public function show(Collection $collection): View
    {
        abort_unless($collection->is_active, 404);

        if (! $collection->isAuto()) {
            $collection->load(['tilawat' => fn ($q) => $q->where('status', 'active')->with('qari')]);
        }

        return view('pages.collections.show', [
            'collection' => $collection,
            'tilawat' => $collection->items(),
        ]);
    }
}
