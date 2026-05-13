<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QariResource;
use App\Http\Resources\TilawaResource;
use App\Models\Qari;
use Illuminate\Http\Request;

class QariController extends Controller
{
    public function index(Request $request)
    {
        $qaris = Qari::where('status', 'active')
            ->withCount(['tilawat' => fn ($q) => $q->where('status', 'active')])
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(20);

        return QariResource::collection($qaris);
    }

    public function show(string $slug)
    {
        $qari = Qari::where('slug', $slug)
            ->where('status', 'active')
            ->withCount(['tilawat' => fn ($q) => $q->where('status', 'active')])
            ->firstOrFail();

        $tilawat = $qari->tilawat()
            ->where('status', 'active')
            ->latest()
            ->paginate(20);

        return response()->json([
            'qari'    => new QariResource($qari),
            'tilawat' => TilawaResource::collection($tilawat),
        ]);
    }
}
