<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\TmpUploadController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\SaveController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\WatchHistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\QariController;
use App\Http\Controllers\TilawaController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// ── Public ──────────────────────────────────────────────────────────────
Route::get('/', HomeController::class)->name('home');

Route::prefix('qaris')->name('qaris.')->group(function () {
    Route::get('/', [QariController::class, 'index'])->name('index');
    Route::get('/{qari:slug}', [QariController::class, 'show'])->name('show');
});

Route::prefix('tilawa')->name('tilawa.')->group(function () {
    Route::get('/{tilawa:slug}', [TilawaController::class, 'show'])->name('show');
    Route::get('/{tilawa:slug}/download', [TilawaController::class, 'download'])
        ->name('download')->middleware('throttle:30,1');
});

// ── Likes (guests keep theirs in localStorage until they sign in) ────────
Route::get('/likes', fn () => view('pages.likes'))->name('likes');

// ── Auth-required ────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', fn () => view('pages.profile'))->name('profile');
});

// ── AJAX API ─────────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/likes/ids', [LikeController::class,        'ids'])->name('api.likes.ids');
    Route::get('/like/{tilawa}', [LikeController::class,    'status'])->name('api.like.status');
    Route::post('/like/{tilawa}', [LikeController::class,   'toggle'])->name('api.like');
    Route::post('/likes/sync', [LikeController::class,      'sync'])->name('api.likes.sync');
    Route::post('/save/{tilawa}', [SaveController::class,   'toggle'])->name('api.save');

    Route::get('/history', [WatchHistoryController::class,          'index'])->name('api.history.index');
    Route::post('/history', [WatchHistoryController::class,         'store'])->name('api.history.store');
    Route::post('/history/sync', [WatchHistoryController::class,    'sync'])->name('api.history.sync');
    Route::delete('/history/{tilawa}', [WatchHistoryController::class, 'destroy'])->name('api.history.destroy');
    Route::delete('/history', [WatchHistoryController::class,       'clear'])->name('api.history.clear');
});
Route::get('/api/search', SearchController::class)->name('api.search');

// ── Admin ────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {

    // ── Reviewer queue (reviewer role only) ───────────────────────────────
    Route::middleware('role:reviewer')->group(function () {
        Route::get('/review', [ReviewController::class, 'index'])->name('review.index');
        Route::get('/review/history', [ReviewController::class, 'history'])->name('review.history');
    });

    // ── Focused uploader (creators only — admins manage, creators upload) ─
    Route::middleware('role:creator')->group(function () {
        Route::get('/upload', [App\Http\Controllers\Admin\TilawaController::class, 'uploader'])->name('upload');
        Route::post('/uploader/qari', [App\Http\Controllers\Admin\TilawaController::class, 'setDefaultQari'])->name('uploader.qari');
        Route::post('/tilawat/quick-store', [App\Http\Controllers\Admin\TilawaController::class, 'quickStore'])->name('tilawat.quick-store');
    });

    // ── Content management (admins & creators) ────────────────────────────
    Route::middleware('role:admin|creator')->group(function () {

        Route::get('/', DashboardController::class)->name('dashboard');

        Route::post('/upload/tmp', [TmpUploadController::class, 'store'])->name('upload.tmp');
        Route::delete('/upload/tmp/{token}', [TmpUploadController::class, 'destroy'])->name('upload.tmp.destroy');

        Route::get('/qaris', [App\Http\Controllers\Admin\QariController::class, 'index'])->name('qaris.index');
        Route::get('/qaris/create', [App\Http\Controllers\Admin\QariController::class, 'create'])->name('qaris.create');
        Route::post('/qaris', [App\Http\Controllers\Admin\QariController::class, 'store'])->name('qaris.store');
        Route::get('/qaris/{qari}/edit', [App\Http\Controllers\Admin\QariController::class, 'edit'])->name('qaris.edit')->middleware('can:update,qari');
        Route::put('/qaris/{qari}', [App\Http\Controllers\Admin\QariController::class, 'update'])->name('qaris.update')->middleware('can:update,qari');
        Route::delete('/qaris/{qari}', [App\Http\Controllers\Admin\QariController::class, 'destroy'])->name('qaris.destroy')->middleware('can:delete,qari');

        Route::get('/tilawat', [App\Http\Controllers\Admin\TilawaController::class, 'index'])->name('tilawat.index');
        Route::get('/tilawat/{tilawa}/edit', [App\Http\Controllers\Admin\TilawaController::class, 'edit'])->name('tilawat.edit')->middleware('can:update,tilawa');
        Route::put('/tilawat/{tilawa}/quick', [App\Http\Controllers\Admin\TilawaController::class, 'quickUpdate'])->name('tilawat.quick-update')->middleware('role:admin');
        Route::put('/tilawat/{tilawa}', [App\Http\Controllers\Admin\TilawaController::class, 'update'])->name('tilawat.update')->middleware('can:update,tilawa');
        Route::delete('/tilawat/{tilawa}', [App\Http\Controllers\Admin\TilawaController::class, 'destroy'])->name('tilawat.destroy')->middleware('can:delete,tilawa');

        Route::middleware('role:admin')->group(function () {
            Route::get('/reports', ReportController::class)->name('reports');
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });

    }); // end role:admin|creator content group
});

Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'ar'])) {
        session()->put('locale', $locale);
    }

    return redirect()->back();
})->name('lang');

Route::get('/storage-link', function () {
    Artisan::call('storage:link');

    return response('OK', 200);
})->withoutMiddleware(['web']);

require __DIR__.'/auth.php';
