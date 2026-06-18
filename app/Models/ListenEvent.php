<?php

namespace App\Models;

use Database\Factories\ListenEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListenEvent extends Model
{
    /** @use HasFactory<ListenEventFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'tilawa_id', 'qari_id', 'seconds', 'listened_at'];

    protected function casts(): array
    {
        return [
            'seconds' => 'integer',
            'listened_at' => 'datetime',
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

    public function qari(): BelongsTo
    {
        return $this->belongsTo(Qari::class);
    }
}
