<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = ['title_ar', 'title_en', 'slug', 'description_ar', 'description_en', 'cover_image', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
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

    public function getTitleAttribute(): string
    {
        return (app()->getLocale() === 'en' && $this->title_en) ? $this->title_en : $this->title_ar;
    }

    public function getDescriptionAttribute(): ?string
    {
        return (app()->getLocale() === 'en' && $this->description_en) ? $this->description_en : $this->description_ar;
    }

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image) {
            return asset('storage/'.$this->cover_image);
        }

        $first = $this->relationLoaded('tilawat') ? $this->tilawat->first() : $this->tilawat()->first();

        return $first?->cover_url ?? asset('images/default-cover.jpg');
    }
}
