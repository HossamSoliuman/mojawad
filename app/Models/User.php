<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'avatar'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function qaris()
    {
        return $this->hasMany(Qari::class, 'created_by');
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function savedTilawat()
    {
        return $this->hasMany(SavedTilawa::class);
    }

    public function watchHistories()
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function followedQaris()
    {
        return $this->belongsToMany(Qari::class, 'follows')->withTimestamps();
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar ? asset('storage/'.$this->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=c9a153&color=07070f&size=128';
    }
}
