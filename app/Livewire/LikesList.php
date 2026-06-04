<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class LikesList extends Component
{
    public int $likedPerPage = 20;

    public function loadMoreLiked(): void
    {
        $this->likedPerPage += 20;
    }

    #[On('tilawa-like-changed')]
    public function syncLikes(): void
    {
        // Re-render so unliked tilawat fall off the list immediately.
    }

    public function render()
    {
        $user = auth()->user();

        $likedRows = $user->likes()->with('tilawa.qari')->latest()->take($this->likedPerPage + 1)->get();
        $hasMoreLiked = $likedRows->count() > $this->likedPerPage;
        $liked = $likedRows->take($this->likedPerPage);

        return view('livewire.likes-list', compact('liked', 'hasMoreLiked'));
    }
}
