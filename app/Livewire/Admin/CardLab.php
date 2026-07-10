<?php

namespace App\Livewire\Admin;

use App\Models\Tilawa;
use App\Services\VideoCardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

class CardLab extends Component
{
    public ?int $tilawaId = null;

    public string $qariName = '';

    public string $surahName = '';

    public string $extraText = '';

    public ?string $cardPath = null;

    public ?string $videoPath = null;

    public ?int $cardMs = null;

    public ?int $videoMs = null;

    public ?string $error = null;

    #[Computed]
    public function tilawat()
    {
        return Tilawa::query()
            ->when(! Auth::user()->hasRole('admin'), fn ($q) => $q->where('uploaded_by', Auth::id()))
            ->with('qari')
            ->latest()
            ->limit(50)
            ->get();
    }

    public function updatedTilawaId(mixed $value): void
    {
        $this->reset('cardPath', 'videoPath', 'cardMs', 'videoMs', 'error');

        $tilawa = $this->selectedTilawa();

        if ($tilawa === null) {
            return;
        }

        $this->qariName = $tilawa->qari?->name ?? '';
        $this->surahName = $tilawa->surah_name ?? '';
    }

    #[Computed]
    public function previewHtml(): string
    {
        return app(VideoCardService::class)->html([
            'qariName' => $this->qariName,
            'surahName' => $this->surahName,
            'extraText' => $this->extraText,
            'qariImage' => $this->selectedTilawa()?->qari?->image_url,
        ]);
    }

    public function renderCard(VideoCardService $cards): void
    {
        $this->reset('cardPath', 'videoPath', 'cardMs', 'videoMs', 'error');

        $disk = Storage::disk(config('publishing.disk'));
        $qariImage = $this->selectedTilawa()?->qari?->image;

        $started = microtime(true);

        try {
            $this->cardPath = $cards->render([
                'qariName' => $this->qariName,
                'surahName' => $this->surahName,
                'extraText' => $this->extraText,
                'qariImage' => $cards->dataUri($qariImage ? $disk->path($qariImage) : null),
            ]);
            $this->cardMs = (int) round((microtime(true) - $started) * 1000);
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function renderSample(VideoCardService $cards): void
    {
        $tilawa = $this->selectedTilawa();

        if ($this->cardPath === null || $tilawa?->audio_path === null) {
            return;
        }

        $this->reset('videoPath', 'videoMs', 'error');

        $started = microtime(true);

        try {
            $this->videoPath = $cards->sampleVideo($this->cardPath, $tilawa->audio_path);
            $this->videoMs = (int) round((microtime(true) - $started) * 1000);
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    #[Computed]
    public function cardUrl(): ?string
    {
        return $this->cardPath ? Storage::disk(config('publishing.disk'))->url($this->cardPath) : null;
    }

    #[Computed]
    public function videoUrl(): ?string
    {
        return $this->videoPath ? Storage::disk(config('publishing.disk'))->url($this->videoPath) : null;
    }

    public function render()
    {
        return view('livewire.admin.card-lab');
    }

    private function selectedTilawa(): ?Tilawa
    {
        return $this->tilawaId ? $this->tilawat->firstWhere('id', $this->tilawaId) : null;
    }
}
