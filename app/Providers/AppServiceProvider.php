<?php
// app/Providers/AppServiceProvider.php
namespace App\Providers;

use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Issue;
use App\Models\MaintenanceTicket;
use App\Models\Reservation;
use App\Models\User;
use App\Observers\AuditableObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Super Admin lolos semua authorization (PDF bag. 3)
        Gate::before(function ($user, string $ability) {
            return $user instanceof User && $user->hasRole('super-admin') ? true : null;
        });

        // Audit trail otomatis (PDF 4L)
        Asset::observe(AuditableObserver::class);
        Reservation::observe(AuditableObserver::class);
        Borrowing::observe(AuditableObserver::class);
        MaintenanceTicket::observe(AuditableObserver::class);
        Issue::observe(AuditableObserver::class);
    }
}
