<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'created_by',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
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
