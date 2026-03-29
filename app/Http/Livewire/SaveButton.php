<?php

namespace App\Http\Livewire;

use App\Models\Video;
use App\Models\VideoSave;
use Livewire\Component;

class SaveButton extends Component
{
    public Video $video;
    public bool $saved = false;
    public int $count = 0;

    public function mount(Video $video): void
    {
        $this->video = $video;
        $this->count = $video->saves;
        $this->saved = auth()->check()
            && VideoSave::where('user_id', auth()->id())->where('video_id', $video->id)->exists();
    }

    public function toggle()
    {
        if (!auth()->check()) {
            return $this->redirect(route('login'));
        }

        $existing = VideoSave::where('user_id', auth()->id())->where('video_id', $this->video->id)->first();

        if ($existing) {
            $existing->delete();
            $this->video->decrement('saves');
            $this->saved = false;
            $this->count--;
        } else {
            VideoSave::create(['user_id' => auth()->id(), 'video_id' => $this->video->id]);
            $this->video->increment('saves');
            $this->saved = true;
            $this->count++;
        }

        $this->video->recomputeScore();
    }

    public function render()
    {
        return view('livewire.save-button');
    }
}
