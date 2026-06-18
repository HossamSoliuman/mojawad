<?php

namespace App\Livewire\Admin;

use App\Models\VideoClip;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ClipQueue extends Component
{
    #[Computed]
    public function clips()
    {
        return VideoClip::with(['tilawa.qari'])
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('created_by', Auth::id()))
            ->latest()
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function hasActive(): bool
    {
        return $this->clips->contains(fn (VideoClip $c) => in_array($c->status, ['pending', 'processing'], true));
    }

    public function render()
    {
        return view('livewire.admin.clip-queue');
    }
}
