<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTilawaRequest;
use App\Http\Requests\UpdateTilawaRequest;
use App\Jobs\UploadToArchiveJob;
use App\Models\{Qari, Tilawa};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Cache, Storage};
use Illuminate\Support\Str;

class TilawaController extends Controller
{
    public function index(Request $request)
    {
        $tilawat = Tilawa::with('qari')
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->when($request->status,  fn($q) => $q->where('status', $request->status))
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

        $archiveFilename = Str::slug($request->title) . '-' . time() . '.mp3';
        $tempPath        = 'tilawa-temp/' . $archiveFilename;

        $request->file('audio')->storeAs('tilawa-temp', $archiveFilename, 'local');

        $tilawa = Tilawa::create([
            'qari_id'        => $request->qari_id,
            'title'          => $request->title,
            'slug'           => $slug,
            'description'    => $request->description,
            'recorded_at'    => $request->recorded_at,
            'recorded_place' => $request->recorded_place,
            'audio_path'     => null,
            'archive_url'    => null,
            'duration'       => 0,
            'cover_image'    => $request->hasFile('cover_image')
                ? $request->file('cover_image')->store('tilawa-covers', 'public')
                : null,
            'status'         => $request->status,
            'is_featured'    => $request->boolean('is_featured'),
            'uploaded_by'    => auth()->id(),
            'upload_status'  => 'pending',
        ]);

        UploadToArchiveJob::dispatch($tilawa, $tempPath, $archiveFilename, [
            'title'   => $request->title,
            'creator' => Qari::find($request->qari_id)->name ?? '',
            'subject' => 'Quran;Tilawat;Islamic Audio',
        ]);

        Cache::forget('homepage_data');

        return redirect()->route('admin.tilawat.uploading', $tilawa);
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

        if ($request->hasFile('audio')) {
            $archiveFilename = Str::slug($request->title) . '-' . time() . '.mp3';
            $tempPath        = 'tilawa-temp/' . $archiveFilename;

            $request->file('audio')->storeAs('tilawa-temp', $archiveFilename, 'local');

            $updates['upload_status'] = 'pending';
            $updates['upload_error']  = null;

            $tilawa->update($updates);

            UploadToArchiveJob::dispatch($tilawa, $tempPath, $archiveFilename, [
                'title'   => $request->title,
                'creator' => Qari::find($request->qari_id)->name ?? '',
                'subject' => 'Quran;Tilawat;Islamic Audio',
            ]);

            Cache::forget('homepage_data');

            return redirect()->route('admin.tilawat.uploading', $tilawa);
        }

        if ($request->hasFile('cover_image')) {
            if ($tilawa->cover_image) Storage::disk('public')->delete($tilawa->cover_image);
            $updates['cover_image'] = $request->file('cover_image')->store('tilawa-covers', 'public');
        }

        $tilawa->update($updates);
        Cache::forget('homepage_data');

        return redirect()->route('admin.tilawat.index')
            ->with('success', 'Tilawa updated successfully.');
    }

    public function destroy(Tilawa $tilawa)
    {
        Storage::disk('public')->delete($tilawa->audio_path);
        if ($tilawa->cover_image) Storage::disk('public')->delete($tilawa->cover_image);
        $tilawa->delete();
        Cache::forget('homepage_data');

        return redirect()->route('admin.tilawat.index')->with('success', 'Tilawa deleted.');
    }
}
