<?php
// routes/web.php

use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// ── Public: Login only (tanpa registrasi publik, sesuai PDF bag. 3) ──
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1') // rate limiting (PDF bag. 12)
        ->name('login.attempt');
});

// ── Authenticated + harus user aktif ──
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // ── Area Admin ──
    Route::prefix('admin')->name('admin.')->group(function () {

        // Super Admin only (via policy; admin punya policy-nya sendiri)
        Route::middleware('permission:organization.manage')->group(function () {
            Route::resource('organizations', OrganizationController::class);
        });

        Route::middleware('permission:user.manage')->group(function () {
            Route::resource('users', UserController::class);
        });

        // Phase 3: assets, categories, locations, asset types
        // Phase 4: reservations, borrowings, checkout/checkin
    });
});