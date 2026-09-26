<?php
// app/Providers/AppServiceProvider.php
namespace App\Providers;

use App\Models\User;
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
    }
}