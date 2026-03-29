<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Qari extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'photo',
        'bio',
        'created_by',
    ];

    public function videos()
    {
        return $this->hasMany(Video::class)->where('status', 'approved')->orderByDesc('score');
    }

    public function allVideos()
    {
        return $this->hasMany(Video::class)->orderByDesc('score');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
