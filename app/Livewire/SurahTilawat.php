<?php

namespace App\Livewire;

use App\Models\Tilawa;
use Livewire\Component;

class SurahTilawat extends Component
{
    public int $surah;

    public string $sort = 'popular';

    public int $perPage = 20;

    protected $queryString = [
        'sort' => ['except' => 'popular'],
    ];

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
        $query = Tilawa::query()
            ->with('qari')
            ->where('status', 'active')
            ->forSurah($this->surah);

        match ($this->sort) {
            'latest' => $query->latest(),
            'downloads' => $query->orderByDesc('downloads_count'),
            default => $query->orderByDesc('likes_count'),
        };

        $rows = $query->take($this->perPage + 1)->get();
        $hasMore = $rows->count() > $this->perPage;
        $tilawat = $rows->take($this->perPage);

        return view('livewire.surah-tilawat', compact('tilawat', 'hasMore'));
    }
}
