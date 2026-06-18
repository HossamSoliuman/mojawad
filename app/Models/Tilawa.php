<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tilawa extends Model
{
    use HasFactory;

    protected $table = 'tilawat';

    protected $fillable = ['qari_id', 'title_ar', 'title_en', 'slug', 'description_ar', 'description_en', 'recorded_at', 'recorded_place', 'surah_number', 'audio_path', 'duration', 'cover_image', 'uploaded_by', 'is_featured', 'downloads_count', 'plays_count', 'likes_count', 'status', 'review_status', 'rejection_note', 'reviewed_by', 'reviewed_at'];

    protected $casts = ['is_featured' => 'boolean', 'recorded_at' => 'date', 'reviewed_at' => 'datetime'];

    public function qari()
    {
        return $this->belongsTo(Qari::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TilawaReview::class)->latest();
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('review_status', 'pending');
    }

    public function logReview(string $action, ?string $note = null, ?int $reviewerId = null): TilawaReview
    {
        return $this->reviews()->create([
            'reviewer_id' => $reviewerId,
            'action' => $action,
            'note' => $note,
        ]);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function savedByUsers()
    {
        return $this->hasMany(SavedTilawa::class);
    }

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image) {
            return asset('storage/'.$this->cover_image);
        }

        return $this->relationLoaded('qari') && $this->qari ? $this->qari->image_url : asset('images/default-cover.jpg');
    }

    public function getAudioUrlAttribute(): string
    {
        return $this->audio_src;
    }

    public function getAudioSrcAttribute(): string
    {
        return asset('uploads/'.$this->audio_path);
    }

    public function getFormattedDurationAttribute(): string
    {
        $m = floor($this->duration / 60);
        $s = $this->duration % 60;

        return sprintf('%d:%02d', $m, $s);
    }

    public function getTitleAttribute(): string
    {
        return (app()->getLocale() === 'en' && $this->title_en) ? $this->title_en : $this->title_ar;
    }

    public function getDescriptionAttribute(): ?string
    {
        return (app()->getLocale() === 'en' && $this->description_en) ? $this->description_en : $this->description_ar;
    }

    public function getSurahNameAttribute(): ?string
    {
        return $this->surah_number ? config('surahs.'.$this->surah_number) : null;
    }

    /**
     * Payload consumed by the persistent audio player (data-track attribute).
     *
     * @return array{id: int, url: string, title: string, qari: string, cover: string, duration: int, download: string}
     */
    public function playerPayload(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->audio_url,
            'title' => $this->title,
            'qari' => $this->qari->name,
            'cover' => $this->cover_url,
            'duration' => (int) $this->duration,
            'download' => route('tilawa.download', $this),
        ];
    }
}
