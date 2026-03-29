<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'qari_id',
        'surah_id',
        'type',
        'file_path',
        'external_url',
        'status',
        'views',
        'likes',
        'saves',
        'score',
    ];

    protected $casts = [
        'views' => 'integer',
        'likes' => 'integer',
        'saves' => 'integer',
        'score' => 'integer',
    ];

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isHosted(): bool
    {
        return $this->type === 'hosted';
    }

    public function isLinked(): bool
    {
        return $this->type === 'linked';
    }

    public function recomputeScore(): void
    {
        $this->score = ($this->views * 1) + ($this->likes * 3) + ($this->saves * 5);
        $this->save();
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRanked($query)
    {
        return $query->orderByDesc('score');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function qari()
    {
        return $this->belongsTo(Qari::class);
    }

    public function surah()
    {
        return $this->belongsTo(Surah::class);
    }

    public function videoLikes()
    {
        return $this->hasMany(VideoLike::class);
    }

    public function videoSaves()
    {
        return $this->hasMany(VideoSave::class);
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'video_likes')->withTimestamps();
    }

    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'video_saves')->withTimestamps();
    }
}
