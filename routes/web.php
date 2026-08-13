<?php

use App\Http\Controllers\Admin\ClipController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PublishingController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\ShortController;
use App\Http\Controllers\Admin\SocialController;
use App\Http\Controllers\Admin\StudioController;
use App\Http\Controllers\Admin\TmpUploadController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\PlayController;
use App\Http\Controllers\Api\SaveController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\WatchHistoryController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PodcastFeedController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QariController;
use App\Http\Controllers\ShortViewController;
use App\Http\Controllers\SurahController;
use App\Http\Controllers\TilawaController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// ── Public ──────────────────────────────────────────────────────────────
Route::get('/', HomeController::class)->name('home');
Route::post('/shorts/{short}/view', ShortViewController::class)->name('shorts.view');

// Podcast RSS feed pulled by Spotify & Anghami — submit this URL to each once.
Route::get('/podcast/feed.xml', [PodcastFeedController::class, 'feed'])->name('podcast.feed');

// Required by Meta before a Facebook app can leave development mode.
Route::view('/privacy', 'pages.privacy')->name('privacy');

Route::prefix('qaris')->name('qaris.')->group(function () {
    Route::get('/', [QariController::class, 'index'])->name('index');
    Route::get('/{qari:slug}', [QariController::class, 'show'])->name('show');
});

Route::get('/surah/{number}', [SurahController::class, 'show'])
    ->whereNumber('number')->name('surah.show');

Route::prefix('collections')->name('collections.')->group(function () {
    Route::get('/', [CollectionController::class, 'index'])->name('index');
    Route::get('/{collection:slug}', [CollectionController::class, 'show'])->name('show');
});

Route::prefix('tilawa')->name('tilawa.')->group(function () {
    Route::get('/{tilawa:slug}', [TilawaController::class, 'show'])->name('show');
    Route::get('/{tilawa:slug}/download', [TilawaController::class, 'download'])
        ->name('download')->middleware('throttle:30,1');
});

// ── Likes & saves (guests keep theirs in localStorage until they sign in) ─
Route::get('/likes', fn () => view('pages.likes'))->name('likes');
Route::get('/saved', fn () => view('pages.saved'))->name('saved');

// ── Auth-required ────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
});

// ── AJAX API ─────────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/likes/ids', [LikeController::class,        'ids'])->name('api.likes.ids');
    Route::get('/like/{tilawa}', [LikeController::class,    'status'])->name('api.like.status');
    Route::post('/like/{tilawa}', [LikeController::class,   'toggle'])->name('api.like');
    Route::post('/likes/sync', [LikeController::class,      'sync'])->name('api.likes.sync');
    Route::get('/follows/ids', [FollowController::class,    'ids'])->name('api.follows.ids');
    Route::post('/follow/{qari:id}', [FollowController::class, 'toggle'])->name('api.follow');
    Route::post('/follows/sync', [FollowController::class,   'sync'])->name('api.follows.sync');

    Route::get('/saves/ids', [SaveController::class,        'ids'])->name('api.saves.ids');
    Route::get('/save/{tilawa}', [SaveController::class,    'status'])->name('api.save.status');
    Route::post('/save/{tilawa}', [SaveController::class,   'toggle'])->name('api.save');
    Route::post('/saves/sync', [SaveController::class,      'sync'])->name('api.saves.sync');

    Route::get('/history', [WatchHistoryController::class,          'index'])->name('api.history.index');
    Route::post('/history', [WatchHistoryController::class,         'store'])->name('api.history.store');
    Route::post('/history/sync', [WatchHistoryController::class,    'sync'])->name('api.history.sync');
    Route::delete('/history/{tilawa}', [WatchHistoryController::class, 'destroy'])->name('api.history.destroy');
    Route::delete('/history', [WatchHistoryController::class,       'clear'])->name('api.history.clear');
});
Route::get('/api/search', SearchController::class)->name('api.search');
Route::post('/api/play/{tilawa}', [PlayController::class, 'increment'])
    ->name('api.play')->middleware('throttle:60,1');

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

        Route::get('/shorts', [ShortController::class, 'index'])->name('shorts.index');
        Route::get('/shorts/create', [ShortController::class, 'create'])->name('shorts.create');
        Route::post('/shorts', [ShortController::class, 'store'])->name('shorts.store');
        Route::get('/shorts/import-poll', [ShortController::class, 'importPoll'])->name('shorts.import-poll');
        Route::post('/shorts/{short}/retry', [ShortController::class, 'retryImport'])->name('shorts.retry');
        Route::get('/shorts/{short}/edit', [ShortController::class, 'edit'])->name('shorts.edit')->middleware('can:update,short');
        Route::put('/shorts/{short}', [ShortController::class, 'update'])->name('shorts.update')->middleware('can:update,short');
        Route::delete('/shorts/{short}', [ShortController::class, 'destroy'])->name('shorts.destroy')->middleware('can:delete,short');

        Route::get('/studio', [StudioController::class, 'index'])->name('studio.index');
        Route::post('/studio/import', [StudioController::class, 'store'])->name('studio.import');
        Route::post('/studio/import-file', [StudioController::class, 'storeFile'])->name('studio.import-file');
        Route::post('/studio/{source}/retry', [StudioController::class, 'retry'])->name('studio.retry')->middleware('can:update,source');
        Route::delete('/studio/{source}', [StudioController::class, 'destroy'])->name('studio.destroy')->middleware('can:delete,source');

        Route::get('/clips', [ClipController::class, 'index'])->name('clips.index');
        Route::get('/clips/search', [ClipController::class, 'search'])->name('clips.search');
        Route::post('/clips', [ClipController::class, 'store'])->name('clips.store');
        Route::get('/clips/{clip}/download', [ClipController::class, 'download'])->name('clips.download')->middleware('can:view,clip');
        Route::post('/clips/{clip}/retry', [ClipController::class, 'retry'])->name('clips.retry')->middleware('can:update,clip');
        Route::patch('/clips/{clip}/tiktok', [ClipController::class, 'tiktok'])->name('clips.tiktok')->middleware('can:update,clip');
        Route::delete('/clips/{clip}', [ClipController::class, 'destroy'])->name('clips.destroy')->middleware('can:delete,clip');

        Route::get('/publishing/factory', [PublishingController::class, 'factory'])->name('publishing.factory');
        Route::post('/publishing/ingest', [PublishingController::class, 'ingest'])->name('publishing.ingest');
        Route::get('/publishing/production', [PublishingController::class, 'production'])->name('publishing.production');
        Route::get('/publishing/production/{tilawa}/download', [PublishingController::class, 'downloadVideo'])
            ->name('publishing.production.download')->middleware('can:view,tilawa');
        Route::get('/publishing/card-lab', [PublishingController::class, 'cardLab'])->name('publishing.card-lab');

        Route::get('/social/facebook', [SocialController::class, 'facebook'])->name('social.facebook');
        Route::get('/social/facebook/{post}/image', [SocialController::class, 'poster'])->name('social.poster');
        Route::get('/social/facebook/{post}/image/download', [SocialController::class, 'downloadPoster'])->name('social.poster.download');

        Route::get('/tilawat', [App\Http\Controllers\Admin\TilawaController::class, 'index'])->name('tilawat.index');
        Route::put('/tilawat/bulk', [App\Http\Controllers\Admin\TilawaController::class, 'bulkUpdate'])->name('tilawat.bulk')->middleware('role:admin');
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
