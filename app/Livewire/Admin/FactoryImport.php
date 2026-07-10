<?php

namespace App\Livewire\Admin;

use App\Jobs\IngestSourceAudio;
use App\Models\Qari;
use App\Models\TilawatSource;
use App\Services\RecitationLibrary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FactoryImport extends Component
{
    public ?int $qariId = null;

    public string $qariSearch = '';

    public ?string $folder = null;

    public ?int $imported = null;

    /**
     * @return Collection<int, array{id: int, name: string, image: string}>
     */
    #[Computed]
    public function qaris(): Collection
    {
        return Qari::where('status', 'active')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'image'])
            ->map(fn (Qari $q): array => [
                'id' => $q->id,
                'name' => $q->name,
                'image' => $q->image_url,
            ]);
    }

    /**
     * @return array{id: int, name: string, image: string}|null
     */
    #[Computed]
    public function selectedQari(): ?array
    {
        return $this->qaris->firstWhere('id', $this->qariId);
    }

    /**
     * @return list<array{name: string, count: int}>
     */
    #[Computed]
    public function folders(): array
    {
        return app(RecitationLibrary::class)->folders();
    }

    public function selectQari(int $id): void
    {
        $exists = Qari::where('status', 'active')->whereKey($id)->exists();

        $this->qariId = $exists ? $id : null;
        $this->qariSearch = '';
        $this->imported = null;
    }

    public function clearQari(): void
    {
        $this->qariId = null;
        $this->folder = null;
        $this->imported = null;
    }

    public function selectFolder(string $name): void
    {
        $this->folder = collect($this->folders)->firstWhere('name', $name) ? $name : null;
        $this->imported = null;
    }

    public function import(RecitationLibrary $library): void
    {
        $this->imported = null;

        $this->validate([
            'qariId' => 'required|integer|exists:qaris,id',
            'folder' => 'required|string',
        ]);

        if (! collect($library->folders())->firstWhere('name', $this->folder)) {
            $this->addError('folder', __('That folder is no longer available.'));

            return;
        }

        $existing = TilawatSource::query()
            ->where('source_type', 'library')
            ->where('qari_id', $this->qariId)
            ->where('status', '!=', 'failed')
            ->pluck('source_url')
            ->flip();

        $count = 0;

        foreach ($library->audioFiles($this->folder) as $path) {
            if ($existing->has($path)) {
                continue;
            }

            $source = TilawatSource::create([
                'source_type' => 'library',
                'source_url' => $path,
                'source_title' => pathinfo($path, PATHINFO_FILENAME),
                'qari_id' => $this->qariId,
                'surah_number' => RecitationLibrary::guessSurahNumber($path),
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            IngestSourceAudio::dispatch($source->id);
            $count++;
        }

        $this->imported = $count;
        $this->folder = null;

        $this->dispatch('factory-updated');
    }

    public function render()
    {
        return view('livewire.admin.factory-import');
    }
}
