<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Qari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Storage};
use Illuminate\Support\Str;

class QariController extends Controller
{
    public function index(Request $request)
    {
        $qaris = Qari::withCount('tilawat')->when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))->when($request->status, fn($q) => $q->where('status', $request->status))->latest()->paginate(15)->withQueryString();
        return view('admin.qaris.index', compact('qaris'));
    }
    public function create()
    {
        return view('admin.qaris.create');
    }
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255', 'biography' => 'nullable|string', 'status' => 'required|in:active,inactive,pending', 'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096']);
        $slug = Str::slug($request->name);
        if (Qari::where('slug', $slug)->exists()) $slug .= '-' . Str::random(4);
        Qari::create(['name' => $request->name, 'slug' => $slug, 'biography' => $request->biography ?? null, 'status' => $request->status, 'is_featured' => $request->boolean('is_featured'), 'image' => $request->hasFile('image') ? $request->file('image')->store('qari-images', 'public') : null, 'created_by' => auth()->id()]);
        Cache::forget('homepage_data');
        return redirect()->route('admin.qaris.index')->with('success', 'Qari created successfully.');
    }
    public function edit(Qari $qari)
    {
        return view('admin.qaris.edit', compact('qari'));
    }
    public function update(Request $request, Qari $qari)
    {
        $request->validate(['name' => 'required|string|max:255', 'biography' => 'nullable|string', 'status' => 'required|in:active,inactive,pending', 'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096']);
        $updates = ['name' => $request->name, 'biography' => $request->biography ?? null, 'status' => $request->status, 'is_featured' => $request->boolean('is_featured')];
        if ($request->hasFile('image')) {
            if ($qari->image) Storage::disk('public')->delete($qari->image);
            $updates['image'] = $request->file('image')->store('qari-images', 'public');
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
