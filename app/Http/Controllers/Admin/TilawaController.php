<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTilawaRequest;
use App\Http\Requests\UpdateTilawaRequest;
use App\Models\{Qari, Tilawa};
use App\Services\FileUploadService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Auth, Cache};
use Illuminate\Support\Str;

class TilawaController extends Controller
{
    public function __construct(private FileUploadService $uploadService)
    {
    }
    public function index(Request $request)
    {
        $tilawat = Tilawa::with('qari')
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.tilawat.index', compact('tilawat'));
    }

    public function create()
    {
        $qaris = Qari::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        return view('admin.tilawat.create', compact('qaris'));
    }

    public function store(StoreTilawaRequest $request)
    {
        $slug = Str::slug($request->title);
        if (Tilawa::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::random(4);
        }

        $audioPath = $this->uploadService->moveFromTmp($request->audio_tmp, 'tilawat');

        $tilawa = Tilawa::create([
            'qari_id'        => $request->qari_id,
            'title'          => $request->title,
            'slug'           => $slug,
            'description'    => $request->description,
            'recorded_at'    => $request->recorded_at,
            'recorded_place' => $request->recorded_place,
            'audio_path'     => $audioPath,
            'archive_url'    => null,
            'duration'       => 0,
            'cover_image'    => $request->filled('cover_image_tmp')
                ? $this->uploadService->moveFromTmp($request->cover_image_tmp, 'tilawa-covers')
                : null,
            'status'         => $request->status,
            'is_featured'    => $request->boolean('is_featured'),
            'uploaded_by'    => Auth::id(),
            'upload_status'  => 'done',
        ]);

        Cache::forget('homepage_data');

        return redirect()->route('admin.tilawat.index')
            ->with('success', 'Tilawa uploaded successfully.');
    }

    public function uploading(Tilawa $tilawa)
    {
        return view('admin.tilawat.uploading', compact('tilawa'));
    }

    public function uploadStatus(Tilawa $tilawa): JsonResponse
    {
        return response()->json([
            'status'      => $tilawa->upload_status,
            'error'       => $tilawa->upload_error,
            'archive_url' => $tilawa->archive_url,
        ]);
    }

    public function edit(Tilawa $tilawa)
    {
        $qaris = Qari::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        return view('admin.tilawat.edit', compact('tilawa', 'qaris'));
    }

    public function update(UpdateTilawaRequest $request, Tilawa $tilawa)
    {
        $updates = [
            'qari_id'        => $request->qari_id,
            'title'          => $request->title,
            'description'    => $request->description,
            'recorded_at'    => $request->recorded_at,
            'recorded_place' => $request->recorded_place,
            'status'         => $request->status,
            'is_featured'    => $request->boolean('is_featured'),
        ];

        if ($request->filled('audio_tmp')) {
            $this->uploadService->delete($tilawa->audio_path);
            $updates['audio_path']    = $this->uploadService->moveFromTmp($request->audio_tmp, 'tilawat');
            $updates['upload_status'] = 'done';
            $updates['upload_error']  = null;
        }

        if ($request->filled('cover_image_tmp')) {
            $this->uploadService->delete($tilawa->cover_image);
            $updates['cover_image'] = $this->uploadService->moveFromTmp($request->cover_image_tmp, 'tilawa-covers');
        }

        $tilawa->update($updates);
        Cache::forget('homepage_data');

        return redirect()->route('admin.tilawat.index')
            ->with('success', 'Tilawa updated successfully.');
    }

    public function destroy(Tilawa $tilawa)
    {
        $this->uploadService->delete($tilawa->audio_path);
        $this->uploadService->delete($tilawa->cover_image);
        $tilawa->delete();
        Cache::forget('homepage_data');

        return redirect()->route('admin.tilawat.index')->with('success', 'Tilawa deleted.');
    }
}
