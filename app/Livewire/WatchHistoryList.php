<?php

namespace App\Livewire;

use App\Models\WatchHistory;
use Livewire\Component;

class WatchHistoryList extends Component
{
    public int $perPage = 20;

    public function loadMore(): void
    {
        $this->perPage += 20;
    }

    public function remove(int $tilawaId): void
    {
        WatchHistory::where('user_id', auth()->id())->where('tilawa_id', $tilawaId)->delete();
        $this->dispatch('tilawa-history-changed');
    }

    public function clearAll(): void
    {
        WatchHistory::where('user_id', auth()->id())->delete();
        $this->dispatch('tilawa-history-changed');
    }

    public function render()
    {
        $rows = auth()->user()->watchHistories()
            ->with('tilawa.qari')
            ->whereHas('tilawa', fn ($q) => $q->where('status', 'active'))
            ->orderByDesc('last_watched_at')
            ->take($this->perPage + 1)
            ->get();

        $hasMore = $rows->count() > $this->perPage;
        $history = $rows->take($this->perPage);

        return view('livewire.watch-history-list', compact('history', 'hasMore'));
    }
}
