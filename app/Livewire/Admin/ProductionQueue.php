<?php

namespace App\Livewire\Admin;

use App\Jobs\PublishToFacebook;
use App\Jobs\PublishToYoutube;
use App\Models\Publication;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Services\FacebookPublisher;
use App\Services\PublishingPipeline;
use App\Services\VideoCardService;
use App\Services\YoutubePublisher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProductionQueue extends Component
{
    #[Url]
    public string $tab = 'selection';

    // ── Selection tab ────────────────────────────────────────────────
    public ?int $selectedQariId = null;

    // ── Card editor (Preparation tab) ────────────────────────────────
    public ?int $editingId = null;

    public string $cardQariName = '';

    public string $cardSurahName = '';

    public string $cardExtraText = '';

    public string $cardRareBadge = '';

    public bool $cardAnimatePhoto = true;

    public bool $cardAnimateText = true;

    // ── Publishing tab ───────────────────────────────────────────────
    public ?int $publishFor = null;

    /** @var list<string> */
    public array $platforms = ['podcast'];

    public string $ytTitle = '';

    public string $ytDescription = '';

    public string $fbTitle = '';

    public string $fbDescription = '';

    public string $hookText = '';

    public bool $youtubeEnabled = false;

    public bool $facebookEnabled = false;

    // ── Remove-from-production confirmation ──────────────────────────
    public ?int $confirmingRemoveId = null;

    public function mount(): void
    {
        $this->youtubeEnabled = YoutubePublisher::enabled();
        $this->facebookEnabled = FacebookPublisher::enabled();
    }

    // ── Selection: browse qaris, then pick their recitations ─────────

    /**
     * Qaris that still have at least one recitation outside the production
     * pipeline, each carrying its count of pickable recitations.
     *
     * @return Collection<int, Qari>
     */
    #[Computed]
    public function selectionQaris()
    {
        return Qari::query()
            ->whereHas('tilawat', fn (Builder $q) => $this->scopeSelectable($q))
            ->withCount(['tilawat as selectable_count' => fn (Builder $q) => $this->scopeSelectable($q)])
            ->ordered()
            ->get();
    }

    /**
     * Recitations of the opened qari that are not yet in the pipeline.
     *
     * @return Collection<int, Tilawa>
     */
    #[Computed]
    public function selectionTilawat()
    {
        if ($this->selectedQariId === null) {
            return collect();
        }

        return $this->scopeSelectable(Tilawa::query())
            ->where('qari_id', $this->selectedQariId)
            ->with('qari')
            ->latest()
            ->limit(200)
            ->get();
    }

    public function selectQari(int $qariId): void
    {
        $this->selectedQariId = $this->selectedQariId === $qariId ? null : $qariId;
    }

    public function addToProduction(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if ($tilawa->production_stage === null) {
            $tilawa->update(['production_stage' => 'preparing']);
        }
    }

    // ── Stage lists ──────────────────────────────────────────────────

    /**
     * @return Collection<int, Tilawa>
     */
    #[Computed]
    public function preparationList()
    {
        return $this->stageQuery('preparing')->latest()->limit(50)->get();
    }

    /**
     * @return Collection<int, Tilawa>
     */
    #[Computed]
    public function publishingList()
    {
        return $this->stageQuery('publishing')->latest()->limit(50)->get();
    }

    /**
     * @return Collection<int, Tilawa>
     */
    #[Computed]
    public function publishedList()
    {
        return $this->stageQuery('published')->latest()->limit(50)->get();
    }

    #[Computed]
    public function selectionCount(): int
    {
        return $this->scopeSelectable(Tilawa::query())->count();
    }

    #[Computed]
    public function preparationCount(): int
    {
        return $this->stageQuery('preparing')->count();
    }

    #[Computed]
    public function publishingCount(): int
    {
        return $this->stageQuery('publishing')->count();
    }

    #[Computed]
    public function publishedCount(): int
    {
        return $this->stageQuery('published')->count();
    }

    #[Computed]
    public function hasActive(): bool
    {
        return $this->preparationList->contains(fn (Tilawa $t) => $t->brand_status === 'processing')
            || $this->publishingList->contains(fn (Tilawa $t) => $t->publications->contains(
                fn (Publication $p) => in_array($p->status, ['pending', 'processing'], true)
            ));
    }

    // ── Card editor (Preparation) ────────────────────────────────────

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
        $this->cardRareBadge = $data['rareBadge'];
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
            'tilawaTitle' => $tilawa?->title_ar,
            'qariName' => $this->cardQariName,
            'surahName' => $this->cardSurahName,
            'extraText' => $this->cardExtraText,
            'rareBadge' => $this->cardRareBadge,
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
            'rare_badge' => trim($this->cardRareBadge),
            'animate_photo' => $this->cardAnimatePhoto,
            'animate_text' => $this->cardAnimateText,
        ])]);
    }

    // ── Rendering the video (Preparation) ────────────────────────────

    private function prepare(int $tilawaId, PublishingPipeline $pipeline): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if ($tilawa->brand_status === 'processing') {
            return;
        }

        $pipeline->prepare($tilawa);
    }

    /**
     * Re-run a failed render. The pipeline reuses the already-mastered audio,
     * cover, and subtitles, so a retry only re-renders the video — the step
     * that actually failed — and finishes far quicker than the first pass.
     */
    public function retryRender(int $tilawaId, PublishingPipeline $pipeline): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if (! in_array($tilawa->brand_status, ['failed', 'ready'], true)) {
            return;
        }

        $pipeline->prepare($tilawa);
    }

    public function moveToPublishing(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if ($tilawa->production_stage === 'preparing'
            && $tilawa->brand_status === 'ready'
            && $this->hasRenderedCard($tilawa)) {
            $tilawa->update(['production_stage' => 'publishing']);
        }
    }

    public function revealVideoInExplorer(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);
        $disk = Storage::disk(config('publishing.disk'));

        abort_unless($tilawa->brand_video_path && $disk->exists($tilawa->brand_video_path), 404);

        $absolutePath = realpath($disk->path($tilawa->brand_video_path));

        abort_unless($absolutePath !== false, 404);

        if (PHP_OS_FAMILY === 'Windows') {
            /**
             * Explorer parses its own command line: /select, must stay unquoted with only the path
             * quoted, so this is a raw string rather than an escaped argument array. An escaped
             * array makes Explorer ignore the switch and open the default folder instead. Explorer
             * also exits with code 1 on success, so the result is deliberately not thrown on.
             */
            Process::run('explorer.exe /select,"'.$absolutePath.'"');

            return;
        }

        $command = PHP_OS_FAMILY === 'Darwin'
            ? ['open', '-R', $absolutePath]
            : ['xdg-open', dirname($absolutePath)];

        Process::run($command)->throw();
    }

    public function moveToPreparation(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if ($tilawa->production_stage === 'publishing') {
            $this->discardRenderedVideo($tilawa, 'preparing');
        }
    }

    // ── Publishing: compose per-platform meta, then upload ───────────

    public function openPublish(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if (! $this->canPublish($tilawa)) {
            return;
        }

        $pubs = $tilawa->publications->keyBy('platform');
        $youtubeMeta = $pubs->get('youtube')?->meta ?? [];
        $facebookMeta = $pubs->get('facebook')?->meta ?? [];

        $this->publishFor = $tilawaId;
        $this->platforms = ['podcast'];
        $this->hookText = $youtubeMeta['hook'] ?? $facebookMeta['hook'] ?? $this->defaultHook($tilawa);
        $this->ytTitle = $youtubeMeta['title'] ?? $this->defaultTitle($tilawa);
        $this->ytDescription = $youtubeMeta['description'] ?? $this->defaultDescription($tilawa, 'youtube');
        $this->fbTitle = $facebookMeta['title'] ?? $this->defaultTitle($tilawa);
        $this->fbDescription = $facebookMeta['description'] ?? $this->defaultDescription($tilawa, 'facebook');
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

        if ($this->canPublish($tilawa) && $this->platforms !== []) {
            $pipeline->publish($tilawa, $this->platforms, Auth::id(), [
                'youtube' => ['hook' => trim($this->hookText), 'title' => trim($this->ytTitle), 'description' => trim($this->ytDescription)],
                'facebook' => ['hook' => trim($this->hookText), 'title' => trim($this->fbTitle), 'description' => trim($this->fbDescription)],
            ]);
        }

        $this->publishFor = null;
    }

    /**
     * A recitation can be published (or re-published to another platform) once
     * its video is ready and it has reached the Publishing or Published stage.
     */
    private function canPublish(Tilawa $tilawa): bool
    {
        return in_array($tilawa->production_stage, ['publishing', 'published'], true)
            && $tilawa->brand_status === 'ready'
            && $this->hasRenderedCard($tilawa);
    }

    private function hasRenderedCard(Tilawa $tilawa): bool
    {
        return filled($tilawa->brand_card['card_image'] ?? null);
    }

    public function moveToPublished(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if ($tilawa->production_stage === 'publishing') {
            $tilawa->update(['production_stage' => 'published']);
        }
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

    // ── Remove from the pipeline (keeps the recitation on the site) ──

    public function confirmRemove(int $tilawaId): void
    {
        $this->confirmingRemoveId = $tilawaId;
    }

    public function cancelRemove(): void
    {
        $this->confirmingRemoveId = null;
    }

    /**
     * Drop the recitation out of the production pipeline while keeping the
     * tilawa, its source, and reusable preparation assets available.
     */
    public function performRemove(): void
    {
        if ($this->confirmingRemoveId === null) {
            return;
        }

        $tilawa = $this->ownedTilawa($this->confirmingRemoveId);

        $this->discardRenderedVideo($tilawa, null);

        $this->confirmingRemoveId = null;
    }

    private function discardRenderedVideo(Tilawa $tilawa, ?string $productionStage): void
    {
        $tilawa->update([
            'production_stage' => $productionStage,
            'brand_video_path' => null,
            'brand_status' => 'none',
            'brand_error' => null,
        ]);
    }

    public function render()
    {
        return view('livewire.admin.production-queue');
    }

    /**
     * @return array<int, array{hook: string, youtube_title: string, youtube_description: string, facebook_title: string, facebook_description: string, all: string}>
     */
    #[Computed]
    public function publishingCopy(): array
    {
        return $this->publishingList
            ->mapWithKeys(function (Tilawa $tilawa): array {
                $publications = $tilawa->publications->keyBy('platform');
                $youtubeMeta = $publications->get('youtube')?->meta ?? [];
                $facebookMeta = $publications->get('facebook')?->meta ?? [];

                $hook = $youtubeMeta['hook'] ?? $facebookMeta['hook'] ?? $this->defaultHook($tilawa);
                $youtubeTitle = $youtubeMeta['title'] ?? $this->defaultTitle($tilawa);
                $youtubeDescription = $youtubeMeta['description'] ?? $this->defaultDescription($tilawa, 'youtube');
                $facebookTitle = $facebookMeta['title'] ?? $this->defaultTitle($tilawa);
                $facebookDescription = $facebookMeta['description'] ?? $this->defaultDescription($tilawa, 'facebook');

                return [$tilawa->id => [
                    'hook' => $hook,
                    'youtube_title' => $youtubeTitle,
                    'youtube_description' => $youtubeDescription,
                    'facebook_title' => $facebookTitle,
                    'facebook_description' => $facebookDescription,
                    'all' => implode("\n\n", [
                        __('Hook').":\n".$hook,
                        __('YouTube title').":\n".$youtubeTitle,
                        __('YouTube description').":\n".$youtubeDescription,
                        __('Facebook post title').":\n".$facebookTitle,
                        __('Facebook post text').":\n".$facebookDescription,
                    ]),
                ]];
            })
            ->all();
    }

    // ── Query helpers ────────────────────────────────────────────────

    private function stageQuery(string $stage): Builder
    {
        return $this->applyOwnership(Tilawa::query())
            ->where('production_stage', $stage)
            ->with(['qari', 'publications']);
    }

    private function scopeSelectable(Builder $query): Builder
    {
        return $this->applyOwnership($query)->whereNull('production_stage');
    }

    private function applyOwnership(Builder $query): Builder
    {
        return $query->when(
            ! Auth::user()->hasRole('admin'),
            fn (Builder $q) => $q->where('uploaded_by', Auth::id()),
        );
    }

    private function ownedTilawa(int $tilawaId): Tilawa
    {
        return $this->applyOwnership(Tilawa::query())->findOrFail($tilawaId);
    }

    private function scopeOwned(Builder $query): void
    {
        $this->applyOwnership($query);
    }

    private function defaultHook(Tilawa $tilawa): string
    {
        return __('A moving recitation from :title, in the voice of :qari. Listen with your heart. ✨', [
            'title' => $tilawa->title_ar,
            'qari' => $tilawa->qari?->name ?? __('an outstanding reciter'),
        ]);
    }

    private function defaultTitle(Tilawa $tilawa): string
    {
        return trim($tilawa->title_ar.($tilawa->qari?->name ? ' | '.$tilawa->qari->name : ''));
    }

    private function defaultDescription(Tilawa $tilawa, string $platform): string
    {
        $link = 'https://mojawad.org/tilawa/'.$tilawa->slug
            .'?utm_source='.$platform.'&utm_medium=video&utm_campaign=publishing';

        return implode("\n\n", array_filter([
            $this->defaultHook($tilawa),
            trim($tilawa->title_ar."\n".($tilawa->qari?->name ?? '')),
            __('Listen to more recitations on Mojawad:')."\n".$link,
            '#القرآن_الكريم #تلاوة #مجود',
        ]));
    }
}
