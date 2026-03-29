<?php

namespace App\Http\Livewire;

use App\Models\Video;
use App\Notifications\VideoStatusChanged;
use Livewire\Component;
use Livewire\WithPagination;

class PendingQueue extends Component
{
    use WithPagination;

    public function approve(int $videoId): void
    {
        $video = Video::with(['qari', 'surah', 'uploader'])->findOrFail($videoId);
        $video->update(['status' => 'approved']);
        $video->uploader->notify(new VideoStatusChanged($video, 'approved'));
        session()->flash('success', 'Video approved.');
    }

    public function reject(int $videoId): void
    {
        $video = Video::with(['qari', 'surah', 'uploader'])->findOrFail($videoId);
        $video->update(['status' => 'rejected']);
        $video->uploader->notify(new VideoStatusChanged($video, 'rejected'));
        session()->flash('success', 'Video rejected.');
    }

    public function render()
    {
        $videos = Video::pending()->with(['qari', 'surah', 'uploader'])->latest()->paginate(10);
        return view('livewire.pending-queue', compact('videos'));
    }
}
