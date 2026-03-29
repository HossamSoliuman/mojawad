<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCreator(): bool
    {
        return $this->role === 'creator';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function canModerate(): bool
    {
        return in_array($this->role, ['creator', 'admin']);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function likes()
    {
        return $this->hasMany(VideoLike::class);
    }

    public function saves()
    {
        return $this->hasMany(VideoSave::class);
    }

    public function savedVideos()
    {
        return $this->belongsToMany(Video::class, 'video_saves')->withTimestamps();
    }

    public function likedVideos()
    {
        return $this->belongsToMany(Video::class, 'video_likes')->withTimestamps();
    }

    public function creatorApplication()
    {
        return $this->hasOne(CreatorApplication::class);
    }

    public function qaris()
    {
        return $this->hasMany(Qari::class, 'created_by');
    }
}
