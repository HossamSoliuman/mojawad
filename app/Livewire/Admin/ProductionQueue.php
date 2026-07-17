<?php

namespace App\Livewire\Admin;

use App\Jobs\PublishToFacebook;
use App\Jobs\PublishToYoutube;
use App\Models\Publication;
use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Services\FacebookPublisher;
use App\Services\PublishingPipeline;
use App\Services\VideoCardService;
use App\Services\YoutubePublisher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProductionQueue extends Component
{
    #[Url]
    public string $tab = 'create';

    /** @var list<int> */
    public array $selected = [];

    // ── Card editor (Create tab) ─────────────────────────────────────
    public ?int $editingId = null;

    public string $cardQariName = '';

    public string $cardSurahName = '';

    public string $cardExtraText = '';

    public bool $cardAnimatePhoto = true;

    public bool $cardAnimateText = true;

    // ── Publish tab ──────────────────────────────────────────────────
    public ?int $publishFor = null;

    /** @var list<string> */
    public array $platforms = ['podcast'];

    #[Url]
    public string $publishFilter = 'all';

    public ?int $confirmingDeleteId = null;

    public bool $youtubeEnabled = false;

    public bool $facebookEnabled = false;

    public function mount(): void
    {
        $this->youtubeEnabled = YoutubePublisher::enabled();
        $this->facebookEnabled = FacebookPublisher::enabled();
    }

    /**
     * Create tab: everything that still needs its video made (or remade).
     *
     * @return Collection<int, Tilawa>
     */
    #[Computed]
    public function createList()
    {
        return $this->baseQuery()
            ->whereIn('brand_status', ['none', 'processing', 'failed'])
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * Publish tab: videos that are ready — publish them, then track where and
     * when each one went out.
     *
     * @return Collection<int, Tilawa>
     */
    #[Computed]
    public function publishList()
    {
        return $this->baseQuery()
            ->where('brand_status', 'ready')
            ->when($this->publishFilter === 'published', fn ($q) => $q->whereHas('publications', fn ($p) => $p->where('status', 'completed')))
            ->when($this->publishFilter === 'unpublished', fn ($q) => $q->whereDoesntHave('publications', fn ($p) => $p->where('status', 'completed')))
            ->latest()
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function createCount(): int
    {
        return $this->createList->count();
    }

    #[Computed]
    public function publishCount(): int
    {
        return $this->baseQuery()->where('brand_status', 'ready')->count();
    }

    #[Computed]
    public function hasActive(): bool
    {
        return $this->createList->contains(fn (Tilawa $t) => $t->brand_status === 'processing')
            || $this->publishList->contains(fn (Tilawa $t) => $t->publications->contains(
                fn (Publication $p) => in_array($p->status, ['pending', 'processing'], true)
            ));
    }

    // ── Card editor ──────────────────────────────────────────────────

    public function openEditor(int $tilawaId, VideoCardService $cards): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if ($tilawa->brand_status === 'processing') {
            return;
        }

        $data = $cards->dataFor($tilawa);

        $this->editingId = $tilawa->id;
        $this->cardQariName = $data['qariName'] ?? '';
        $this->cardSurahName = $data['surahName'] ?? '';
        $this->cardExtraText = $data['extraText'] ?? '';
        $this->cardAnimatePhoto = $data['animate_photo'];
        $this->cardAnimateText = $data['animate_text'];
    }

    public function closeEditor(): void
    {
        $this->editingId = null;
    }

    #[Computed]
    public function editorPreviewHtml(): string
    {
        if ($this->editingId === null) {
            return '';
        }

        $tilawa = Tilawa::with('qari')->find($this->editingId);

        return app(VideoCardService::class)->html([
            'qariName' => $this->cardQariName,
            'surahName' => $this->cardSurahName,
            'extraText' => $this->cardExtraText,
            'qariImage' => $tilawa?->qari?->image_url,
        ], [
            'animatePreview' => true,
            'animatePhoto' => $this->cardAnimatePhoto,
            'animateText' => $this->cardAnimateText,
        ]);
    }

    public function saveCard(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $this->persistCard();
        $this->editingId = null;
    }

    public function saveAndPrepare(PublishingPipeline $pipeline): void
    {
        if ($this->editingId === null) {
            return;
        }

        $tilawaId = $this->editingId;

        $this->persistCard();
        $this->prepare($tilawaId, $pipeline);
        $this->editingId = null;
    }

    private function persistCard(): void
    {
        $tilawa = $this->ownedTilawa($this->editingId);

        $tilawa->update(['brand_card' => array_merge($tilawa->brand_card ?? [], [
            'qari_name' => trim($this->cardQariName) ?: null,
            'surah_name' => trim($this->cardSurahName) ?: null,
            'extra_text' => trim($this->cardExtraText) ?: null,
            'animate_photo' => $this->cardAnimatePhoto,
            'animate_text' => $this->cardAnimateText,
        ])]);
    }

    // ── Rendering & publishing ───────────────────────────────────────

    public function prepare(int $tilawaId, PublishingPipeline $pipeline): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if ($tilawa->brand_status === 'processing') {
            return;
        }

        $pipeline->prepare($tilawa);
    }

    public function bulkPrepare(PublishingPipeline $pipeline): void
    {
        foreach ($this->createList->whereIn('id', $this->selected) as $tilawa) {
            if (in_array($tilawa->brand_status, ['none', 'failed'], true)) {
                $pipeline->prepare($tilawa);
            }
        }

        $this->selected = [];
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

    // ── Deletion (in-page modal, never the browser confirm) ─────────

    public function confirmDelete(int $tilawaId): void
    {
        $this->confirmingDeleteId = $tilawaId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function performDelete(): void
    {
        if ($this->confirmingDeleteId === null) {
            return;
        }

        $tilawa = $this->ownedTilawa($this->confirmingDeleteId);
        $disk = Storage::disk(config('publishing.disk'));

        $paths = [
            $tilawa->audio_path,
            $tilawa->master_audio_path,
            $tilawa->brand_cover_path,
            $tilawa->brand_video_path,
            $tilawa->subtitle_path,
            $tilawa->brand_card['card_image'] ?? null,
            $tilawa->source?->source_url,
        ];

        foreach ($paths as $path) {
            if ($path) {
                $disk->delete($path);
            }
        }

        $tilawa->source?->delete();
        $tilawa->delete();

        $this->selected = array_values(array_diff($this->selected, [$tilawa->id]));
        $this->confirmingDeleteId = null;
    }

    public function render()
    {
        return view('livewire.admin.production-queue');
    }

    private function baseQuery(): Builder
    {
        return Tilawa::query()
            ->whereHas('source', fn ($q) => $q->whereIn('source_type', TilawatSource::FACTORY_TYPES))
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('uploaded_by', Auth::id()))
            ->with(['qari', 'publications']);
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
