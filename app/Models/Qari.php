<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Qari extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'image', 'biography', 'created_by', 'is_featured', 'status'];
    protected $casts = ['is_featured' => 'boolean'];
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function tilawat()
    {
        return $this->hasMany(Tilawa::class);
    }
    public function getImageUrlAttribute(): string
    {
        return $this->image ? asset('storage/' . $this->image) : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=1e1e35&color=c9a153&size=400';
    }
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
