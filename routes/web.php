<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\QariController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CreatorApplicationController;
use App\Http\Controllers\Creator\QueueController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\VideoController as AdminVideoController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/qaris/{qari}', [QariController::class, 'show'])->name('qaris.show');
Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show')->whereNumber('video');

Route::middleware('auth')->group(function () {
    Route::get('/library', [LibraryController::class, 'index'])->name('library');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/creator/apply', [CreatorApplicationController::class, 'create'])->name('creator.apply.create');
    Route::post('/creator/apply', [CreatorApplicationController::class, 'store'])->name('creator.apply.store');
    Route::get('/videos/create', [VideoController::class, 'create'])->name('videos.create');
    Route::post('/videos', [VideoController::class, 'store'])->name('videos.store');
});

Route::middleware(['auth', 'role:creator,admin'])->group(function () {
    Route::get('/creator/queue', [QueueController::class, 'index'])->name('creator.queue');
    Route::get('/qaris/create', [QariController::class, 'create'])->name('qaris.create');
    Route::post('/qaris', [QariController::class, 'store'])->name('qaris.store');
    Route::get('/qaris/{qari}/edit', [QariController::class, 'edit'])->name('qaris.edit');
    Route::put('/qaris/{qari}', [QariController::class, 'update'])->name('qaris.update');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications');
    Route::post('/applications/{application}/approve', [ApplicationController::class, 'approve'])->name('applications.approve');
    Route::post('/applications/{application}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');
    Route::delete('/videos/{video}', [AdminVideoController::class, 'destroy'])->name('videos.destroy');
});
