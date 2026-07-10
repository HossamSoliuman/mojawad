<?php

namespace App\Livewire\Admin;

use App\Jobs\DetectAyahRange;
use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Services\AyahDetectionService;
use App\Support\TilawaTitle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class FactoryQueue extends Component
{
    /** @var array<int, array{from: ?int, to: ?int}> */
    public array $edits = [];

    public bool $asrEnabled = false;

    public function mount(): void
    {
        $this->asrEnabled = AyahDetectionService::enabled();
    }

    #[On('factory-updated')]
    public function refreshQueue(): void
    {
        unset($this->sources, $this->hasActive);
    }

    #[Computed]
    public function sources()
    {
        return TilawatSource::uploads()
            ->with(['qari', 'tilawa'])
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('created_by', Auth::id()))
            ->latest()
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function hasActive(): bool
    {
        return $this->sources->contains(function (TilawatSource $s) {
            return in_array($s->status, ['pending', 'processing'], true)
                || ($s->tilawa && $s->tilawa->ayah_confidence === null && $s->status === 'completed' && $this->asrEnabled && $s->tilawa->ayah_from === null);
        });
    }

    public function confirm(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        $data = validator($this->edits[$tilawaId] ?? [], [
            'from' => 'required|integer|min:1',
            'to' => 'required|integer|min:1|gte:from',
        ])->validate();

        $title = TilawaTitle::withRange($tilawa->surah_number, (int) $data['from'], (int) $data['to']);

        $tilawa->update([
            'ayah_from' => (int) $data['from'],
            'ayah_to' => (int) $data['to'],
            'ayah_confidence' => 'manual',
            'title_ar' => $title,
            'slug' => $this->uniqueSlug($title, $tilawa->id),
        ]);
    }

    public function redetect(int $tilawaId): void
    {
        $tilawa = $this->ownedTilawa($tilawaId);

        if (! AyahDetectionService::enabled()) {
            throw ValidationException::withMessages(['asr' => __('Ayah detection is not configured.')]);
        }

        $tilawa->update(['ayah_confidence' => null, 'ayah_from' => null, 'ayah_to' => null]);

        DetectAyahRange::dispatch($tilawa->id);
    }

    public function deleteSource(int $sourceId): void
    {
        $source = TilawatSource::uploads()
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('created_by', Auth::id()))
            ->findOrFail($sourceId);

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
            $source->tilawa->delete();
        }

        $source->delete();

        unset($this->edits[$sourceId]);
    }

    public function render()
    {
        // Seed the editable ayah fields for rows that have a tilawa but no edit yet.
        foreach ($this->sources as $source) {
            if ($source->tilawa && ! array_key_exists($source->tilawa->id, $this->edits)) {
                $this->edits[$source->tilawa->id] = [
                    'from' => $source->tilawa->ayah_from,
                    'to' => $source->tilawa->ayah_to,
                ];
            }
        }

        return view('livewire.admin.factory-queue');
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
