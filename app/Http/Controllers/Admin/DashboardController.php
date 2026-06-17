<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadLog;
use App\Models\Like;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        if (! $user->hasRole('admin')) {
            if ($user->hasRole('reviewer')) {
                return redirect()->route('admin.review.index');
            }

            return redirect()->route('admin.upload');
        }

        $weekAgo = now()->subDays(7);
        $prevWeek = now()->subDays(14);

        $stats = [
            'total_qaris' => Qari::count(),
            'total_tilawat' => Tilawa::count(),
            'active_tilawat' => Tilawa::where('status', 'active')->count(),
            'total_users' => User::count(),
            'total_downloads' => DownloadLog::count(),
            'total_likes' => Like::count(),
            'downloads_7d' => DownloadLog::where('downloaded_at', '>=', $weekAgo)->count(),
            'downloads_prev7d' => DownloadLog::whereBetween('downloaded_at', [$prevWeek, $weekAgo])->count(),
            'likes_7d' => Like::where('created_at', '>=', $weekAgo)->count(),
            'likes_prev7d' => Like::whereBetween('created_at', [$prevWeek, $weekAgo])->count(),
            'users_7d' => User::where('created_at', '>=', $weekAgo)->count(),
            'tilawat_30d' => Tilawa::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $attention = [
            'pending_review' => Tilawa::pendingReview()->count(),
            'rejected' => Tilawa::where('review_status', 'rejected')->count(),
            'pending_qaris' => Qari::where('status', 'pending')->count(),
            'stuck_uploads' => Tilawa::whereIn('upload_status', ['pending', 'uploading', 'failed'])
                ->where('created_at', '<', now()->subHour())->count(),
            'local_audio' => Tilawa::whereNull('archive_url')->whereNotNull('audio_path')->count(),
        ];

        $from = now()->subDays(29)->startOfDay();
        $charts = [
            'labels' => collect(range(0, 29))->map(fn (int $i) => $from->copy()->addDays($i)->format('Y-m-d'))->all(),
            'downloads' => $this->dailyCounts(DownloadLog::query(), 'downloaded_at', $from),
            'likes' => $this->dailyCounts(Like::query(), 'created_at', $from),
        ];

        $topTilawat = Tilawa::with('qari')
            ->where('status', 'active')
            ->orderByDesc('downloads_count')
            ->take(5)
            ->get();

        $topQaris = Qari::query()
            ->withCount(['tilawat as active_tilawat_count' => fn ($q) => $q->where('status', 'active')])
            ->withSum('tilawat as total_likes', 'likes_count')
            ->withSum('tilawat as total_downloads', 'downloads_count')
            ->orderByDesc('total_downloads')
            ->take(5)
            ->get();

        $recentTilawat = Tilawa::with('qari', 'uploader')->latest()->take(8)->get();

        return view('admin.dashboard', compact('stats', 'attention', 'charts', 'topTilawat', 'topQaris', 'recentTilawat'));
    }

    /**
     * Counts per day for the last 30 days, zero-filled.
     *
     * @return list<int>
     */
    private function dailyCounts($query, string $column, Carbon $from): array
    {
        $counts = $query
            ->where($column, '>=', $from)
            ->selectRaw("DATE({$column}) as d, COUNT(*) as c")
            ->groupBy('d')
            ->pluck('c', 'd');

        return collect(range(0, 29))
            ->map(fn (int $i) => (int) ($counts[$from->copy()->addDays($i)->format('Y-m-d')] ?? 0))
            ->all();
    }
}
