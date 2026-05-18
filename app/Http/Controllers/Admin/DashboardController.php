<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{DownloadLog, Like, Qari, Tilawa, User};

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user->hasRole('admin')) {
            $stats = [
                'total_qaris' => Qari::count(),
                'total_tilawat' => Tilawa::count(),
                'total_users' => User::count(),
                'total_downloads' => DownloadLog::count(),
                'total_likes' => Like::count(),
                'pending_qaris' => Qari::where('status', 'pending')->count(),
                'pending_tilawat' => Tilawa::where('status', 'pending')->count()
            ];
            $recent_tilawat = Tilawa::with('qari', 'uploader')->latest()->take(10)->get();
        } else {
            $stats = [
                'total_qaris' => Qari::where('created_by', $user->id)->count(),
                'total_tilawat' => Tilawa::where('uploaded_by', $user->id)->count(),
                'total_users' => 0,
                'total_downloads' => Tilawa::where('uploaded_by', $user->id)->sum('downloads_count'),
                'total_likes' => Tilawa::where('uploaded_by', $user->id)->sum('likes_count'),
                'pending_qaris' => Qari::where('created_by', $user->id)->where('status', 'pending')->count(),
                'pending_tilawat' => Tilawa::where('uploaded_by', $user->id)->where('status', 'pending')->count()
            ];
            $recent_tilawat = Tilawa::with('qari', 'uploader')->where('uploaded_by', $user->id)->latest()->take(10)->get();
        }
        return view('admin.dashboard', compact('stats', 'recent_tilawat'));
    }
}
