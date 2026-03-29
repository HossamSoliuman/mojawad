<?php

namespace App\Http\Livewire;

use App\Models\Video;
use App\Models\VideoLike;
use Livewire\Component;

class LikeButton extends Component
{
    public Video $video;
    public bool $liked = false;
    public int $count = 0;

    public function mount(Video $video): void
    {
        $this->video = $video;
        $this->count = $video->likes;
        $this->liked = auth()->check()
            && VideoLike::where('user_id', auth()->id())->where('video_id', $video->id)->exists();
    }

    public function toggle()
    {
        if (!auth()->check()) {
            return $this->redirect(route('login'));
        }

        $existing = VideoLike::where('user_id', auth()->id())->where('video_id', $this->video->id)->first();

        if ($existing) {
            $existing->delete();
            $this->video->decrement('likes');
            $this->liked = false;
            $this->count--;
        } else {
            VideoLike::create(['user_id' => auth()->id(), 'video_id' => $this->video->id]);
            $this->video->increment('likes');
            $this->liked = true;
            $this->count++;
        }

        $this->video->recomputeScore();
    }

    public function render()
    {
        return view('livewire.like-button');
    }
}
