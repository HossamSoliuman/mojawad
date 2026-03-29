<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideoRequest;
use App\Models\Qari;
use App\Models\Surah;
use App\Models\Video;

class VideoController extends Controller
{
    public function show(Video $video)
    {
        abort_if(!$video->isApproved() && !auth()->user()?->canModerate(), 404);

        $video->increment('views');
        $video->recomputeScore();
        $video->load(['qari', 'surah']);

        return view('videos.show', ['video' => $video]);
    }

    public function create()
    {
        $qaris = Qari::orderBy('name_en')->get();
        $surahs = Surah::all();
        return view('videos.create', compact('qaris', 'surahs'));
    }

    public function store(StoreVideoRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $data['status'] = auth()->user()->canModerate() ? 'approved' : 'pending';

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('videos', 'public');
            $data['type'] = 'hosted';
        } else {
            $data['type'] = 'linked';
        }

        Video::create($data);

        $message = $data['status'] === 'approved' ? 'Video published.' : 'Video submitted for review.';

        return redirect()->route('home')->with('success', $message);
    }
}
