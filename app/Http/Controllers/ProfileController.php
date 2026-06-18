<?php

namespace App\Http\Controllers;

use App\Services\ListeningStats;
use Illuminate\Contracts\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = auth()->user();
        $stats = new ListeningStats($user);

        return view('pages.profile', [
            'totals' => $stats->totals(),
            'streak' => $stats->streak(),
            'hourHistogram' => $stats->hourHistogram(),
            'dailyTrend' => $stats->dailyTrend(30),
            'topQaris' => $stats->topQaris(5),
            'topTilawat' => $stats->topTilawat(5),
        ]);
    }
}
