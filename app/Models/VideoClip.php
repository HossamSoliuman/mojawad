<?php

namespace App\Models;

use Database\Factories\VideoClipFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VideoClip extends Model
{
    /** @use HasFactory<VideoClipFactory> */
    use HasFactory;

    protected $fillable = [
        'tilawa_id',
        'created_by',
        'template',
        'clip_start',
        'clip_duration',
        'overlay_path',
        'caption_ar',
        'output_path',
        'poster_path',
        'status',
        'error',
        'tiktok_url',
        'duration',
    ];

    protected function casts(): array
    {
        return [
            'clip_start' => 'integer',
            'clip_duration' => 'integer',
            'duration' => 'integer',
        ];
    }

    public function tilawa(): BelongsTo
    {
        return $this->belongsTo(Tilawa::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    public function getOutputUrlAttribute(): ?string
    {
        return $this->output_path
            ? Storage::disk('public')->url($this->output_path)
            : null;
    }

    public function getPosterUrlAttribute(): ?string
    {
        return $this->poster_path
            ? Storage::disk('public')->url($this->poster_path)
            : null;
    }
}
