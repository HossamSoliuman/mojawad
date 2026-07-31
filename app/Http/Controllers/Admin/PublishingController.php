<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\IngestSourceRequest;
use App\Jobs\IngestSourceAudio;
use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublishingController extends Controller
{
    public function __construct(private FileUploadService $uploads) {}

    public function factory(): View
    {
        return view('admin.publishing.factory');
    }

    public function production(): View
    {
        return view('admin.publishing.production');
    }

    public function cardLab(): View
    {
        return view('admin.publishing.card-lab');
    }

    public function downloadVideo(Tilawa $tilawa): StreamedResponse
    {
        $disk = Storage::disk(config('publishing.disk'));

        abort_unless($tilawa->brand_video_path && $disk->exists($tilawa->brand_video_path), 404);

        $slug = Str::slug($tilawa->title_en ?: $tilawa->title_ar);
        $name = ($slug !== '' ? $slug : 'mojawad-tilawa').'-'.$tilawa->id.'.mp4';

        return $disk->download($tilawa->brand_video_path, $name);
    }

    public function ingest(IngestSourceRequest $request): RedirectResponse
    {
        $qariId = (int) $request->input('qari_id');

        $sourcePath = $this->uploads->moveFromTmp(
            (string) $request->input('audio_tmp'),
            config('publishing.source_dir').'/'.$qariId,
        );

        $source = TilawatSource::create([
            'source_type' => 'upload',
            'source_url' => $sourcePath,
            'qari_id' => $qariId,
            'surah_number' => (int) $request->input('surah_number'),
            'status' => 'pending',
            'created_by' => Auth::id(),
        ]);

        IngestSourceAudio::dispatch($source->id);

        return redirect()->route('admin.publishing.factory')
            ->with('success', __('Source queued for cleaning & ingest.'));
    }
}
