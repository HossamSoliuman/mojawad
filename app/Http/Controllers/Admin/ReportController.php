<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadLog;
use App\Models\Like;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\TilawaReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $range = (int) $request->input('range', 30);
        if (! in_array($range, [7, 30, 90, 365], true)) {
            $range = 30;
        }

        $from = now()->subDays($range - 1)->startOfDay();

        $series = [
            'downloads' => $this->dailySeries(DownloadLog::query(), 'downloaded_at', $from, $range),
            'likes' => $this->dailySeries(Like::query(), 'created_at', $from, $range),
            'tilawat' => $this->dailySeries(Tilawa::query(), 'created_at', $from, $range),
            'users' => $this->dailySeries(User::query(), 'created_at', $from, $range),
        ];

        $topDownloaded = DownloadLog::query()
            ->where('downloaded_at', '>=', $from)
            ->selectRaw('tilawa_id, COUNT(*) as total')
            ->groupBy('tilawa_id')
            ->orderByDesc('total')
            ->take(10)
            ->with('tilawa.qari')
            ->get()
            ->filter(fn ($row) => $row->tilawa !== null);

        $topLiked = Like::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('tilawa_id, COUNT(*) as total')
            ->groupBy('tilawa_id')
            ->orderByDesc('total')
            ->take(10)
            ->with('tilawa.qari')
            ->get()
            ->filter(fn ($row) => $row->tilawa !== null);

        $topQaris = Qari::query()
            ->withCount(['tilawat as active_tilawat_count' => fn ($q) => $q->where('status', 'active')])
            ->withSum('tilawat as total_likes', 'likes_count')
            ->withSum('tilawat as total_downloads', 'downloads_count')
            ->orderByDesc('total_downloads')
            ->take(10)
            ->get();

        $reviewRows = TilawaReview::query()
            ->where('created_at', '>=', $from)
            ->whereIn('action', ['approved', 'rejected'])
            ->with(['reviewer:id,name', 'tilawa:id,created_at'])
            ->get();

        $reviewStats = [
            'approved' => $reviewRows->where('action', 'approved')->count(),
            'rejected' => $reviewRows->where('action', 'rejected')->count(),
            'pending' => Tilawa::pendingReview()->count(),
            'avg_hours' => round($reviewRows
                ->filter(fn (TilawaReview $r) => $r->tilawa !== null)
                ->avg(fn (TilawaReview $r) => $r->tilawa->created_at->diffInHours($r->created_at)) ?? 0),
        ];

        $reviewers = $reviewRows
            ->filter(fn (TilawaReview $r) => $r->reviewer !== null)
            ->groupBy('reviewer_id')
            ->map(fn (Collection $rows) => [
                'name' => $rows->first()->reviewer->name,
                'approved' => $rows->where('action', 'approved')->count(),
                'rejected' => $rows->where('action', 'rejected')->count(),
            ])
            ->sortByDesc(fn (array $r) => $r['approved'] + $r['rejected'])
            ->values();

        $creators = Tilawa::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('uploaded_by, COUNT(*) as total')
            ->groupBy('uploaded_by')
            ->orderByDesc('total')
            ->with('uploader:id,name')
            ->take(10)
            ->get()
            ->filter(fn ($row) => $row->uploader !== null);

        $hosting = [
            'archive' => Tilawa::where('migrated_to_archive', true)->orWhereNotNull('archive_url')->count(),
            'local' => Tilawa::where('migrated_to_archive', false)->whereNull('archive_url')->count(),
        ];

        $statuses = Tilawa::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.reports', compact(
            'range', 'series', 'topDownloaded', 'topLiked', 'topQaris',
            'reviewStats', 'reviewers', 'creators', 'hosting', 'statuses'
        ));
    }

    /**
     * Per-day counts over the range with missing days filled with zeros.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    private function dailySeries($query, string $column, Carbon $from, int $days): array
    {
        $counts = $query
            ->where($column, '>=', $from)
            ->selectRaw("DATE({$column}) as d, COUNT(*) as c")
            ->groupBy('d')
            ->pluck('c', 'd');

        $labels = [];
        $values = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $labels[] = $day->format('Y-m-d');
            $values[] = (int) ($counts[$day->format('Y-m-d')] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
