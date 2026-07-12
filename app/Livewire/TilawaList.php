<?php

namespace App\Livewire;

use App\Models\Qari;
use Livewire\Component;

class TilawaList extends Component
{
    public Qari $qari;

    public string $search = '';

    public string $sort = 'latest';

    public ?int $surah = null;

    public int $perPage = 20;

    protected $queryString = [
        'search' => ['except' => ''],
        'sort' => ['except' => 'latest'],
        'surah' => ['except' => null],
    ];

    public function updatedSearch(): void
    {
        $this->perPage = 20;
    }

    public function updatedSort(): void
    {
        $this->perPage = 20;
    }

    public function updatedSurah(): void
    {
        $this->surah = $this->surah ?: null;
        $this->perPage = 20;
    }

    public function loadMore(): void
    {
        $this->perPage += 20;
    }

    public function render()
    {
        $query = $this->qari->tilawat()->with('qari')->where('status', 'active');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title_ar', 'like', '%'.$this->search.'%')
                    ->orWhere('title_en', 'like', '%'.$this->search.'%')
                    ->orWhere('recorded_place', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->surah) {
            $query->forSurah($this->surah);
        }

        match ($this->sort) {
            'popular' => $query->orderByDesc('likes_count'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        $rows = $query->take($this->perPage + 1)->get();
        $hasMore = $rows->count() > $this->perPage;
        $tilawat = $rows->take($this->perPage);

        $surahs = $this->qari->tilawat()
            ->where('status', 'active')
            ->whereNotNull('surah_number')
            ->distinct()
            ->orderBy('surah_number')
            ->pluck('surah_number');

        return view('livewire.tilawa-list', compact('tilawat', 'hasMore', 'surahs'));
    }
}
