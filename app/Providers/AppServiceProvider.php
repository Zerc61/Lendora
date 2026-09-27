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
        // Tiga sumber, berurutan dari paling tegas:
        //
        //   1. APP_URL diawali https://  → konfigurasi eksplisit, andalkan ini.
        //   2. X-Forwarded-Proto: https  → sinyal yang selalu dikirim Vercel /
        //      Nginx / Cloudflare untuk request yang aslinanya TLS. Dipakai
        //      supaya halaman tidak rusak hanya karena satu env var lupa diisi.
        //   3. Selain itu tidak dipaksa, agar pengembangan lokal lewat
        //      http://localhost tetap normal.
        //
        // Kenapa (2) aman: header ini HANYA menaikkan http → https, tidak
        // pernah menurunkan. Header yang dipalsukan hanya bisa membuat
        // halaman rusak (browser mengarang https ke server yang http) — tidak
        // mungkin membuat kredensial terkirim tanpa enkripsi.
        //
        // Catatan: JANGAN set app.asset_url di sini. forceScheme sudah cukup
        // untuk `asset()` — UrlGenerator::formatRoot() menukar skema pada
        // $request->root() sambil mempertahankan host-nya, jadi preview
        // deployment tetap memakai host-nya sendiri. Memaksa
        // app.asset_url = APP_URL justru membuat aset preview menunjuk ke
        // domain produksi.
        $appUrl = (string) config('app.url');
        $forwarded = strtolower((string) $this->app->make('request')->header('X-Forwarded-Proto'));

        $isHttps = strtolower((string) parse_url($appUrl, PHP_URL_SCHEME)) === 'https'
            || $forwarded === 'https';

        if ($isHttps) {
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
