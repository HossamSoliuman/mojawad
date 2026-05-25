<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Qari extends Model
{
    use HasFactory;
    protected $fillable = ['name_ar', 'name_en', 'slug', 'image', 'biography_ar', 'biography_en', 'created_by', 'is_featured', 'status'];
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
        return $this->image
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->image)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name_ar) . '&background=1e1e35&color=c9a153&size=400';
    }

    public function getNameAttribute(): string
    {
        return (app()->getLocale() === 'en' && $this->name_en) ? $this->name_en : $this->name_ar;
    }

    public function getBiographyAttribute(): ?string
    {
        return (app()->getLocale() === 'en' && $this->biography_en) ? $this->biography_en : $this->biography_ar;
    }
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
