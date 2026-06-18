<?php

namespace App\Services;

use App\Models\DownloadLog;
use App\Models\ListenEvent;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ListeningStats
{
    /**
     * Memoized event rows (id-less projection) for the PHP-grouped insights.
     *
     * @var Collection<int, ListenEvent>|null
     */
    private ?Collection $events = null;

    public function __construct(private User $user) {}

    /**
     * Headline counters for the stat tiles.
     *
     * @return array{all_time_seconds: int, week_seconds: int, completed: int, liked: int, downloaded: int, saved: int}
     */
    public function totals(): array
    {
        $weekStart = Carbon::now()->startOfWeek();

        return [
            'all_time_seconds' => (int) $this->user->listenEvents()->sum('seconds'),
            'week_seconds' => (int) $this->user->listenEvents()->where('listened_at', '>=', $weekStart)->sum('seconds'),
            'completed' => $this->user->watchHistories()->where('completed', true)->count(),
            'liked' => $this->user->likes()->count(),
            'downloaded' => DownloadLog::where('user_id', $this->user->id)->count(),
            'saved' => $this->user->savedTilawat()->count(),
        ];
    }

    /**
     * Consecutive days with at least one listen event, ending today (or
     * yesterday if nothing has been heard yet today — the streak survives).
     */
    public function streak(): int
    {
        $days = $this->loadEvents()
            ->map(fn (ListenEvent $e) => $e->listened_at->toDateString())
            ->unique()
            ->flip();

        if ($days->isEmpty()) {
            return 0;
        }

        $cursor = Carbon::today();

        if (! $days->has($cursor->toDateString())) {
            $cursor = $cursor->subDay();

            if (! $days->has($cursor->toDateString())) {
                return 0;
            }
        }

        $streak = 0;

        while ($days->has($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    /**
     * Total seconds listened per hour of the day (0..23) — the "best time".
     *
     * @return array<int, int>
     */
    public function hourHistogram(): array
    {
        $buckets = array_fill(0, 24, 0);

        foreach ($this->loadEvents() as $event) {
            $buckets[(int) $event->listened_at->hour] += $event->seconds;
        }

        return $buckets;
    }

    /**
     * Seconds listened per day for the last $days days, oldest first, zeros filled.
     *
     * @return list<array{date: string, seconds: int}>
     */
    public function dailyTrend(int $days = 30): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $byDay = $this->loadEvents()
            ->filter(fn (ListenEvent $e) => $e->listened_at->gte($start))
            ->groupBy(fn (ListenEvent $e) => $e->listened_at->toDateString())
            ->map(fn (Collection $group) => (int) $group->sum('seconds'));

        $trend = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $trend[] = ['date' => $date, 'seconds' => (int) $byDay->get($date, 0)];
        }

        return $trend;
    }

    /**
     * Top reciters by summed listening time.
     *
     * @return list<array{qari: Qari, seconds: int}>
     */
    public function topQaris(int $limit = 5): array
    {
        $totals = $this->user->listenEvents()
            ->selectRaw('qari_id, SUM(seconds) as total')
            ->groupBy('qari_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'qari_id');

        if ($totals->isEmpty()) {
            return [];
        }

        $qaris = Qari::whereIn('id', $totals->keys())->get()->keyBy('id');

        return $totals
            ->map(fn ($seconds, $id) => $qaris->has($id)
                ? ['qari' => $qaris->get($id), 'seconds' => (int) $seconds]
                : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Most-listened recitations by summed listening time.
     *
     * @return list<array{tilawa: Tilawa, seconds: int}>
     */
    public function topTilawat(int $limit = 5): array
    {
        $totals = $this->user->listenEvents()
            ->selectRaw('tilawa_id, SUM(seconds) as total')
            ->groupBy('tilawa_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'tilawa_id');

        if ($totals->isEmpty()) {
            return [];
        }

        $tilawat = Tilawa::with('qari')->whereIn('id', $totals->keys())->get()->keyBy('id');

        return $totals
            ->map(fn ($seconds, $id) => $tilawat->has($id)
                ? ['tilawa' => $tilawat->get($id), 'seconds' => (int) $seconds]
                : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, ListenEvent>
     */
    private function loadEvents(): Collection
    {
        return $this->events ??= $this->user->listenEvents()
            ->select('listened_at', 'seconds')
            ->orderBy('listened_at')
            ->get();
    }
}
