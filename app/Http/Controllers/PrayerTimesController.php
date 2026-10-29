<?php

namespace App\Http\Controllers;

use App\Services\PrayerLocator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrayerTimesController extends Controller
{
    /**
     * Prayer schedule for the location the browser stored in its cookie.
     */
    public function __invoke(Request $request, PrayerLocator $locator): JsonResponse
    {
        return response()->json($locator->resolve($request->cookie(PrayerLocator::COOKIE)));
    }
}
