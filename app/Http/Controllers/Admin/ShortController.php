<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShortRequest;
use App\Http\Requests\UpdateShortRequest;
use App\Models\Short;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ShortController extends Controller
{
    public function __construct(private FileUploadService $uploadService) {}

    public function index(Request $request): View
    {
        $shorts = Short::with('creator')
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
        return view('admin.shorts.create');
    }

    public function store(StoreShortRequest $request): RedirectResponse
    {
        Short::create([
            'title_ar' => $request->title_ar,
            'title_en' => $request->title_en,
            'type' => $request->type,
            'media_path' => $this->uploadService->moveFromTmp($request->media_tmp, 'shorts'),
            'poster_path' => $request->filled('poster_tmp')
                ? $this->uploadService->moveFromTmp($request->poster_tmp, 'short-posters')
                : null,
            'sort_order' => $request->integer('sort_order'),
            'status' => $request->status,
            'created_by' => Auth::id(),
        ]);

        Cache::forget('active_hero_shorts');

        return redirect()->route('admin.shorts.index')->with('success', __('Short created successfully.'));
    }

    public function edit(Short $short): View
    {
        return view('admin.shorts.edit', compact('short'));
    }

    public function update(UpdateShortRequest $request, Short $short): RedirectResponse
    {
        $updates = [
            'title_ar' => $request->title_ar,
            'title_en' => $request->title_en,
            'type' => $request->type,
            'sort_order' => $request->integer('sort_order'),
            'status' => $request->status,
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

        Cache::forget('active_hero_shorts');

        return redirect()->route('admin.shorts.index')->with('success', __('Short updated successfully.'));
    }

    public function destroy(Short $short): RedirectResponse
    {
        Storage::disk('public')->delete($short->media_path);
        if ($short->poster_path) {
            Storage::disk('public')->delete($short->poster_path);
        }
        $short->delete();

        Cache::forget('active_hero_shorts');

        return redirect()->route('admin.shorts.index')->with('success', __('Short deleted.'));
    }
}
