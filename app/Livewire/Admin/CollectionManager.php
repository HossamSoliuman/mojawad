<?php

namespace App\Livewire\Admin;

use App\Models\Collection;
use App\Models\Tilawa;
use App\Services\AutoCollectionResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class CollectionManager extends Component
{
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title_ar = '';

    public string $title_en = '';

    public string $description_ar = '';

    public string $type = Collection::TYPE_MANUAL;

    public string $auto_rule = AutoCollectionResolver::RULE_MOST_PLAYED;

    public int $auto_limit = 10;

    public bool $is_active = true;

    public int $sort_order = 0;

    /** @var TemporaryUploadedFile|null */
    public $cover = null;

    public ?string $currentCover = null;

    /**
     * Ordered ids of the hand-picked recitations in a manual collection.
     *
     * @var array<int, int>
     */
    public array $picked = [];

    public string $search = '';

    public ?int $confirmingDeleteId = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string|max:2000',
            'type' => 'required|in:'.Collection::TYPE_MANUAL.','.Collection::TYPE_AUTO,
            'auto_rule' => 'required_if:type,'.Collection::TYPE_AUTO.'|in:'.implode(',', AutoCollectionResolver::ruleKeys()),
            'auto_limit' => 'required|integer|min:1|max:100',
            'sort_order' => 'required|integer|min:0|max:65535',
            'cover' => 'nullable|image|max:4096',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'title_ar.required' => __('The Arabic title is required.'),
            'auto_rule.required_if' => __('Pick the rule that fills this collection.'),
            'cover.image' => __('The cover must be an image.'),
            'cover.max' => __('The cover may not be larger than 4 MB.'),
        ];
    }

    #[Computed]
    public function collections()
    {
        return Collection::withCount(['tilawat as tilawat_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array<string, array{label: string, hint: string, icon: string}>
     */
    #[Computed]
    public function ruleOptions(): array
    {
        return AutoCollectionResolver::rules();
    }

    /**
     * Live preview of what the selected auto rule currently resolves to, so the
     * admin sees the real recitations before saving.
     */
    #[Computed]
    public function autoPreview()
    {
        if ($this->type !== Collection::TYPE_AUTO || ! AutoCollectionResolver::isValidRule($this->auto_rule)) {
            return collect();
        }

        return app(AutoCollectionResolver::class)->resolve($this->auto_rule, $this->auto_limit);
    }

    /**
     * Recitations matching the picker search, excluding ones already added.
     */
    #[Computed]
    public function searchResults()
    {
        if (trim($this->search) === '') {
            return collect();
        }

        return Tilawa::with('qari')
            ->where('status', 'active')
            ->whereNotIn('id', $this->picked)
            ->where(fn ($q) => $q->where('title_ar', 'like', '%'.$this->search.'%')
                ->orWhere('title_en', 'like', '%'.$this->search.'%')
                ->orWhereHas('qari', fn ($sub) => $sub->where('name_ar', 'like', '%'.$this->search.'%')
                    ->orWhere('name_en', 'like', '%'.$this->search.'%')))
            ->limit(15)
            ->get();
    }

    /**
     * The picked recitations, kept in the exact order the admin arranged them.
     */
    #[Computed]
    public function pickedTilawat()
    {
        if ($this->picked === []) {
            return collect();
        }

        $tilawat = Tilawa::with('qari')->whereIn('id', $this->picked)->get()->keyBy('id');

        return collect($this->picked)->map(fn (int $id) => $tilawat->get($id))->filter()->values();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $collection = Collection::with('tilawat')->findOrFail($id);

        $this->editingId = $collection->id;
        $this->title_ar = $collection->title_ar;
        $this->title_en = $collection->title_en ?? '';
        $this->description_ar = $collection->description_ar ?? '';
        $this->type = $collection->type;
        $this->auto_rule = $collection->auto_rule ?: AutoCollectionResolver::RULE_MOST_PLAYED;
        $this->auto_limit = $collection->auto_limit ?: 10;
        $this->is_active = $collection->is_active;
        $this->sort_order = $collection->sort_order;
        $this->currentCover = $collection->cover_image;
        $this->picked = $collection->tilawat->pluck('id')->all();
        $this->cover = null;
        $this->search = '';
        $this->showForm = true;

        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function addTilawa(int $id): void
    {
        if (! in_array($id, $this->picked, true)) {
            $this->picked[] = $id;
        }

        $this->search = '';
        unset($this->pickedTilawat, $this->searchResults);
    }

    public function removeTilawa(int $id): void
    {
        $this->picked = array_values(array_filter($this->picked, fn (int $picked) => $picked !== $id));

        unset($this->pickedTilawat, $this->searchResults);
    }

    public function moveUp(int $index): void
    {
        $this->swap($index, $index - 1);
    }

    public function moveDown(int $index): void
    {
        $this->swap($index, $index + 1);
    }

    public function save(): void
    {
        $data = $this->validate();

        $isAuto = $data['type'] === Collection::TYPE_AUTO;

        $attributes = [
            'title_ar' => $data['title_ar'],
            'title_en' => $data['title_en'] ?: null,
            'description_ar' => $data['description_ar'] ?: null,
            'type' => $data['type'],
            'auto_rule' => $isAuto ? $data['auto_rule'] : null,
            'auto_limit' => $isAuto ? $data['auto_limit'] : 10,
            'is_active' => $this->is_active,
            'sort_order' => $data['sort_order'],
        ];

        $collection = $this->editingId
            ? Collection::findOrFail($this->editingId)
            : new Collection;

        if (! $this->editingId || $collection->title_ar !== $attributes['title_ar']) {
            $attributes['slug'] = $this->uniqueSlug($attributes['title_en'] ?: $attributes['title_ar'], $collection->id);
        }

        if ($this->cover) {
            if ($collection->cover_image) {
                Storage::disk('public')->delete($collection->cover_image);
            }
            $attributes['cover_image'] = $this->cover->store('collection-covers', 'public');
        }

        $collection->fill($attributes)->save();

        $collection->tilawat()->sync(
            $isAuto ? [] : collect($this->picked)->mapWithKeys(fn (int $id, int $i) => [$id => ['sort_order' => $i]])->all()
        );

        Cache::forget('homepage_data');

        $this->resetForm();
        unset($this->collections);

        session()->flash('collection-saved', __('Collection saved.'));
    }

    public function toggleActive(int $id): void
    {
        $collection = Collection::findOrFail($id);
        $collection->update(['is_active' => ! $collection->is_active]);

        Cache::forget('homepage_data');
        unset($this->collections);
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
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

        $collection = Collection::find($this->confirmingDeleteId);

        if ($collection) {
            if ($collection->cover_image) {
                Storage::disk('public')->delete($collection->cover_image);
            }

            if ($this->editingId === $collection->id) {
                $this->resetForm();
            }

            $collection->delete();
            Cache::forget('homepage_data');
        }

        $this->confirmingDeleteId = null;
        unset($this->collections);
    }

    public function render()
    {
        return view('livewire.admin.collection-manager');
    }

    private function swap(int $a, int $b): void
    {
        if (! isset($this->picked[$a], $this->picked[$b])) {
            return;
        }

        [$this->picked[$a], $this->picked[$b]] = [$this->picked[$b], $this->picked[$a]];

        unset($this->pickedTilawat);
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'title_ar', 'title_en', 'description_ar', 'type', 'auto_rule', 'auto_limit', 'is_active', 'sort_order', 'cover', 'currentCover', 'picked', 'search']);

        $this->resetValidation();
    }

    private function uniqueSlug(string $title, ?int $ignoreId): string
    {
        // Keep the language argument null so Arabic titles survive as readable
        // slugs instead of being transliterated away to an empty string.
        $base = Str::slug($title, '-', null) ?: 'collection';
        $slug = $base;

        while (Collection::where('slug', $slug)->whereKeyNot($ignoreId ?? 0)->exists()) {
            $slug = $base.'-'.Str::random(5);
        }

        return $slug;
    }
}
