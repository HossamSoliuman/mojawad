<?php

namespace App\Livewire;

use Livewire\Component;

class LibraryList extends Component
{
    public int $savedPerPage = 20;
    public int $likedPerPage = 20;

    public function loadMoreSaved(): void
    {
        $this->savedPerPage += 20;
    }

    public function loadMoreLiked(): void
    {
        $this->likedPerPage += 20;
    }

    public function render()
    {
        $user = auth()->user();

        $savedRows    = $user->savedTilawat()->with('tilawa.qari')->latest()->take($this->savedPerPage + 1)->get();
        $hasMoreSaved = $savedRows->count() > $this->savedPerPage;
        $saved        = $savedRows->take($this->savedPerPage);

        $likedRows    = $user->likes()->with('tilawa.qari')->latest()->take($this->likedPerPage + 1)->get();
        $hasMoreLiked = $likedRows->count() > $this->likedPerPage;
        $liked        = $likedRows->take($this->likedPerPage);

        return view('livewire.library-list', compact('saved', 'hasMoreSaved', 'liked', 'hasMoreLiked'));
    }
}
