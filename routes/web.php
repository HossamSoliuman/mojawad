<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\QariController;
use App\Http\Controllers\TilawaController;
use Illuminate\Support\Facades\Route;

// ── Public ──────────────────────────────────────────────────────────────
Route::get('/', HomeController::class)->name('home');

Route::prefix('qaris')->name('qaris.')->group(function () {
    Route::get('/',            [QariController::class, 'index'])->name('index');
    Route::get('/{qari:slug}', [QariController::class, 'show'])->name('show');
});

Route::prefix('tilawa')->name('tilawa.')->group(function () {
    Route::get('/{tilawa:slug}',          [TilawaController::class, 'show'])->name('show');
    Route::get('/{tilawa:slug}/download', [TilawaController::class, 'download'])
         ->name('download')->middleware('throttle:30,1');
});

// ── Auth-required ────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/library', fn() => view('pages.library'))->name('library');
    Route::get('/profile', fn() => view('pages.profile'))->name('profile');
});

// ── AJAX API ─────────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('api')->group(function () {
    Route::post('/like/{tilawa}',  [\App\Http\Controllers\Api\LikeController::class,   'toggle'])->name('api.like');
    Route::post('/save/{tilawa}',  [\App\Http\Controllers\Api\SaveController::class,   'toggle'])->name('api.save');
});
Route::get('/api/search', \App\Http\Controllers\Api\SearchController::class)->name('api.search');

// ── Admin ────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin|creator'])->group(function () {

    Route::get('/', DashboardController::class)->name('dashboard');

    // Qaris CRUD
    Route::get('/qaris',               [\App\Http\Controllers\Admin\QariController::class, 'index'])->name('qaris.index');
    Route::get('/qaris/create',        [\App\Http\Controllers\Admin\QariController::class, 'create'])->name('qaris.create');
    Route::post('/qaris',              [\App\Http\Controllers\Admin\QariController::class, 'store'])->name('qaris.store');
    Route::get('/qaris/{qari}/edit',   [\App\Http\Controllers\Admin\QariController::class, 'edit'])->name('qaris.edit');
    Route::put('/qaris/{qari}',        [\App\Http\Controllers\Admin\QariController::class, 'update'])->name('qaris.update');
    Route::delete('/qaris/{qari}',     [\App\Http\Controllers\Admin\QariController::class, 'destroy'])->name('qaris.destroy');

    // Tilawat CRUD
    Route::get('/tilawat',               [\App\Http\Controllers\Admin\TilawaController::class, 'index'])->name('tilawat.index');
    Route::get('/tilawat/create',        [\App\Http\Controllers\Admin\TilawaController::class, 'create'])->name('tilawat.create');
    Route::post('/tilawat',              [\App\Http\Controllers\Admin\TilawaController::class, 'store'])->name('tilawat.store');
    Route::get('/tilawat/{tilawa}/edit', [\App\Http\Controllers\Admin\TilawaController::class, 'edit'])->name('tilawat.edit');
    Route::put('/tilawat/{tilawa}',      [\App\Http\Controllers\Admin\TilawaController::class, 'update'])->name('tilawat.update');
    Route::delete('/tilawat/{tilawa}',   [\App\Http\Controllers\Admin\TilawaController::class, 'destroy'])->name('tilawat.destroy');

    // Users (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/users',                    [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/role',        [\App\Http\Controllers\Admin\UserController::class, 'updateRole'])->name('users.role');
    });
});

require __DIR__.'/auth.php';
