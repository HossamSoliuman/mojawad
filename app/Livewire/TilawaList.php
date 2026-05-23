<?php

namespace App\Livewire;

use App\Models\Qari;
use Livewire\Component;

class TilawaList extends Component
{
    public Qari $qari;
    public string $sort = 'latest';
    public int $perPage = 20;

    public function updatedSort(): void
    {
        $this->perPage = 20;
    }

    public function loadMore(): void
    {
        $this->perPage += 20;
    }

    public function render()
    {
        $query = $this->qari->tilawat()->with('qari')->where('status', 'active');

        match ($this->sort) {
            'popular' => $query->orderByDesc('likes_count'),
            'oldest'  => $query->oldest(),
            default   => $query->latest(),
        };

        $rows    = $query->take($this->perPage + 1)->get();
        $hasMore = $rows->count() > $this->perPage;
        $tilawat = $rows->take($this->perPage);

        return view('livewire.tilawa-list', compact('tilawat', 'hasMore'));
    }
}
