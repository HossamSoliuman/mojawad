<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{DownloadLog, Like, Qari, Tilawa, User};

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = ['total_qaris' => Qari::count(), 'total_tilawat' => Tilawa::count(), 'total_users' => User::count(), 'total_downloads' => DownloadLog::count(), 'total_likes' => Like::count(), 'pending_qaris' => Qari::where('status', 'pending')->count(), 'pending_tilawat' => Tilawa::where('status', 'pending')->count()];
        $recent_tilawat = Tilawa::with('qari', 'uploader')->latest()->take(10)->get();
        return view('admin.dashboard', compact('stats', 'recent_tilawat'));
    }
}
