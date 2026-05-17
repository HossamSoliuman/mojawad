<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TmpUpload extends Model
{
    protected $table = 'tmp_uploads';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'uploaded_by',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'size'       => 'integer',
    ];

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }
}
