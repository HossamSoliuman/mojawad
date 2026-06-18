<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class SurahController extends Controller
{
    public function show(int $number): View
    {
        abort_unless($number >= 1 && $number <= 114, 404);

        return view('pages.surah', [
            'number' => $number,
            'name' => config('surahs.'.$number, (string) $number),
        ]);
    }
}
