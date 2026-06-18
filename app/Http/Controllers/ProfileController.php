<?php

namespace App\Http\Controllers;

use App\Models\DownloadLog;
use Carbon\CarbonInterval;
use Illuminate\Contracts\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = auth()->user();

        $histories = $user->watchHistories()->with('tilawa.qari')->get();

        $totalSeconds = (int) $histories->sum(fn ($h) => $h->completed ? $h->duration : $h->position);

        $listeningTime = $totalSeconds > 0
            ? CarbonInterval::seconds($totalSeconds)->cascade()->forHumans(['short' => true, 'parts' => 2])
            : null;

        $topQari = $histories
            ->filter(fn ($h) => $h->tilawa && $h->tilawa->qari)
            ->groupBy(fn ($h) => $h->tilawa->qari->id)
            ->sortByDesc(fn ($group) => $group->count())
            ->first()?->first()?->tilawa->qari;

        return view('pages.profile', [
            'likedCount' => $user->likes()->count(),
            'savedCount' => $user->savedTilawat()->count(),
            'watchedCount' => $histories->count(),
            'downloadsCount' => DownloadLog::where('user_id', $user->id)->count(),
            'listeningTime' => $listeningTime,
            'topQari' => $topQari,
        ]);
    }
}
