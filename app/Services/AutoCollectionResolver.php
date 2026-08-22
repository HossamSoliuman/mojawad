<?php

namespace App\Services;

use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\WatchHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Builds the recitation list behind an auto (smart) collection — a collection
 * whose contents are computed from live platform stats instead of being picked
 * by hand in the admin panel.
 */
class AutoCollectionResolver
{
    public const RULE_RARE_GEMS = 'rare_gems';

    public const RULE_MOST_PLAYED = 'most_played';

    public const RULE_MOST_LIKED = 'most_liked';

    public const RULE_TRENDING = 'trending';

    public const RULE_RECENT = 'recent';

    public const RULE_MOST_DOWNLOADED = 'most_downloaded';

    /**
     * How many qaris count as "big" when picking rare recitations for them.
     */
    private const BIG_QARI_POOL = 15;

    /**
     * Every rule an admin can attach to a collection, keyed by its stored value.
     *
     * @return array<string, array{label: string, hint: string, icon: string}>
     */
    public static function rules(): array
    {
        return [
            self::RULE_RARE_GEMS => [
                'label' => __('Rare recitations by great qaris'),
                'hint' => __('Least-heard recitations from the most prominent qaris.'),
                'icon' => 'fa-gem',
            ],
            self::RULE_MOST_PLAYED => [
                'label' => __('Most played'),
                'hint' => __('Ranked by total plays.'),
                'icon' => 'fa-headphones',
            ],
            self::RULE_MOST_LIKED => [
                'label' => __('Most liked'),
                'hint' => __('Ranked by total likes.'),
                'icon' => 'fa-heart',
            ],
            self::RULE_TRENDING => [
                'label' => __('Trending this week'),
                'hint' => __('Most listened to over the last 7 days.'),
                'icon' => 'fa-fire',
            ],
            self::RULE_RECENT => [
                'label' => __('Newly added'),
                'hint' => __('The latest recitations published on the platform.'),
                'icon' => 'fa-sparkles',
            ],
            self::RULE_MOST_DOWNLOADED => [
                'label' => __('Most downloaded'),
                'hint' => __('Ranked by total downloads.'),
                'icon' => 'fa-download',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function ruleKeys(): array
    {
        return array_keys(self::rules());
    }

    public static function isValidRule(?string $rule): bool
    {
        return $rule !== null && in_array($rule, self::ruleKeys(), true);
    }

    public static function label(?string $rule): ?string
    {
        return self::rules()[$rule]['label'] ?? null;
    }

    /**
     * Resolve a rule into its ordered recitations.
     *
     * @return SupportCollection<int, Tilawa>
     */
    public function resolve(?string $rule, int $limit = 10): SupportCollection
    {
        if (! self::isValidRule($rule)) {
            return collect();
        }

        $limit = max(1, min($limit, 100));

        return match ($rule) {
            self::RULE_RARE_GEMS => $this->rareGems($limit),
            self::RULE_MOST_PLAYED => $this->baseQuery()->orderByDesc('plays_count')->orderByDesc('likes_count')->limit($limit)->get(),
            self::RULE_MOST_LIKED => $this->baseQuery()->orderByDesc('likes_count')->orderByDesc('plays_count')->limit($limit)->get(),
            self::RULE_TRENDING => $this->trending($limit),
            self::RULE_RECENT => $this->baseQuery()->latest()->limit($limit)->get(),
            self::RULE_MOST_DOWNLOADED => $this->baseQuery()->orderByDesc('downloads_count')->limit($limit)->get(),
        };
    }

    private function baseQuery(): Builder
    {
        return Tilawa::query()->with('qari')->where('status', 'active');
    }

    /**
     * Hidden treasures: recitations by the platform's most prominent qaris —
     * featured ones first, then those with the largest libraries — that almost
     * nobody has played yet.
     *
     * @return SupportCollection<int, Tilawa>
     */
    private function rareGems(int $limit): SupportCollection
    {
        $bigQariIds = Qari::query()
            ->where('status', 'active')
            ->withCount(['tilawat' => fn ($q) => $q->where('status', 'active')])
            ->whereHas('tilawat', fn ($q) => $q->where('status', 'active'))
            ->orderByDesc('is_featured')
            ->orderByDesc('tilawat_count')
            ->limit(self::BIG_QARI_POOL)
            ->pluck('id');

        if ($bigQariIds->isEmpty()) {
            return collect();
        }

        return $this->baseQuery()
            ->whereIn('qari_id', $bigQariIds)
            ->orderBy('plays_count')
            ->orderBy('likes_count')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return SupportCollection<int, Tilawa>
     */
    private function trending(int $limit): SupportCollection
    {
        $ranked = WatchHistory::query()
            ->where('last_watched_at', '>=', now()->subDays(7))
            ->select('tilawa_id')
            ->selectRaw('COUNT(*) as plays')
            ->groupBy('tilawa_id')
            ->orderByDesc('plays')
            ->limit($limit)
            ->pluck('tilawa_id');

        if ($ranked->isEmpty()) {
            return collect();
        }

        return $this->baseQuery()
            ->whereIn('id', $ranked)
            ->get()
            ->sortBy(fn (Tilawa $t) => $ranked->search($t->id))
            ->values();
    }
}
