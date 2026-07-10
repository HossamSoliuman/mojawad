<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTilawaRequest;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Services\AudioDurationService;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TilawaController extends Controller
{
    public function __construct(private FileUploadService $uploadService) {}

    public function index(Request $request)
    {
        $tilawat = Tilawa::with('qari', 'uploader')
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('uploaded_by', Auth::id()))
            ->when($request->search, fn ($q) => $q->where(fn ($sub) => $sub->where('title_ar', 'like', '%'.$request->search.'%')->orWhere('title_en', 'like', '%'.$request->search.'%')))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->review, fn ($q) => $q->where('review_status', $request->review))
            ->when($request->qari, fn ($q) => $q->where('qari_id', $request->qari))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $qaris = Qari::orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'image'])
            ->map(fn (Qari $q) => [
                'id' => $q->id,
                'name' => $q->name,
                'image' => $q->image_url,
            ]);

        return view('admin.tilawat.index', compact('tilawat', 'qaris'));
    }

    public function uploader(Request $request)
    {
        $qaris = Qari::where('status', 'active')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'image'])
            ->map(fn (Qari $q) => [
                'id' => $q->id,
                'name' => $q->name,
                'image' => $q->image_url,
            ]);

        $defaultQari = null;
        if ($id = $request->session()->get('uploader_qari_id')) {
            $defaultQari = $qaris->firstWhere('id', $id);
        }

        return view('admin.tilawat.uploader', [
            'qaris' => $qaris,
            'defaultQari' => $defaultQari,
            'titleMode' => $request->session()->get('uploader_title_mode', 'filename'),
        ]);
    }

    public function setDefaultQari(Request $request): JsonResponse
    {
        $data = $request->validate([
            'qari_id' => 'nullable|exists:qaris,id',
            'title_mode' => 'nullable|in:filename,manual',
        ]);

        if (array_key_exists('title_mode', $data) && $data['title_mode']) {
            $request->session()->put('uploader_title_mode', $data['title_mode']);
        }

        if (empty($data['qari_id'])) {
            $request->session()->forget('uploader_qari_id');

            return response()->json(['success' => true, 'cleared' => true]);
        }

        $qari = Qari::where('status', 'active')->findOrFail($data['qari_id']);
        $request->session()->put('uploader_qari_id', $qari->id);

        return response()->json([
            'success' => true,
            'qari' => ['id' => $qari->id, 'name' => $qari->name, 'image' => $qari->image_url],
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'qari_id' => 'required|exists:qaris,id',
            'audio_tmp' => 'required|string|exists:tmp_uploads,id',
            'title' => 'required|string|max:255',
        ]);

        $qari = Qari::where('status', 'active')->findOrFail($data['qari_id']);

        $title = trim($data['title']);
        $base = Str::slug($title) ?: 'tilawa';
        $slug = $base;
        while (Tilawa::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::random(6);
        }

        $audioPath = $this->uploadService->moveFromTmp($data['audio_tmp'], 'tilawat');

        $tilawa = Tilawa::create([
            'qari_id' => $qari->id,
            'title_ar' => $title,
            'title_en' => null,
            'slug' => $slug,
            'audio_path' => $audioPath,
            'duration' => AudioDurationService::getSeconds($audioPath),
            'cover_image' => null,
            'status' => 'pending',
            'review_status' => 'pending',
            'is_featured' => false,
            'uploaded_by' => Auth::id(),
        ]);

        $tilawa->logReview('submitted');

        Cache::forget('homepage_data');

        return response()->json([
            'success' => true,
            'id' => $tilawa->id,
            'title' => $tilawa->title_ar,
        ]);
    }

    public function edit(Tilawa $tilawa)
    {
        $qaris = Qari::where('status', 'active')
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('created_by', Auth::id()))
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'image']);

        return view('admin.tilawat.edit', compact('tilawa', 'qaris'));
    }

    public function update(UpdateTilawaRequest $request, Tilawa $tilawa)
    {
        $updates = [
            'qari_id' => $request->qari_id,
            'title_ar' => $request->title_ar,
            'title_en' => $request->title_en,
            'description_ar' => $request->description_ar,
            'description_en' => $request->description_en,
            'recorded_at' => $request->recorded_at,
            'recorded_place' => $request->recorded_place,
            'is_featured' => $request->boolean('is_featured'),
        ];

        $isAdmin = Auth::user()->hasRole('admin');
        $resubmitted = false;

        if ($isAdmin) {
            $updates['status'] = $request->status ?? $tilawa->status;
            if ($updates['status'] === 'active') {
                $updates['review_status'] = 'approved';
            }
        } else {
            $updates['status'] = 'pending';
            $updates['review_status'] = 'pending';
            $updates['rejection_note'] = null;
            $resubmitted = true;
        }

        if ($request->filled('audio_tmp')) {
            $this->uploadService->delete($tilawa->audio_path);
            $newAudioPath = $this->uploadService->moveFromTmp($request->audio_tmp, 'tilawat');
            $updates['audio_path'] = $newAudioPath;
            $updates['duration'] = AudioDurationService::getSeconds($newAudioPath);
        }

        if ($request->filled('cover_image_tmp')) {
            $this->uploadService->delete($tilawa->cover_image);
            $updates['cover_image'] = $this->uploadService->moveFromTmp($request->cover_image_tmp, 'tilawa-covers');
        }

        $tilawa->update($updates);

        if ($resubmitted) {
            $tilawa->logReview('resubmitted');
        }

        Cache::forget('homepage_data');

        return redirect()->route('admin.tilawat.index')
            ->with('success', $resubmitted
                ? 'Tilawa resubmitted for review.'
                : 'Tilawa updated successfully.');
    }

    public function quickUpdate(Request $request, Tilawa $tilawa)
    {
        $data = $request->validate([
            'status' => 'nullable|in:active,inactive,pending',
            'is_featured' => 'nullable|boolean',
        ]);

        $updates = [];

        if (array_key_exists('status', $data) && $data['status'] !== null) {
            $updates['status'] = $data['status'];
            if ($data['status'] === 'active') {
                $updates['review_status'] = 'approved';
            }
        }

        if ($request->has('is_featured')) {
            $updates['is_featured'] = $request->boolean('is_featured');
        }

        $tilawa->update($updates);
        Cache::forget('homepage_data');

        return back()->with('success', 'Tilawa updated.');
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
