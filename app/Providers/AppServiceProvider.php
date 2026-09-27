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
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Pagination & asset kustom (tanpa build step)
        Paginator::defaultView('pagination::lendora');
        Paginator::defaultSimpleView('pagination::lendora');

        // Super Admin lolos semua authorization (PDF bag. 3)
        Gate::before(function ($user, string $ability) {
            return $user instanceof User && $user->hasRole('super-admin') ? true : null;
        });

        // PDF 12: HTTPS produksi — URL yang dihasilkan (link email, asset(),
        // signed URL) harus https kalau app di balik TLS-terminating proxy.
        //
        // Syaratnya BUKAN `environment('production')`. Di Vercel, APP_ENV bisa
        // tidak sampai ke container, dan ketika itu seluruh URL —
        // `asset()` untuk CSS/JS dan `route()` untuk <form action> — jatuh ke
        // http:// sementara halamannya disajikan https://. Akibatnya:
        //   1. CSS/JS jadi mixed content → diblokir browser TANPA pesan, jadi
        //      halaman tampil tanpa gaya sama sekali;
        //   2. <form action="http://…"> memicu peringatan "formulir tidak aman".
        //
        // Acuannya scheme dari APP_URL, karena itu satu-satunya nilai yang
        // pasti benar dan wajib diisi di produksi.
        //
        // Hanya `forceScheme` — JANGAN set app.asset_url di sini. forceScheme
        // sudah cukup untuk `asset()`: UrlGenerator::formatRoot() menukar
        // skema pada $request->root() sambil mempertahankan host-nya, jadi
        // preview deployment tetap memakai host-nya sendiri. Memaksa
        // app.asset_url = APP_URL justru membuat aset preview menunjuk ke
        // domain produksi.
        $appUrl = (string) config('app.url');

        if (strtolower((string) parse_url($appUrl, PHP_URL_SCHEME)) === 'https') {
            URL::forceScheme('https');
        }

        // Audit trail otomatis (PDF 4L)
        Asset::observe(AuditableObserver::class);
        Reservation::observe(AuditableObserver::class);
        Borrowing::observe(AuditableObserver::class);
        MaintenanceTicket::observe(AuditableObserver::class);
        Issue::observe(AuditableObserver::class);
    }
}
