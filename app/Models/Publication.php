<?php

namespace App\Models;

use Database\Factories\PublicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Publication extends Model
{
    /** @use HasFactory<PublicationFactory> */
    use HasFactory;

    protected $fillable = [
        'tilawa_id',
        'platform',
        'status',
        'external_id',
        'external_url',
        'error',
        'meta',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'meta' => 'array',
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
}
