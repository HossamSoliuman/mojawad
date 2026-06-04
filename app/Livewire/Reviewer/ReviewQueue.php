<?php

namespace App\Livewire\Reviewer;

use App\Models\Tilawa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ReviewQueue extends Component
{
    public ?int $activeId = null;

    public string $groupBy = 'qari';

    public ?int $rejectingId = null;

    public string $rejectionNote = '';

    public ?int $historyId = null;

    public function mount(): void
    {
        $this->authorize('review', Tilawa::class);
    }

    #[Computed]
    public function tilawat()
    {
        return Tilawa::with(['qari', 'uploader', 'reviews.reviewer'])
            ->pendingReview()
            ->oldest()
            ->get();
    }

    #[Computed]
    public function activeTilawa(): ?Tilawa
    {
        if ($this->activeId !== null) {
            $match = $this->tilawat->firstWhere('id', $this->activeId);

            if ($match !== null) {
                return $match;
            }
        }

        return $this->tilawat->first();
    }

    #[Computed]
    public function groupedTilawat()
    {
        return $this->tilawat
            ->groupBy(function (Tilawa $tilawa): string {
                return $this->groupBy === 'creator'
                    ? ($tilawa->uploader?->name ?? __('Unknown creator'))
                    : $tilawa->qari->name;
            })
            ->sortKeys();
    }

    public function setGroupBy(string $mode): void
    {
        $this->groupBy = in_array($mode, ['qari', 'creator'], true) ? $mode : 'qari';
    }

    public function focus(int $id): void
    {
        $this->activeId = $id;
        $this->reset(['rejectingId', 'rejectionNote', 'historyId']);
    }

    public function approve(int $id): void
    {
        $this->authorize('review', Tilawa::class);

        $tilawa = Tilawa::findOrFail($id);
        $tilawa->update([
            'status' => 'active',
            'review_status' => 'approved',
            'rejection_note' => null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
        $tilawa->logReview('approved', null, Auth::id());

        Cache::forget('homepage_data');
        $this->resetState();
        $this->dispatch('review-done', message: __('Tilawa approved and published.'));
    }

    public function startReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->rejectionNote = '';
        $this->historyId = null;
    }

    public function cancelReject(): void
    {
        $this->reset(['rejectingId', 'rejectionNote']);
    }

    public function reject(int $id): void
    {
        $this->authorize('review', Tilawa::class);

        $validated = $this->validate([
            'rejectionNote' => 'required|string|min:3|max:1000',
        ], [
            'rejectionNote.required' => __('Please add a note explaining what the creator needs to fix.'),
        ]);

        $tilawa = Tilawa::findOrFail($id);
        $tilawa->update([
            'status' => 'inactive',
            'review_status' => 'rejected',
            'rejection_note' => $validated['rejectionNote'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
        $tilawa->logReview('rejected', $validated['rejectionNote'], Auth::id());

        Cache::forget('homepage_data');
        $this->resetState();
        $this->dispatch('review-done', message: __('Tilawa rejected — the creator has been notified.'));
    }

    public function toggleHistory(int $id): void
    {
        $this->historyId = $this->historyId === $id ? null : $id;
    }

    private function resetState(): void
    {
        $this->reset(['rejectingId', 'rejectionNote', 'historyId']);
        unset($this->tilawat, $this->activeTilawa);
        $this->activeId = $this->tilawat->first()?->id;
    }

    public function render()
    {
        return view('livewire.reviewer.review-queue');
    }
}
