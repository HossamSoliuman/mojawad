<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioImportFileRequest;
use App\Http\Requests\StudioImportRequest;
use App\Jobs\ImportTilawaFromYoutube;
use App\Models\Qari;
use App\Models\TilawatSource;
use App\Services\YoutubeImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudioController extends Controller
{
    public function __construct(private YoutubeImportService $youtube) {}

    public function index(): View
    {
        $qaris = Qari::where('status', 'active')
            ->ordered()
            ->get(['id', 'name_ar', 'name_en']);

        return view('admin.studio.index', compact('qaris'));
    }

    public function store(StudioImportRequest $request): RedirectResponse
    {
        $urls = $this->youtube->parseUrls((string) $request->input('urls'));

        $count = $this->queueImports($urls, (int) $request->input('qari_id'));

        return redirect()->route('admin.studio.index')
            ->with('success', trans_choice(':count link queued for import.|:count links queued for import.', $count, ['count' => $count]));
    }

    public function storeFile(StudioImportFileRequest $request): RedirectResponse
    {
        $contents = (string) file_get_contents($request->file('file')->getRealPath());
        $urls = $this->youtube->parseUrls($contents);

        if ($urls === []) {
            return redirect()->route('admin.studio.index')
                ->with('error', __('No valid YouTube links were found.'));
        }

        $count = $this->queueImports($urls, (int) $request->input('qari_id'));

        return redirect()->route('admin.studio.index')
            ->with('success', trans_choice(':count link queued for import.|:count links queued for import.', $count, ['count' => $count]));
    }

    public function retry(TilawatSource $source): RedirectResponse
    {
        $source->update(['status' => 'pending', 'error' => null, 'processed_at' => null]);

        ImportTilawaFromYoutube::dispatch($source->id);

        return redirect()->route('admin.studio.index')->with('success', __('Import re-queued.'));
    }

    public function destroy(TilawatSource $source): RedirectResponse
    {
        $source->delete();

        return redirect()->route('admin.studio.index')->with('success', __('Source removed.'));
    }

    /**
     * @param  list<string>  $urls
     */
    private function queueImports(array $urls, int $qariId): int
    {
        foreach ($urls as $url) {
            $source = TilawatSource::create([
                'source_type' => 'youtube',
                'source_url' => $url,
                'source_video_id' => $this->youtube->extractVideoId($url),
                'qari_id' => $qariId,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            ImportTilawaFromYoutube::dispatch($source->id);
        }

        return count($urls);
    }
}
