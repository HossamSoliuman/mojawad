<?php

namespace App\Http\Controllers;

use App\Models\Qari;

class QariController extends Controller
{
    public function index()
    {
        $qaris = Qari::where('status', 'active')
            ->withCount(['tilawat' => fn($q) => $q->where('status', 'active')])
            ->orderByDesc('is_featured')->paginate(24);
        return view('pages.qari.index', compact('qaris'));
    }
    public function show(Qari $qari)
    {
        abort_if($qari->status !== 'active' && !auth()->user()?->hasRole('admin'), 403);
        $tilawat = $qari->tilawat()->where('status', 'active')->latest()->paginate(20);
        return view('pages.qari.show', compact('qari', 'tilawat'));
    }
}
