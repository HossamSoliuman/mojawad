<?php

namespace App\Livewire;

use App\Models\Tilawa;
use Livewire\Attributes\On;
use Livewire\Component;

class SavesList extends Component
{
    public int $savedPerPage = 20;

    /** @var array<int, int> Saved tilawa ids supplied by the browser for signed-out visitors. */
    public array $guestIds = [];

    public bool $guestLoaded = false;

    public function loadMoreSaved(): void
    {
        $this->savedPerPage += 20;
    }

    public function loadGuest(array $ids): void
    {
        $this->guestIds = array_values(array_unique(array_map('intval', $ids)));
        $this->guestLoaded = true;
    }

    #[On('tilawa-save-changed')]
    public function syncSaves(?int $id = null, ?bool $saved = null): void
    {
        if (auth()->check() || $id === null) {
            return;
        }

        if ($saved) {
            $this->guestIds[] = $id;
            $this->guestIds = array_values(array_unique($this->guestIds));
        } else {
            $this->guestIds = array_values(array_diff($this->guestIds, [$id]));
        }
    }

    public function render()
    {
        if (auth()->check()) {
            $savedRows = auth()->user()->savedTilawat()->with('tilawa.qari')->latest()->take($this->savedPerPage + 1)->get();
            $hasMoreSaved = $savedRows->count() > $this->savedPerPage;
            $saved = $savedRows->take($this->savedPerPage)->pluck('tilawa')->filter();
        } else {
            $ids = array_reverse($this->guestIds);
            $page = array_slice($ids, 0, $this->savedPerPage + 1);
            $rows = Tilawa::with('qari')->where('status', 'active')->whereIn('id', $page)->get()->keyBy('id');
            $saved = collect($page)->map(fn ($id) => $rows->get($id))->filter()->values();
            $hasMoreSaved = $saved->count() > $this->savedPerPage;
            $saved = $saved->take($this->savedPerPage);
        }

        return view('livewire.saves-list', compact('saved', 'hasMoreSaved'));
    }
}
