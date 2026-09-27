<?php
// routes/web.php

use App\Http\Controllers\Admin\AssetAttachmentController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetTypeController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BorrowingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CheckinController;
use App\Http\Controllers\Admin\CheckoutController;
use App\Http\Controllers\Admin\IssueController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\MaintenanceTicketController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Borrower\BorrowingController as BorrowerBorrowingController;
use App\Http\Controllers\Borrower\ReservationController as BorrowerReservationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ShowcaseController;
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

    // ── Notifikasi in-app (PDF 4N) ──
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // ── Profil (bag. 8: Profile) ──
    Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::put('profile/identity', [ProfileController::class, 'updateIdentity'])->name('profile.identity');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences');

    // ── Area Borrower ──
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

    // ── QR & Scan (Phase 5) + Halaman Produk ──
    Route::get('scan', [ScanController::class, 'index'])->name('scan');
    Route::get('scan/{assetCode}', [ScanController::class, 'resolve'])->name('scan.resolve');
    Route::get('qr/{assetCode}', [ScanController::class, 'png'])->name('assets.qr');
    Route::get('p/{assetCode}', [ShowcaseController::class, 'show'])->name('showcase');

    // ── Area Admin ──
    Route::prefix('admin')->name('admin.')->group(function () {

        Route::middleware('permission:organization.manage')->resource('organizations', OrganizationController::class);
        Route::middleware('permission:user.manage')->resource('users', UserController::class);
        // Tab per peran di navbar: /admin/users/role/staff, /admin/users/role/borrower, …
        Route::get('users/role/{role}', [UserController::class, 'index'])
            ->whereIn('role', UserController::ROLE_TABS)
            ->name('users.byRole');

        Route::middleware('permission:category.manage')->resource('categories', CategoryController::class);
        Route::middleware('permission:location.manage')->resource('locations', LocationController::class);
        Route::middleware('permission:asset-type.manage')->resource('asset-types', AssetTypeController::class);

        Route::resource('assets', AssetController::class);
        Route::get('assets/{asset}/qr-label', [ScanController::class, 'label'])->name('assets.qr-label');
        Route::post('assets/{asset}/retire', [AssetController::class, 'retire'])->name('assets.retire');
        Route::post('assets/{asset}/attachments', [AssetAttachmentController::class, 'store'])->name('assets.attachments.store');
        Route::post('attachments/{attachment}/cover', [AssetAttachmentController::class, 'cover'])->name('attachments.cover');
        Route::delete('attachments/{attachment}', [AssetAttachmentController::class, 'destroy'])->name('attachments.destroy');

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

        // ── Phase 6: Issues (workflow penuh — create dulu agar tidak ditelan {issue}) ──
        Route::middleware('permission:issue.create')->group(function () {
            Route::get('issues/create', [IssueController::class, 'create'])->name('issues.create');
            Route::post('issues', [IssueController::class, 'store'])->name('issues.store');
        });
        Route::middleware('permission:issue.view')->group(function () {
            Route::get('issues', [IssueController::class, 'index'])->name('issues.index');
            Route::get('issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
        });
        Route::post('issues/{issue}/transition', [IssueController::class, 'transition'])
            ->middleware('permission:issue.resolve')->name('issues.transition');

        // ── Phase 6: Maintenance Tickets (create dulu agar tidak ditelan {ticket}) ──
        Route::middleware('permission:maintenance.create')->group(function () {
            Route::get('tickets/create', [MaintenanceTicketController::class, 'create'])->name('tickets.create');
            Route::post('tickets', [MaintenanceTicketController::class, 'store'])->name('tickets.store');
            Route::get('tickets/{ticket}/edit', [MaintenanceTicketController::class, 'edit'])->name('tickets.edit');
            Route::put('tickets/{ticket}', [MaintenanceTicketController::class, 'update'])->name('tickets.update');
        });
        Route::middleware('permission:maintenance.view')->group(function () {
            Route::get('tickets', [MaintenanceTicketController::class, 'index'])->name('tickets.index');
            Route::get('tickets/{ticket}', [MaintenanceTicketController::class, 'show'])->name('tickets.show');
        });
        Route::middleware('permission:maintenance.work')->group(function () {
            Route::post('tickets/{ticket}/transition', [MaintenanceTicketController::class, 'transition'])->name('tickets.transition');
            Route::post('tickets/{ticket}/logs', [MaintenanceTicketController::class, 'addLog'])->name('tickets.logs.store');
        });

        // ── Phase 7: Audit Trail (read-only, PDF 4L) ──
        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->middleware('permission:audit.view')->name('audit-logs.index');

        // ── Phase 8: Laporan & Export (PDF 4M) ──
        Route::middleware('permission:report.view')->group(function () {
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
        });
    });
});
