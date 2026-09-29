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
use App\Support\AppCounts;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton, bukan static — layout/sidebar memanggil Navigation::badges()
        // berkali-kali dalam satu request dan memo AppCounts menahan hasilnya.
        // Tapi ingat: asal "satu instance hidup satu request" HANYA berlaku di
        // web (PHP menghidupkan container sekali per HTTP request). Framework
        // testing memakai satu container untuk SELURUH proses dan untuk banyak
        // request di dalam satu test, jadi memo harus di-flush per request —
        // lihat RequestHandled di boot().
        $this->app->singleton(AppCounts::class);
    }

    public function boot(): void
    {
        // Pagination & asset kustom (tanpa build step)
        Paginator::defaultView('pagination::lendora');
        Paginator::defaultSimpleView('pagination::lendora');

        // Super Admin lolos semua authorization (PDF bag. 3)
        //
        // hasRole() dipanggil sekali per user per request lalu di-memoize.
        // Tanpa ini callback ini berjalan untuk SETIAP can() — layout admin
        // saja memicu puluhan — dan tiap pem_callan memicu relasi roles.
        Gate::before(function ($user, string $ability) {
            static $superAdmin = null;

            if ($superAdmin === null) {
                $superAdmin = $user instanceof User && $user->hasRole('super-admin');
            }

            return $superAdmin ? true : null;
        });

        // Memo badge navigasi ikut dibuang saat request selesai.
        //
        // Mengapa perlu: AppCounts di-bind sebagai singleton container, dan
        // asumsi "satu container = satu request" benar untuk web biasa tapi
        // TIDAK untuk framework testing — Laravel memakai satu container untuk
        // seluruh proses PHPUnit dan untuk banyak request dalam satu test.
        // Tanpa flush ini, test seperti "persetujuan menaikkan angka siap
        // check-out" gagal: request pertama menghitung angka lalu
        // menyimpannya di memo, request kedua (setelah data berubah) kembali
        // membaca angka basi. RequestHandled dikirim oleh HttpKernel untuk
        // SETIAP request — termasuk yang berakhir exception — sehingga ini
        // membuat perilaku test sama dengan produksi: memo hidup tepat satu
        // request.
        $this->app['events']->listen(
            RequestHandled::class,
            fn () => $this->app->forgetInstance(AppCounts::class),
        );

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
