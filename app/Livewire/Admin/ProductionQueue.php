<?php

namespace App\Livewire\Admin;

use App\Jobs\PublishToFacebook;
use App\Jobs\PublishToYoutube;
use App\Models\Publication;
use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Services\FacebookPublisher;
use App\Services\PublishingPipeline;
use App\Services\YoutubePublisher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProductionQueue extends Component
{
    /** @var list<int> */
    public array $selected = [];

    public ?int $publishFor = null;

    /** @var list<string> */
    public array $platforms = ['podcast'];

    public bool $youtubeEnabled = false;

    public bool $facebookEnabled = false;

    public function mount(): void
    {
        $this->youtubeEnabled = YoutubePublisher::enabled();
        $this->facebookEnabled = FacebookPublisher::enabled();
    }

    #[Computed]
    public function tilawat()
    {
        return Tilawa::query()
            ->whereHas('source', fn ($q) => $q->whereIn('source_type', TilawatSource::FACTORY_TYPES))
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('uploaded_by', Auth::id()))
            ->with(['qari', 'publications'])
            ->latest()
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function hasActive(): bool
    {
        return $this->tilawat->contains(function (Tilawa $t) {
            return $t->brand_status === 'processing'
                || $t->publications->contains(fn (Publication $p) => in_array($p->status, ['pending', 'processing'], true));
        });
    }

    public function prepare(int $tilawaId, PublishingPipeline $pipeline): void
    {
        $pipeline->prepare($this->ownedTilawa($tilawaId));
    }

    public function openPublish(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if ($tilawa->brand_status !== 'ready') {
            return;
        }

        $this->publishFor = $tilawaId;
        $this->platforms = ['podcast'];
    }

    public function cancelPublish(): void
    {
        $this->publishFor = null;
    }

    public function doPublish(PublishingPipeline $pipeline): void
    {
        if ($this->publishFor === null) {
            return;
        }

        $tilawa = $this->ownedTilawa($this->publishFor);

        if ($tilawa->brand_status === 'ready' && $this->platforms !== []) {
            $pipeline->publish($tilawa, $this->platforms, Auth::id());
        }

        $this->publishFor = null;
    }

    public function retryPublication(int $publicationId): void
    {
        $publication = Publication::whereHas('tilawa', fn ($q) => $this->scopeOwned($q))->findOrFail($publicationId);

        $publication->update(['status' => 'pending', 'error' => null]);

        match ($publication->platform) {
            'youtube' => PublishToYoutube::dispatch($publication->id),
            'facebook' => PublishToFacebook::dispatch($publication->id),
            'podcast' => app(PublishingPipeline::class)->publish($publication->tilawa, ['podcast'], Auth::id()),
            default => null,
        };
    }

    public function delete(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);
        $disk = Storage::disk(config('publishing.disk'));

        foreach ([$tilawa->audio_path, $tilawa->master_audio_path, $tilawa->brand_cover_path, $tilawa->brand_video_path, $tilawa->subtitle_path, $tilawa->source?->source_url] as $path) {
            if ($path) {
                $disk->delete($path);
            }
        }

        $tilawa->source?->delete();
        $tilawa->delete();

        $this->selected = array_values(array_diff($this->selected, [$tilawaId]));
    }

    public function bulkPrepare(PublishingPipeline $pipeline): void
    {
        foreach ($this->selectedTilawat() as $tilawa) {
            if (in_array($tilawa->brand_status, ['none', 'failed'], true)) {
                $pipeline->prepare($tilawa);
            }
        }

        $this->selected = [];
    }

    public function render()
    {
        return view('livewire.admin.production-queue');
    }

    /**
     * @return Collection<int, Tilawa>
     */
    private function selectedTilawat()
    {
        return $this->tilawat->whereIn('id', $this->selected);
    }

    private function ownedTilawa(int $tilawaId): Tilawa
    {
        return Tilawa::query()
            ->whereHas('source', fn ($q) => $q->whereIn('source_type', TilawatSource::FACTORY_TYPES))
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('uploaded_by', Auth::id()))
            ->findOrFail($tilawaId);
    }

    private function scopeOwned($query): void
    {
        $query->whereHas('source', fn ($q) => $q->whereIn('source_type', TilawatSource::FACTORY_TYPES))
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('uploaded_by', Auth::id()));
    }
}
