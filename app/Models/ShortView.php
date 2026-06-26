<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortView extends Model
{
    protected $fillable = [
        'short_id',
        'viewer_key',
        'views',
        'last_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'views' => 'integer',
            'last_viewed_at' => 'datetime',
        ];
    }

    public function short(): BelongsTo
    {
        return $this->belongsTo(Short::class);
    }
}
