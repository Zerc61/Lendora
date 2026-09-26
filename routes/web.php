<?php
// routes/web.php

use App\Http\Controllers\Admin\AssetAttachmentController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetTypeController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\LocationController;
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

// ── Public: Login only (tanpa registrasi publik — PDF bag. 3) ──
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login.attempt');
});

// ── Authenticated + user aktif ──
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // ── Admin area ──
    Route::prefix('admin')->name('admin.')->group(function () {

        Route::middleware('permission:organization.manage')->group(function () {
            Route::resource('organizations', OrganizationController::class);
        });

        Route::middleware('permission:user.manage')->group(function () {
            Route::resource('users', UserController::class);
        });

        // ── Phase 3: Asset & Inventory ──
        Route::middleware('permission:category.manage')->resource('categories', CategoryController::class);
        Route::middleware('permission:location.manage')->resource('locations', LocationController::class);
        Route::middleware('permission:asset-type.manage')->resource('asset-types', AssetTypeController::class);

        // Aset: policy-driven (borrower punya asset.view → bisa browse)
        Route::resource('assets', AssetController::class);

        // Attachments
        Route::post('assets/{asset}/attachments', [AssetAttachmentController::class, 'store'])
            ->name('assets.attachments.store');
        Route::delete('attachments/{attachment}', [AssetAttachmentController::class, 'destroy'])
            ->name('attachments.destroy');

        // Phase 4: reservations, borrowings, checkout/checkin
    });
});