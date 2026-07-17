<?php

namespace App\Models;

use App\Support\TilawaTitle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Tilawa extends Model
{
    use HasFactory;

    protected $table = 'tilawat';

    protected $fillable = ['qari_id', 'title_ar', 'title_en', 'slug', 'description_ar', 'description_en', 'recorded_at', 'recorded_place', 'surah_number', 'surahs', 'audio_path', 'duration', 'cover_image', 'uploaded_by', 'is_featured', 'downloads_count', 'plays_count', 'likes_count', 'status', 'review_status', 'rejection_note', 'reviewed_by', 'reviewed_at', 'ayah_from', 'ayah_to', 'ayah_confidence', 'subtitle_path', 'master_audio_path', 'brand_cover_path', 'brand_video_path', 'brand_status', 'brand_error', 'brand_card', 'production_stage'];

    protected $casts = ['is_featured' => 'boolean', 'recorded_at' => 'date', 'reviewed_at' => 'datetime', 'ayah_from' => 'integer', 'ayah_to' => 'integer', 'surahs' => 'array', 'brand_card' => 'array'];

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

    public function source(): HasOne
    {
        return $this->hasOne(TilawatSource::class);
    }

    public function clips(): HasMany
    {
        return $this->hasMany(VideoClip::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('review_status', 'pending');
    }

    /**
     * Match recitations belonging to a surah, whether it is the sole surah or
     * one of several a multi-surah recitation spans.
     */
    public function scopeForSurah(Builder $query, int $surah): Builder
    {
        return $query->where(function (Builder $q) use ($surah) {
            $q->where('surah_number', $surah)
                ->orWhereJsonContains('surahs', $surah);
        });
    }

    /**
     * A recitation that spans more than one surah — its `surahs` list holds the
     * ordered surah numbers and no single ayah range applies across them.
     */
    public function isMultiSurah(): bool
    {
        return is_array($this->surahs) && count($this->surahs) > 1;
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
     * Display label of every surah this recitation covers, collapsing to the
     * single surah name when it does not span multiple surahs.
     */
    public function getSurahLabelAttribute(): ?string
    {
        return TilawaTitle::surahList($this->surahs ?: $this->surah_number);
    }

    public function getBrandVideoUrlAttribute(): ?string
    {
        return $this->brand_video_path ? Storage::disk('public')->url($this->brand_video_path) : null;
    }

    public function getBrandCoverUrlAttribute(): ?string
    {
        return $this->brand_cover_path ? Storage::disk('public')->url($this->brand_cover_path) : null;
    }

    /**
     * The last rendered 16:9 video card image (stored inside brand_card so no
     * extra column is needed) — used as the video poster and list thumbnail.
     */
    public function getBrandCardImageUrlAttribute(): ?string
    {
        $path = $this->brand_card['card_image'] ?? null;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function getMasterAudioUrlAttribute(): ?string
    {
        return $this->master_audio_path ? Storage::disk('public')->url($this->master_audio_path) : null;
    }

    public function getSubtitleUrlAttribute(): ?string
    {
        return $this->subtitle_path ? Storage::disk('public')->url($this->subtitle_path) : null;
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
