<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\FacebookPostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacebookPost extends Model
{
    /** @use HasFactory<FacebookPostFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'ready', 'scheduled', 'published', 'archived'];

    public const LENGTHS = ['short', 'long'];

    public const ASPECT_RATIOS = ['4:5', '1:1', '16:9'];

    /**
     * The three workspace tabs. Every status belongs to exactly one stage, so a
     * post is always reachable from one of the tabs.
     *
     * @var array<string, list<string>>
     */
    public const STAGES = [
        'creation' => ['draft', 'ready'],
        'scheduled' => ['scheduled'],
        'posted' => ['published', 'archived'],
    ];

    /** Sentinel key used by the sidebar for posts saved without a category. */
    public const UNCATEGORIZED = '__none';

    /** @var array<string, array{label: string, icon: string, color: string}> */
    public const CATEGORIES = [
        'qari_story' => ['label' => 'Qari story', 'icon' => 'fa-microphone-lines', 'color' => '#0f766e'],
        'story_part' => ['label' => 'Story series', 'icon' => 'fa-book-open', 'color' => '#7c3aed'],
        'hadith' => ['label' => 'Hadith', 'icon' => 'fa-mosque', 'color' => '#b45309'],
        'tafseer' => ['label' => 'Tafseer', 'icon' => 'fa-star-and-crescent', 'color' => '#1d4ed8'],
        'quran_fact' => ['label' => 'Quran insight', 'icon' => 'fa-lightbulb', 'color' => '#be123c'],
    ];

    protected $fillable = [
        'facebook_campaign_id',
        'title',
        'category',
        'status',
        'length',
        'hook',
        'body',
        'cta',
        'hashtags',
        'visual_brief',
        'image_prompt',
        'aspect_ratio',
        'image_file',
        'visual_text',
        'alt_text',
        'review_notes',
        'publish_slot',
        'scheduled_for',
        'published_at',
        'external_id',
        'external_url',
        'publish_error',
        'publish_attempted_at',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'published_at' => 'datetime',
            'publish_attempted_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FacebookCampaign::class, 'facebook_campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCampaign(Builder $query, int|string $campaign): Builder
    {
        return $campaign === 'all' ? $query : $query->where('facebook_campaign_id', $campaign);
    }

    public function scopeInStage(Builder $query, string $stage): Builder
    {
        return $query->whereIn('status', self::stageStatuses($stage));
    }

    public function scopeForCategory(Builder $query, string $category): Builder
    {
        if ($category === 'all') {
            return $query;
        }

        if ($category === self::UNCATEGORIZED) {
            return $query->where(fn (Builder $query) => $query->whereNull('category')->orWhere('category', ''));
        }

        return $query->where('category', $category);
    }

    /**
     * Scheduled posts whose time has come and that the scheduler has not tried
     * yet. Claiming a row stamps `publish_attempted_at`, so the automatic
     * publisher touches each post exactly once — a failure waits for a human
     * rather than looping and risking a duplicate on the Page.
     */
    public function scopeDueForPublishing(Builder $query): Builder
    {
        return $query
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->whereNull('external_id')
            ->whereNull('publish_attempted_at');
    }

    /** Whether this post already exists on the Facebook Page. */
    public function isLive(): bool
    {
        return filled($this->external_id);
    }

    /**
     * The publishing slot read in the cadence timezone. Slots are stored in UTC
     * like every other timestamp, but an editor judges them against the clock the
     * audience lives by, so every screen shows this instead of the raw column.
     */
    public function localScheduledFor(): ?CarbonInterface
    {
        return $this->scheduled_for?->copy()->setTimezone(self::slotTimezone());
    }

    public static function slotTimezone(): string
    {
        return (string) config('publishing.facebook.cadence.timezone', config('app.timezone'));
    }

    /** @return list<string> */
    public static function stageStatuses(string $stage): array
    {
        return self::STAGES[$stage] ?? self::STATUSES;
    }

    public static function stageForStatus(string $status): string
    {
        foreach (self::STAGES as $stage => $statuses) {
            if (in_array($status, $statuses, true)) {
                return $stage;
            }
        }

        return array_key_first(self::STAGES);
    }

    public function fullText(): string
    {
        return implode("\n\n", array_filter([
            trim($this->hook),
            trim($this->body),
            trim((string) $this->cta),
            trim((string) $this->hashtags),
        ]));
    }

    /** @return list<string> */
    public function hashtagList(): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim((string) $this->hashtags)) ?: []));
    }

    /** @return list<string> */
    public function reviewNoteList(): array
    {
        return array_values(array_filter(preg_split('/\R+/u', trim((string) $this->review_notes)) ?: []));
    }

    public function imagePath(): ?string
    {
        $file = basename(trim((string) $this->image_file));

        if ($file === '') {
            return null;
        }

        return rtrim((string) config('publishing.campaign_images'), '\\/').DIRECTORY_SEPARATOR.$file;
    }

    public function hasImage(): bool
    {
        return $this->imagePath() !== null && is_file($this->imagePath());
    }

    public function imageVersion(): int
    {
        return $this->hasImage() ? (int) filemtime($this->imagePath()) : 0;
    }

    /** @return array{label: string, icon: string, color: string} */
    public function categoryPresentation(): array
    {
        return self::categoryMeta((string) $this->category);
    }

    /** @return array{label: string, icon: string, color: string} */
    public static function categoryMeta(string $category): array
    {
        if ($category === '' || $category === self::UNCATEGORIZED) {
            return ['label' => 'Uncategorized', 'icon' => 'fa-note-sticky', 'color' => '#475569'];
        }

        return self::CATEGORIES[$category] ?? [
            'label' => str($category)->replace('_', ' ')->title()->toString(),
            'icon' => 'fa-note-sticky',
            'color' => '#475569',
        ];
    }
}
