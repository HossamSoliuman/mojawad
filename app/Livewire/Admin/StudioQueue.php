<?php

namespace App\Livewire\Admin;

use App\Models\TilawatSource;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class StudioQueue extends Component
{
    #[Computed]
    public function sources()
    {
        return TilawatSource::with(['qari', 'tilawa'])
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('created_by', Auth::id()))
            ->latest()
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function hasActive(): bool
    {
        return $this->sources->contains(fn (TilawatSource $s) => in_array($s->status, ['pending', 'processing'], true));
    }

    public function render()
    {
        return view('livewire.admin.studio-queue');
    }
}
