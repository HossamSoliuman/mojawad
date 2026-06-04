<?php

namespace App\Livewire\Reviewer;

use App\Models\Tilawa;
use App\Models\TilawaReview;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewHistory extends Component
{
    use WithPagination;

    public string $action = '';

    public function mount(): void
    {
        $this->authorize('review', Tilawa::class);
    }

    public function updatingAction(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $reviews = TilawaReview::with(['tilawa.qari', 'reviewer'])
            ->when($this->action, fn ($q) => $q->where('action', $this->action))
            ->latest()
            ->paginate(20);

        return view('livewire.reviewer.review-history', compact('reviews'));
    }
}
