<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadLog;
use App\Models\Like;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\User;
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
        $stats = [
            'total_qaris' => Qari::count(),
            'total_tilawat' => Tilawa::count(),
            'total_users' => User::count(),
            'total_downloads' => DownloadLog::count(),
            'total_likes' => Like::count(),
            'pending_qaris' => Qari::where('status', 'pending')->count(),
            'pending_tilawat' => Tilawa::where('status', 'pending')->count(),
            'pending_review' => Tilawa::pendingReview()->count(),
            'rejected_tilawat' => Tilawa::where('review_status', 'rejected')->count(),
        ];
        $recent_tilawat = Tilawa::with('qari', 'uploader')->latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recent_tilawat'));
    }
}
