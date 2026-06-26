<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Short extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_ar',
        'title_en',
        'type',
        'media_path',
        'poster_path',
        'qari_id',
        'source_url',
        'import_status',
        'import_error',
        'created_by',
        'sort_order',
        'status',
        'pinned_starts_at',
        'pinned_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'pinned_starts_at' => 'datetime',
            'pinned_ends_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function qari(): BelongsTo
    {
        return $this->belongsTo(Qari::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(ShortView::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePinnedNow(Builder $query): Builder
    {
        return $query
            ->whereNotNull('pinned_starts_at')
            ->whereNotNull('pinned_ends_at')
            ->where('pinned_starts_at', '<=', now())
            ->where('pinned_ends_at', '>=', now());
    }

    public function isPinnedNow(): bool
    {
        return $this->pinned_starts_at !== null
            && $this->pinned_ends_at !== null
            && $this->pinned_starts_at->lte(now())
            && $this->pinned_ends_at->gte(now());
    }

    public function hasFuturePin(): bool
    {
        return $this->pinned_starts_at !== null
            && $this->pinned_ends_at !== null
            && $this->pinned_starts_at->gt(now());
    }

    public function getTitleAttribute(): string
    {
        return (app()->getLocale() === 'en' && $this->title_en) ? $this->title_en : $this->title_ar;
    }

    public function getMediaUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->media_path);
    }

    public function getPosterUrlAttribute(): ?string
    {
        return $this->poster_path
            ? Storage::disk('public')->url($this->poster_path)
            : null;
    }

    /**
     * @return array{id:int,type:string,title:string,src:string,poster:?string}
     */
    public function heroPayload(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'src' => $this->media_url,
            'poster' => $this->poster_url,
        ];
    }
}
