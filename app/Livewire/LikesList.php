<?php

namespace App\Livewire;

use App\Models\Tilawa;
use Livewire\Attributes\On;
use Livewire\Component;

class LikesList extends Component
{
    public int $likedPerPage = 20;

    /** @var array<int, int> Liked tilawa ids supplied by the browser for signed-out visitors. */
    public array $guestIds = [];

    public bool $guestLoaded = false;

    public function loadMoreLiked(): void
    {
        $this->likedPerPage += 20;
    }

    public function loadGuest(array $ids): void
    {
        $this->guestIds = array_values(array_unique(array_map('intval', $ids)));
        $this->guestLoaded = true;
    }

    #[On('tilawa-like-changed')]
    public function syncLikes(?int $id = null, ?bool $liked = null): void
    {
        if (auth()->check() || $id === null) {
            return;
        }

        if ($liked) {
            $this->guestIds[] = $id;
            $this->guestIds = array_values(array_unique($this->guestIds));
        } else {
            $this->guestIds = array_values(array_diff($this->guestIds, [$id]));
        }
    }

    public function render()
    {
        if (auth()->check()) {
            $likedRows = auth()->user()->likes()->with('tilawa.qari')->latest()->take($this->likedPerPage + 1)->get();
            $hasMoreLiked = $likedRows->count() > $this->likedPerPage;
            $liked = $likedRows->take($this->likedPerPage)->pluck('tilawa')->filter();
        } else {
            $ids = array_reverse($this->guestIds);
            $page = array_slice($ids, 0, $this->likedPerPage + 1);
            $rows = Tilawa::with('qari')->where('status', 'active')->whereIn('id', $page)->get()->keyBy('id');
            $liked = collect($page)->map(fn ($id) => $rows->get($id))->filter()->values();
            $hasMoreLiked = $liked->count() > $this->likedPerPage;
            $liked = $liked->take($this->likedPerPage);
        }

        return view('livewire.likes-list', compact('liked', 'hasMoreLiked'));
    }
}
