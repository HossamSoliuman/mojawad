<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadLog extends Model
{
    protected $table = 'downloads_log';

    public $timestamps = false;

    protected $fillable = ['user_id', 'tilawa_id', 'ip_address', 'downloaded_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tilawa(): BelongsTo
    {
        return $this->belongsTo(Tilawa::class);
    }
}
