<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShortRequest;
use App\Http\Requests\UpdateShortRequest;
use App\Jobs\ImportTiktokShort;
use App\Models\Qari;
use App\Models\Short;
use App\Services\FileUploadService;
use App\Services\TiktokImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ShortController extends Controller
{
    public function __construct(
        private FileUploadService $uploadService,
        private TiktokImportService $tiktok,
    ) {}

    public function index(Request $request): View
    {
        $shorts = Short::with(['creator', 'qari'])
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('created_by', Auth::id()))
            ->when($request->search, fn ($q) => $q->where(fn ($sub) => $sub->where('title_ar', 'like', '%'.$request->search.'%')->orWhere('title_en', 'like', '%'.$request->search.'%')))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.shorts.index', compact('shorts'));
    }

    public function create(): View
    {
        $qaris = Qari::where('status', 'active')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en']);

        return view('admin.shorts.create', compact('qaris'));
    }

    public function store(StoreShortRequest $request): RedirectResponse
    {
        if ($request->input('source') === 'tiktok') {
            return $this->storeFromTiktok($request);
        }

        $qari = Qari::find($request->integer('qari_id'));

        Short::create([
            'title_ar' => $qari?->name_ar ?? __('Short'),
            'title_en' => $qari?->name_en,
            'type' => $request->type,
            'qari_id' => $qari?->id,
            'media_path' => $this->uploadService->moveFromTmp($request->media_tmp, 'shorts'),
            'poster_path' => $request->filled('poster_tmp')
                ? $this->uploadService->moveFromTmp($request->poster_tmp, 'short-posters')
                : null,
            'sort_order' => $request->integer('sort_order'),
            'status' => $request->status,
            'created_by' => Auth::id(),
            'pinned_starts_at' => $request->filled('pinned_starts_at') ? $request->date('pinned_starts_at') : null,
            'pinned_ends_at' => $request->filled('pinned_ends_at') ? $request->date('pinned_ends_at') : null,
        ]);

        return redirect()->route('admin.shorts.index')->with('success', __('Short created successfully.'));
    }

    /**
     * Bulk-create video shorts from one or many TikTok URLs (pasted and/or read
     * from an uploaded link list), all linked to the chosen qari. Each short is
     * stored in a pending state and a queued job downloads the clip with yt-dlp.
     */
    private function storeFromTiktok(StoreShortRequest $request): RedirectResponse
    {
        $urls = $this->tiktok->parseUrls($this->collectTiktokUrls($request));

        if ($urls === []) {
            return redirect()->route('admin.shorts.create')
                ->withInput()
                ->with('error', __('No valid TikTok links were found.'));
        }

        $qari = Qari::find($request->integer('qari_id'));

        foreach ($urls as $url) {
            $short = Short::create([
                'title_ar' => $qari?->name_ar ?? __('TikTok video'),
                'title_en' => $qari?->name_en,
                'type' => 'video',
                'media_path' => null,
                'qari_id' => $qari?->id,
                'source_url' => $url,
                'import_status' => 'pending',
                'sort_order' => $request->integer('sort_order'),
                'status' => $request->status,
                'created_by' => Auth::id(),
            ]);

            ImportTiktokShort::dispatch($short->id);
        }

        $count = count($urls);

        return redirect()->route('admin.shorts.index')
            ->with('success', trans_choice(
                ':count TikTok video queued for download.|:count TikTok videos queued for download.',
                $count,
                ['count' => $count]
            ));
    }

    /**
     * Merge the pasted links with the contents of an uploaded link list.
     */
    private function collectTiktokUrls(StoreShortRequest $request): string
    {
        $blob = (string) $request->input('urls');

        if ($request->hasFile('urls_file')) {
            $blob .= "\n".(string) file_get_contents($request->file('urls_file')->getRealPath());
        }

        return $blob;
    }

    public function retryImport(Short $short): RedirectResponse
    {
        abort_unless($short->import_status === 'failed', 422);

        $short->update(['import_status' => 'pending', 'import_error' => null]);

        ImportTiktokShort::dispatch($short->id);

        return redirect()->route('admin.shorts.index')->with('success', __('Import re-queued.'));
    }

    public function importPoll(Request $request): JsonResponse
    {
        $ids = array_filter(array_map('intval', (array) $request->query('ids', [])));

        if (empty($ids)) {
            return response()->json([]);
        }

        return response()->json(
            Short::whereIn('id', $ids)->pluck('import_status', 'id')
        );
    }

    public function edit(Short $short): View
    {
        $qaris = Qari::where('status', 'active')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en']);

        return view('admin.shorts.edit', compact('short', 'qaris'));
    }

    public function update(UpdateShortRequest $request, Short $short): RedirectResponse
    {
        $updates = [
            'title_ar' => $request->title_ar,
            'title_en' => $request->title_en,
            'type' => $request->type,
            'qari_id' => $request->integer('qari_id') ?: null,
            'sort_order' => $request->integer('sort_order'),
            'status' => $request->status,
            'pinned_starts_at' => $request->filled('pinned_starts_at') ? $request->date('pinned_starts_at') : null,
            'pinned_ends_at' => $request->filled('pinned_ends_at') ? $request->date('pinned_ends_at') : null,
        ];

        if ($request->filled('media_tmp')) {
            Storage::disk('public')->delete($short->media_path);
            $updates['media_path'] = $this->uploadService->moveFromTmp($request->media_tmp, 'shorts');
        }

        if ($request->filled('poster_tmp')) {
            if ($short->poster_path) {
                Storage::disk('public')->delete($short->poster_path);
            }
            $updates['poster_path'] = $this->uploadService->moveFromTmp($request->poster_tmp, 'short-posters');
        }

        $short->update($updates);

        return redirect()->route('admin.shorts.index')->with('success', __('Short updated successfully.'));
    }

    public function destroy(Short $short): RedirectResponse
    {
        if ($short->media_path) {
            Storage::disk('public')->delete($short->media_path);
        }
        if ($short->poster_path) {
            Storage::disk('public')->delete($short->poster_path);
        }
        $short->delete();

        return redirect()->route('admin.shorts.index')->with('success', __('Short deleted.'));
    }
}
