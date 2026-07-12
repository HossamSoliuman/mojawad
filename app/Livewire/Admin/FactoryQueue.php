<?php

namespace App\Livewire\Admin;

use App\Jobs\DetectAyahRange;
use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Services\AyahDetectionService;
use App\Support\TilawaTitle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class FactoryQueue extends Component
{
    public string $tab = 'processing';

    /** @var array<int, array{from: ?int, to: ?int}> */
    public array $edits = [];

    /** @var array<int, string> */
    public array $selected = [];

    public bool $selectAll = false;

    public ?int $confirmingSourceId = null;

    public bool $confirmingBulkDelete = false;

    public bool $asrEnabled = false;

    public function mount(): void
    {
        $this->asrEnabled = AyahDetectionService::enabled();
    }

    public function switchTab(string $tab): void
    {
        $this->tab = in_array($tab, ['processing', 'review'], true) ? $tab : 'processing';

        $this->reset('selected', 'selectAll');
    }

    /**
     * Toggle every source in the active tab in one click. Selection is stored as
     * string ids so the checkboxes stay in sync with their rendered values.
     */
    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value
            ? $this->currentSources()->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    #[On('factory-updated')]
    public function refreshQueue(): void
    {
        unset($this->processingSources, $this->reviewSources, $this->processingCount, $this->reviewCount, $this->hasActive);
    }

    private function baseQuery(): Builder
    {
        return TilawatSource::uploads()
            ->with(['qari', 'tilawa'])
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('created_by', Auth::id()));
    }

    /**
     * Sources still moving through the pipeline: queued, cleaning, or failed.
     */
    #[Computed]
    public function processingSources()
    {
        return $this->baseQuery()
            ->where('status', '!=', 'completed')
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * Cleaned recitations awaiting a human listen & approval — they leave this
     * list the moment they are approved (their tilawa becomes active).
     */
    #[Computed]
    public function reviewSources()
    {
        return $this->baseQuery()
            ->where('status', 'completed')
            ->whereHas('tilawa', fn ($q) => $q->where('status', '!=', 'active'))
            ->latest()
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function processingCount(): int
    {
        return $this->baseQuery()->where('status', '!=', 'completed')->count();
    }

    #[Computed]
    public function reviewCount(): int
    {
        return $this->baseQuery()
            ->where('status', 'completed')
            ->whereHas('tilawa', fn ($q) => $q->where('status', '!=', 'active'))
            ->count();
    }

    #[Computed]
    public function hasActive(): bool
    {
        if ($this->baseQuery()->whereIn('status', ['pending', 'processing'])->exists()) {
            return true;
        }

        if (! $this->asrEnabled) {
            return false;
        }

        return $this->baseQuery()
            ->where('status', 'completed')
            ->whereHas('tilawa', fn ($q) => $q->whereNull('ayah_from')->whereNull('ayah_confidence'))
            ->exists();
    }

    public function confirm(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if ($tilawa->isMultiSurah()) {
            return;
        }

        $tilawa->update($this->rangeAttributes($tilawa, $tilawaId));
    }

    /**
     * Approve a reviewed recitation: optionally lock in an edited ayah range,
     * then activate it so it goes live on the platform immediately.
     */
    public function approve(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        $edit = $this->edits[$tilawaId] ?? [];

        $range = (! $tilawa->isMultiSurah() && ! empty($edit['from']) && ! empty($edit['to']))
            ? $this->rangeAttributes($tilawa, $tilawaId)
            : [];

        $tilawa->update([
            ...$range,
            'status' => 'active',
            'review_status' => 'approved',
            'rejection_note' => null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $tilawa->logReview('approved', null, Auth::id());

        Cache::forget('homepage_data');

        unset($this->reviewSources, $this->reviewCount, $this->hasActive);
    }

    public function redetect(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if ($tilawa->isMultiSurah()) {
            return;
        }

        if (! AyahDetectionService::enabled()) {
            throw ValidationException::withMessages(['asr' => __('Ayah detection is not configured.')]);
        }

        $tilawa->update(['ayah_confidence' => null, 'ayah_from' => null, 'ayah_to' => null]);

        DetectAyahRange::dispatch($tilawa->id);
    }

    public function confirmDelete(int $sourceId): void
    {
        $this->confirmingSourceId = $sourceId;
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selected !== []) {
            $this->confirmingBulkDelete = true;
        }
    }

    public function cancelDelete(): void
    {
        $this->reset('confirmingSourceId', 'confirmingBulkDelete');
    }

    /**
     * Run the delete the confirmation modal was opened for — the selected batch
     * when a bulk delete is pending, otherwise the single queued source.
     */
    public function performDelete(): void
    {
        if ($this->confirmingBulkDelete) {
            $this->deleteSelected();
        } elseif ($this->confirmingSourceId !== null) {
            $this->deleteSource($this->confirmingSourceId);
        }

        $this->reset('confirmingSourceId', 'confirmingBulkDelete');
    }

    public function deleteSource(int $sourceId): void
    {
        $source = TilawatSource::uploads()
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('created_by', Auth::id()))
            ->with('tilawa')
            ->findOrFail($sourceId);

        $this->deleteSourceRecord($source);
    }

    /**
     * Remove every currently selected source (and its recitation) in one action,
     * scoped to sources the user owns so ids from other creators are ignored.
     */
    public function deleteSelected(): void
    {
        $ids = array_map('intval', $this->selected);

        if ($ids === []) {
            return;
        }

        $sources = TilawatSource::uploads()
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('created_by', Auth::id()))
            ->whereIn('id', $ids)
            ->with('tilawa')
            ->get();

        foreach ($sources as $source) {
            $this->deleteSourceRecord($source);
        }

        $this->reset('selected', 'selectAll');
    }

    private function deleteSourceRecord(TilawatSource $source): void
    {
        $disk = Storage::disk(config('publishing.disk'));

        if ($source->source_url) {
            $disk->delete($source->source_url);
        }

        if ($source->tilawa) {
            foreach ([$source->tilawa->audio_path, $source->tilawa->master_audio_path, $source->tilawa->brand_cover_path, $source->tilawa->brand_video_path, $source->tilawa->subtitle_path] as $path) {
                if ($path) {
                    $disk->delete($path);
                }
            }
            unset($this->edits[$source->tilawa->id]);
            $source->tilawa->delete();
        }

        $source->delete();
    }

    /**
     * The sources shown in the active tab, used to drive the select-all toggle.
     */
    private function currentSources()
    {
        return $this->tab === 'review' ? $this->reviewSources : $this->processingSources;
    }

    public function render()
    {
        // Seed the editable ayah fields for review rows that have no edit yet.
        foreach ($this->reviewSources as $source) {
            if ($source->tilawa && ! array_key_exists($source->tilawa->id, $this->edits)) {
                $this->edits[$source->tilawa->id] = [
                    'from' => $source->tilawa->ayah_from,
                    'to' => $source->tilawa->ayah_to,
                ];
            }
        }

        return view('livewire.admin.factory-queue');
    }

    /**
     * Validate the edited ayah range and build the attributes needed to persist
     * it, including the range-aware title and a fresh unique slug.
     *
     * @return array{ayah_from: int, ayah_to: int, ayah_confidence: string, title_ar: string, slug: string}
     */
    private function rangeAttributes(Tilawa $tilawa, int $tilawaId): array
    {
        $data = validator($this->edits[$tilawaId] ?? [], [
            'from' => 'required|integer|min:1',
            'to' => 'required|integer|min:1|gte:from',
        ])->validate();

        $title = TilawaTitle::withRange($tilawa->surah_number, (int) $data['from'], (int) $data['to']);

        return [
            'ayah_from' => (int) $data['from'],
            'ayah_to' => (int) $data['to'],
            'ayah_confidence' => 'manual',
            'title_ar' => $title,
            'slug' => $this->uniqueSlug($title, $tilawa->id),
        ];
    }

    private function ownedTilawa(int $tilawaId): Tilawa
    {
        return Tilawa::whereHas('source', function ($q) {
            $q->whereIn('source_type', TilawatSource::FACTORY_TYPES)
                ->when(! Auth::user()->hasRole('admin'), fn ($sub) => $sub->where('created_by', Auth::id()));
        })->findOrFail($tilawaId);
    }

    private function uniqueSlug(string $title, int $ignoreId): string
    {
        $base = Str::slug($title) ?: 'tilawa';
        $slug = $base;

        while (Tilawa::where('slug', $slug)->whereKeyNot($ignoreId)->exists()) {
            $slug = $base.'-'.Str::random(6);
        }

        return $slug;
    }
}
