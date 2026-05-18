<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQariRequest;
use App\Http\Requests\UpdateQariRequest;
use App\Models\Qari;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Cache, Storage};
use Illuminate\Support\Str;

class QariController extends Controller
{
    public function __construct(private FileUploadService $uploadService)
    {
    }
    public function index(Request $request)
    {
        $qaris = Qari::withCount('tilawat')
            ->when(!Auth::user()->hasRole('admin'), fn($q) => $q->where('created_by', Auth::id()))
            ->when($request->search, fn($q) => $q->where(fn($sub) => $sub->where('name_ar', 'like', '%' . $request->search . '%')->orWhere('name_en', 'like', '%' . $request->search . '%')))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();
        return view('admin.qaris.index', compact('qaris'));
    }
    
    public function create()
    {
        return view('admin.qaris.create');
    }

    public function store(StoreQariRequest $request)
    {
        $slug = Str::slug($request->name_ar);
        if (Qari::where('slug', $slug)->exists()) $slug .= '-' . Str::random(4);
        Qari::create(
            [
                'name_ar'     => $request->name_ar,
                'name_en'     => $request->name_en,
                'slug'        => $slug,
                'biography_ar'=> $request->biography_ar ?? null,
                'biography_en'=> $request->biography_en ?? null,
                'status'      => $request->status,
                'is_featured' => $request->boolean('is_featured'),
                'image'       => $request->filled('image_tmp')
                    ? $this->uploadService->moveFromTmp($request->image_tmp, 'qari-images')
                    : null,
                'created_by'  => Auth::id()
            ]
        );
        Cache::forget('homepage_data');
        return redirect()->route('admin.qaris.index')->with('success', 'Qari created successfully.');
    }

    public function edit(Qari $qari)
    {
        return view('admin.qaris.edit', compact('qari'));
    }

    public function update(UpdateQariRequest $request, Qari $qari)
    {
        $updates = ['name_ar' => $request->name_ar, 'name_en' => $request->name_en, 'biography_ar' => $request->biography_ar ?? null, 'biography_en' => $request->biography_en ?? null, 'status' => $request->status, 'is_featured' => $request->boolean('is_featured')];
        if ($request->filled('image_tmp')) {
            if ($qari->image) Storage::disk('public')->delete($qari->image);
            $updates['image'] = $this->uploadService->moveFromTmp($request->image_tmp, 'qari-images');
        }
        $qari->update($updates);
        Cache::forget('homepage_data');
        return redirect()->route('admin.qaris.index')->with('success', 'Qari updated successfully.');
    }

    public function destroy(Qari $qari)
    {
        if ($qari->image) Storage::disk('public')->delete($qari->image);
        $qari->delete();
        Cache::forget('homepage_data');
        return redirect()->route('admin.qaris.index')->with('success', 'Qari deleted.');
    }
}
