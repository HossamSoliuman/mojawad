<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQariRequest;
use App\Models\Qari;
use Illuminate\Support\Facades\Storage;

class QariController extends Controller
{
    public function show(Qari $qari)
    {
        $videos = $qari->videos()->with('surah')->paginate(12);
        return view('qaris.show', compact('qari', 'videos'));
    }

    public function create()
    {
        return view('qaris.create');
    }

    public function store(StoreQariRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('qaris', 'public');
        }

        Qari::create($data);

        return redirect()->route('home')->with('success', 'Qari created.');
    }

    public function edit(Qari $qari)
    {
        return view('qaris.edit', compact('qari'));
    }

    public function update(StoreQariRequest $request, Qari $qari)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($qari->photo) {
                Storage::disk('public')->delete($qari->photo);
            }
            $data['photo'] = $request->file('photo')->store('qaris', 'public');
        }

        $qari->update($data);

        return redirect()->route('qaris.show', $qari)->with('success', 'Qari updated.');
    }
}
