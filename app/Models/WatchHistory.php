<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchHistory extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'tilawa_id', 'position', 'duration', 'completed', 'last_watched_at'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'duration' => 'integer',
            'completed' => 'boolean',
            'last_watched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tilawa(): BelongsTo
    {
        return $this->belongsTo(Tilawa::class);
    }

    public function getProgressPercentAttribute(): int
    {
        return $this->duration ? (int) min(100, round($this->position / $this->duration * 100)) : 0;
    }
}
