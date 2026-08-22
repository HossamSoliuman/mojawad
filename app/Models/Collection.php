<?php

namespace App\Models;

use App\Services\AutoCollectionResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection as SupportCollection;

class Collection extends Model
{
    use HasFactory;

    public const TYPE_MANUAL = 'manual';

    public const TYPE_AUTO = 'auto';

    protected $fillable = ['title_ar', 'title_en', 'slug', 'type', 'auto_rule', 'auto_limit', 'description_ar', 'description_en', 'cover_image', 'icon', 'is_active', 'sort_order'];

    /** @var SupportCollection<int, Tilawa>|null */
    private ?SupportCollection $resolvedItems = null;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'auto_limit' => 'integer', 'sort_order' => 'integer'];
    }

    public function tilawat(): BelongsToMany
    {
        return $this->belongsToMany(Tilawa::class, 'collection_tilawa')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('collection_tilawa.sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isAuto(): bool
    {
        return $this->type === self::TYPE_AUTO;
    }

    /**
     * The recitations this collection shows: hand-picked rows for manual
     * collections, live stat-driven results for auto ones.
     *
     * @return SupportCollection<int, Tilawa>
     */
    public function items(): SupportCollection
    {
        if ($this->resolvedItems !== null) {
            return $this->resolvedItems;
        }

        return $this->resolvedItems = $this->isAuto()
            ? app(AutoCollectionResolver::class)->resolve($this->auto_rule, $this->auto_limit ?? 10)
            : ($this->relationLoaded('tilawat')
                ? $this->tilawat
                : $this->tilawat()->where('status', 'active')->with('qari')->get());
    }

    public function itemsCount(): int
    {
        if ($this->isAuto()) {
            return $this->items()->count();
        }

        return $this->tilawat_count
            ?? ($this->relationLoaded('tilawat') ? $this->tilawat->count() : $this->tilawat()->where('status', 'active')->count());
    }

    public function getTitleAttribute(): string
    {
        return (app()->getLocale() === 'en' && $this->title_en) ? $this->title_en : $this->title_ar;
    }

    public function getDescriptionAttribute(): ?string
    {
        return (app()->getLocale() === 'en' && $this->description_en) ? $this->description_en : $this->description_ar;
    }

    public function getIconClassAttribute(): string
    {
        return $this->icon
            ?: (AutoCollectionResolver::rules()[$this->auto_rule]['icon'] ?? 'fa-layer-group');
    }

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image) {
            return asset('storage/'.$this->cover_image);
        }

        return $this->items()->first()?->cover_url ?? asset('images/default-cover.jpg');
    }
}
