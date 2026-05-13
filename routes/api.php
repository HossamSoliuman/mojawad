<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\QariController;
use App\Http\Controllers\Api\TilawaController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\SaveController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

// ── Public API ───────────────────────────────────────────────────────────────

Route::get('/home', HomeController::class);

Route::prefix('qaris')->group(function () {
    Route::get('/', [QariController::class, 'index']);
    Route::get('/{slug}', [QariController::class, 'show']);
});

Route::prefix('tilawat')->group(function () {
    Route::get('/{slug}', [TilawaController::class, 'show']);
    Route::get('/{slug}/download', [TilawaController::class, 'download'])
        ->middleware('throttle:30,1');
});

Route::get('/search', SearchController::class);

// ── Auth ─────────────────────────────────────────────────────────────────────

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });
});

// ── Authenticated user actions ───────────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/like/{tilawa}', [LikeController::class, 'toggle']);
    Route::post('/save/{tilawa}', [SaveController::class, 'toggle']);
    Route::get('/library',        [ProfileController::class, 'library']);
    Route::put('/profile',        [ProfileController::class, 'update']);
});

// ── Language ──────────────────────────────────────────────────────────────────
// Handled by Next.js i18n — no server-side locale switching needed.
