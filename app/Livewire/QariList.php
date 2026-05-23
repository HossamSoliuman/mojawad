<?php

namespace App\Livewire;

use App\Models\Qari;
use Livewire\Component;

class QariList extends Component
{
    public string $search = '';
    public string $sort = 'featured';
    public int $perPage = 24;

    protected $queryString = [
        'search' => ['except' => ''],
        'sort'   => ['except' => 'featured'],
    ];

    public function updatedSearch(): void
    {
        $this->perPage = 24;
    }

    public function updatedSort(): void
    {
        $this->perPage = 24;
    }

    public function loadMore(): void
    {
        $this->perPage += 24;
    }

    public function render()
    {
        $query = Qari::where('status', 'active')
            ->withCount(['tilawat' => fn($q) => $q->where('status', 'active')]);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name_ar', 'like', '%' . $this->search . '%')
                  ->orWhere('name_en', 'like', '%' . $this->search . '%');
            });
        }

        match ($this->sort) {
            'name'  => $query->orderBy('name_ar'),
            'count' => $query->orderByDesc('tilawat_count'),
            default => $query->orderByDesc('is_featured')->orderByDesc('tilawat_count'),
        };

        // Fetch one extra to know if more exist
        $rows    = $query->take($this->perPage + 1)->get();
        $hasMore = $rows->count() > $this->perPage;
        $qaris   = $rows->take($this->perPage);

        return view('livewire.qari-list', compact('qaris', 'hasMore'));
    }
}
