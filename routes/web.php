<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\QariController;
use App\Http\Controllers\Admin\TilawaController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Admin panel remains Blade — uses session-based auth
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin|creator'])->group(function () {

    Route::get('/', DashboardController::class)->name('dashboard');

    // Qaris CRUD
    Route::resource('qaris', QariController::class);

    // Tilawat CRUD
    Route::resource('tilawat', TilawaController::class);

    // Users (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
    });
});

// Admin auth (login page for the admin panel)
require __DIR__ . '/auth.php';
