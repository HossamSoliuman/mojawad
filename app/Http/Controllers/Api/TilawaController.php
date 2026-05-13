<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TilawaResource;
use App\Models\DownloadLog;
use App\Models\Like;
use App\Models\SavedTilawa;
use App\Models\Tilawa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TilawaController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $tilawa = Tilawa::where('slug', $slug)
            ->where('status', 'active')
            ->with('qari')
            ->firstOrFail();

        $related = Tilawa::where('qari_id', $tilawa->qari_id)
            ->where('id', '!=', $tilawa->id)
            ->where('status', 'active')
            ->take(6)
            ->get();

        $liked = false;
        $saved = false;

        if ($request->user()) {
            $liked = Like::where('user_id', $request->user()->id)
                ->where('tilawa_id', $tilawa->id)
                ->exists();

            $saved = SavedTilawa::where('user_id', $request->user()->id)
                ->where('tilawa_id', $tilawa->id)
                ->exists();
        }

        return response()->json([
            'tilawa'  => (new TilawaResource($tilawa))->toArray($request),
            'related' => TilawaResource::collection($related),
            'liked'   => $liked,
            'saved'   => $saved,
        ]);
    }

    public function download(Request $request, Tilawa $tilawa)
    {
        abort_if($tilawa->status !== 'active', 403);

        DownloadLog::create([
            'user_id'    => $request->user()?->id,
            'tilawa_id'  => $tilawa->id,
            'ip_address' => $request->ip(),
        ]);

        $tilawa->increment('downloads_count');

        return Storage::disk('public')->download(
            $tilawa->audio_path,
            Str::slug($tilawa->title) . '.mp3'
        );
    }
}
