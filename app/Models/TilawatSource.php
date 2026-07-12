<?php

namespace App\Models;

use App\Support\TilawaTitle;
use Database\Factories\TilawatSourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TilawatSource extends Model
{
    /** @use HasFactory<TilawatSourceFactory> */
    use HasFactory;

    /**
     * Source types handled by the Factory pipeline: direct uploads and files
     * pulled from the local recitation library.
     *
     * @var list<string>
     */
    public const FACTORY_TYPES = ['upload', 'library'];

    protected $fillable = [
        'tilawa_id',
        'source_type',
        'source_url',
        'source_video_id',
        'source_title',
        'thumbnail_url',
        'qari_id',
        'surah_number',
        'surahs',
        'part',
        'status',
        'error',
        'created_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'surahs' => 'array',
            'part' => 'integer',
        ];
    }

    public function tilawa(): BelongsTo
    {
        return $this->belongsTo(Tilawa::class);
    }

    public function qari(): BelongsTo
    {
        return $this->belongsTo(Qari::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Human label of the surah(s) this source covers, joined with the Arabic
     * conjunction when the file spans more than one surah.
     */
    public function getSurahLabelAttribute(): ?string
    {
        return TilawaTitle::surahList($this->surahs ?: $this->surah_number);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeUploads(Builder $query): Builder
    {
        return $query->whereIn('source_type', self::FACTORY_TYPES);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }
}
