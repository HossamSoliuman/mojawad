<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClipStoreRequest;
use App\Http\Requests\ClipTiktokRequest;
use App\Jobs\RenderVideoClip;
use App\Models\Tilawa;
use App\Models\VideoClip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClipController extends Controller
{
    public function index(): View
    {
        return view('admin.clips.index', [
            'templates' => config('clips.templates'),
            'presets' => config('clips.presets'),
            'maxDuration' => config('clips.max_duration'),
        ]);
    }

    /**
     * Searchable tilawa picker for the editor — returns the fields the preview
     * and scrubber need (audio + duration), which the public search omits.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['tilawat' => []]);
        }

        $tilawat = Tilawa::with('qari')
            ->where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('title_ar', 'like', '%'.$q.'%')
                    ->orWhere('title_en', 'like', '%'.$q.'%')
                    ->orWhereHas('qari', function ($sub) use ($q) {
                        $sub->where('name_ar', 'like', '%'.$q.'%')
                            ->orWhere('name_en', 'like', '%'.$q.'%');
                    });
            })
            ->take(8)
            ->get()
            ->map(fn (Tilawa $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'qari' => $t->qari->name,
                'surah' => $t->surah_name,
                'cover' => $t->cover_url,
                'audio' => $t->audio_url,
                'duration' => (int) $t->duration,
            ]);

        return response()->json(['tilawat' => $tilawat]);
    }

    public function store(ClipStoreRequest $request): RedirectResponse
    {
        $overlayPath = $this->storeOverlay($request->input('overlay'));

        $clip = VideoClip::create([
            'tilawa_id' => (int) $request->input('tilawa_id'),
            'created_by' => Auth::id(),
            'template' => (string) $request->input('template'),
            'clip_start' => (int) $request->input('clip_start'),
            'clip_duration' => (int) $request->input('clip_duration'),
            'caption_ar' => $request->input('caption_ar'),
            'overlay_path' => $overlayPath,
            'status' => 'pending',
        ]);

        RenderVideoClip::dispatch($clip->id);

        return redirect()->route('admin.clips.index')
            ->with('success', __('Clip queued for rendering.'));
    }

    public function retry(VideoClip $clip): RedirectResponse
    {
        $clip->update(['status' => 'pending', 'error' => null]);

        RenderVideoClip::dispatch($clip->id);

        return redirect()->route('admin.clips.index')->with('success', __('Render re-queued.'));
    }

    public function tiktok(ClipTiktokRequest $request, VideoClip $clip): RedirectResponse
    {
        $clip->update(['tiktok_url' => $request->input('tiktok_url')]);

        return redirect()->route('admin.clips.index')->with('success', __('TikTok link saved.'));
    }

    public function destroy(VideoClip $clip): RedirectResponse
    {
        $disk = Storage::disk(config('clips.disk'));

        foreach ([$clip->output_path, $clip->poster_path, $clip->overlay_path] as $path) {
            if ($path) {
                $disk->delete($path);
            }
        }

        $clip->delete();

        return redirect()->route('admin.clips.index')->with('success', __('Clip removed.'));
    }

    public function download(VideoClip $clip): StreamedResponse
    {
        abort_unless($clip->output_path && Storage::disk(config('clips.disk'))->exists($clip->output_path), 404);

        $name = Str::slug(optional($clip->tilawa)->title ?: 'mojawad-clip').'-'.$clip->id.'.mp4';

        return Storage::disk(config('clips.disk'))->download($clip->output_path, $name);
    }

    private function storeOverlay(string $dataUrl): string
    {
        $data = base64_decode((string) preg_replace('#^data:image/png;base64,#', '', $dataUrl), true);

        $disk = Storage::disk(config('clips.disk'));
        $path = config('clips.overlay_dir').'/'.Str::uuid()->toString().'.png';

        $disk->put($path, $data);

        return $path;
    }
}
