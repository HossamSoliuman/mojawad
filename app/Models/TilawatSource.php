<?php

namespace App\Models;

use Database\Factories\TilawatSourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TilawatSource extends Model
{
    /** @use HasFactory<TilawatSourceFactory> */
    use HasFactory;

    protected $fillable = [
        'tilawa_id',
        'source_type',
        'source_url',
        'source_video_id',
        'source_title',
        'thumbnail_url',
        'qari_id',
        'status',
        'error',
        'created_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function tilawa(): BelongsTo
    {
        return $this->belongsTo(Tilawa::class);
    }

    public function qari(): BelongsTo
    {
        return $this->belongsTo(Qari::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }
}
