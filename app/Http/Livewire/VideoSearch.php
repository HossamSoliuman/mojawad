<?php

namespace App\Http\Livewire;

use App\Models\Qari;
use App\Models\Surah;
use App\Models\Video;
use Livewire\Component;
use Livewire\WithPagination;

class VideoSearch extends Component
{
    use WithPagination;

    public string $query = '';

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $videos = collect();
        $qaris = collect();

        if (strlen($this->query) >= 2) {
            $qaris = Qari::where('name_en', 'like', "%{$this->query}%")
                ->orWhere('name_ar', 'like', "%{$this->query}%")
                ->get();

            $surah = Surah::where('name_en', 'like', "%{$this->query}%")
                ->orWhere('name_ar', 'like', "%{$this->query}%")
                ->first();

            $videoQuery = Video::approved()->ranked()->with(['qari', 'surah']);

            if ($surah || $qaris->isNotEmpty()) {
                $videoQuery->where(function ($q) use ($surah, $qaris) {
                    if ($surah) $q->where('surah_id', $surah->id);
                    if ($qaris->isNotEmpty()) $q->orWhereIn('qari_id', $qaris->pluck('id'));
                });
            }

            $videos = $videoQuery->paginate(12);
        }

        return view('livewire.video-search', compact('videos', 'qaris'));
    }
}
