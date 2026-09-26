<?php
// routes/web.php

use App\Http\Controllers\Admin\AssetAttachmentController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetTypeController;
use App\Http\Controllers\Admin\BorrowingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CheckinController;
use App\Http\Controllers\Admin\CheckoutController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Borrower\BorrowingController as BorrowerBorrowingController;
use App\Http\Controllers\Borrower\ReservationController as BorrowerReservationController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login.attempt');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // ── Area Borrower: reservasi & peminjaman milik sendiri ──
    Route::prefix('my')->name('my.')->group(function () {
        Route::middleware('permission:reservation.create')->group(function () {
            Route::get('reservations', [BorrowerReservationController::class, 'index'])->name('reservations.index');
            Route::get('reservations/create', [BorrowerReservationController::class, 'create'])->name('reservations.create');
            Route::post('reservations', [BorrowerReservationController::class, 'store'])->name('reservations.store');
            Route::get('reservations/{reservation}', [BorrowerReservationController::class, 'show'])->name('reservations.show');
            Route::post('reservations/{reservation}/cancel', [BorrowerReservationController::class, 'cancel'])->name('reservations.cancel');
        });

        Route::get('borrowings', [BorrowerBorrowingController::class, 'index'])->name('borrowings.index');
        Route::get('borrowings/{borrowing}', [BorrowerBorrowingController::class, 'show'])->name('borrowings.show');
    });

    // ── Area Admin ──
    Route::prefix('admin')->name('admin.')->group(function () {

        Route::middleware('permission:organization.manage')->resource('organizations', OrganizationController::class);
        Route::middleware('permission:user.manage')->resource('users', UserController::class);

        Route::middleware('permission:category.manage')->resource('categories', CategoryController::class);
        Route::middleware('permission:location.manage')->resource('locations', LocationController::class);
        Route::middleware('permission:asset-type.manage')->resource('asset-types', AssetTypeController::class);

        Route::resource('assets', AssetController::class);
        Route::post('assets/{asset}/attachments', [AssetAttachmentController::class, 'store'])->name('assets.attachments.store');
        Route::delete('attachments/{attachment}', [AssetAttachmentController::class, 'destroy'])->name('attachments.destroy');

        // ── Phase 4: Reservasi, Peminjaman, Checkout, Check-in ──
        Route::get('reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
        Route::get('reservations/{reservation}', [AdminReservationController::class, 'show'])->name('reservations.show');
        Route::post('reservations/{reservation}/approve', [AdminReservationController::class, 'approve'])
            ->middleware('permission:reservation.approve')->name('reservations.approve');
        Route::post('reservations/{reservation}/reject', [AdminReservationController::class, 'reject'])
            ->middleware('permission:reservation.approve')->name('reservations.reject');

        Route::middleware('permission:borrowing.view')->group(function () {
            Route::get('borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
            Route::get('borrowings/{borrowing}', [BorrowingController::class, 'show'])->name('borrowings.show');
        });

        Route::middleware('permission:checkout.perform')->group(function () {
            Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout.index');
            Route::get('checkout/{borrowing}', [CheckoutController::class, 'show'])->name('checkout.show');
            Route::post('checkout/{borrowing}', [CheckoutController::class, 'store'])->name('checkout.store');
        });

        Route::middleware('permission:checkin.perform')->group(function () {
            Route::get('checkin', [CheckinController::class, 'index'])->name('checkin.index');
            Route::get('checkin/{borrowing}', [CheckinController::class, 'show'])->name('checkin.show');
            Route::post('checkin/{borrowing}', [CheckinController::class, 'store'])->name('checkin.store');
        });

        // Phase 5: QR & Handover
    });
});
