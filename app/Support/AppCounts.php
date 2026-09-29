<?php

// app/App/Support/AppCounts.php

namespace App\Support;

use App\Enums\IssueStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Issue;
use App\Models\MaintenanceTicket;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Penghitung counter global: SATU query per tabel, di-memoize per request.
 *
 * Kenapa kelas ini ada: dashboard dan layout navigasi membutuhkan angka yang
 * PERSIS SAMA (antrean reservasi, tiket, issue, status peminjaman). Tanpa
 * kelas ini tiap pihak menghitung sendiri — dashboard menjalankan 22 COUNT,
 * lalu Navigation menjalankan ~6 lagi untuk angka yang identik. Terhadap
 * DB produksi (Neon PostgreSQL, region Singapura) duplikasi itu mahal: 28
 * round-trip untuk data yang cukup dibaca dalam 5. Yang mahal adalah jumlah
 * round-trip-nya, bukan biaya server — semua query di sini 0,04–0,5 ms, tapi
 * tiap round-trip menambah waktu tempuh jaringan. Lihat PERF.md.
 *
 * DI-DAFTARKAN SEBAGAI SINGLETON di container, bukan pakai static. Itu
 * disengaja: container Laravel dibangun ulang tiap request (dan tiap test),
 * sehingga memo ikut hilang otomatis. Versi static pernah dipakai di sini dan
 * langsung menggigit — test kedua membaca angka dari database test pertama
 * karena rollback tidak menyentuh static.
 *
 * Dua lapis:
 *   1. Memo per request — menghindari query berulang dalam satu halaman
 *      (layout memanggil badges() untuk rail, topbar, notification, dll).
 *      Dibuang tiap request lewat RequestHandled (AppServiceProvider).
 *   2. Cache lintas request, TTL BADGE_TTL detik — Mengurangi jumlah
 *      round-trip per halaman yang identik antar request. Ini keputusan
 *      eksplisit pengguna: badge boleh basi ≤ TTL daripada membayar query
 *      untuk angka operasional yang jarang berubah. Store default `database`
 *      dipakai apa adanya: satu sumber kebenaran bersama antar instance
 *      Vercel (tidak ada inkonsistensi per-instance), dan saat hit biayanya
 *      hanya 1 query cache per halaman — masih hemat ~4 COUNT query.
 *
 * Angka TIDAK disaring per permission di sini. Penyaringan ada di pemanggil
 * (Navigation::badge()) supaya tabel cukup dihitung satu kali untuk semua peran.
 */
class AppCounts
{
    /**
     * Berapa detik angka boleh basi antar request.
     *
     * Rentang yang disetujui pengguna: 30–60. 45 = titik tengah: cukup
     * pendek untuk tidak menyesatkan keputusan operasional CUMA beberapa
     * puluh detik, cukup panjang untuk benar-benar memangkas round-trip.
     */
    private const BADGE_TTL = 45;

    /** @var array<string, mixed> */
    private array $memo = [];

    private function once(string $key, callable $resolve): mixed
    {
        return $this->memo[$key] ??= $resolve();
    }

    /**
     * Cache lintas request, TTL detik.
     *
     * Test memakai satu container untuk seluruh proses dan menjalankan
     * migrate:fresh — cache yang bertahan antar request akan menyimpan angka
     * dari test sebelumnya dan meracuni test berikutnya. Saat menguji
     * angka harus selalu segar, jadi lapisan ini dilewati total
     * (AppServiceProvider tetap membuang memo per request).
     */
    private function remember(string $key, callable $resolve): mixed
    {
        if (app()->runningUnitTests()) {
            return $resolve();
        }

        return Cache::remember($key, self::BADGE_TTL, $resolve);
    }

    /** Jumlah peminjaman per status — 1 query, menutup beberapa kebutuhan. */
    public function borrowingByStatus(): array
    {
        return $this->once('borrowingByStatus', fn () => $this->remember(
            'app-counts:borrowing-status',
            fn () => Borrowing::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($n) => (int) $n)
                ->all(),
        ));
    }

    /** Aset per status. Total aset ikut didapat dari penjumlahannya, jadi
     *  dashboard tidak perlu COUNT(*) tersendiri. */
    public function assetByStatus(): array
    {
        return $this->once('assetByStatus', fn () => $this->remember(
            'app-counts:asset-status',
            fn () => Asset::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($n) => (int) $n)
                ->all(),
        ));
    }

    /**
     * Aset per kondisi.
     *
     * `condition` itu reserved word di MySQL **dan** PostgreSQL, jadi tidak
     * boleh ditulis mentah di selectRaw. Backtick HANYA berlaku di MySQL — di
     * PostgreSQL backtick bukan quoting identifier, sehingga
     * `selectRaw('`condition`, count(*)')` gagal dengan
     * `SQLSTATE[42601]: syntax error at or near ","` dan dashboard ikut mati
     * karena angka kondisi ikut dihitung di halaman ini.
     *
     * Kolomnya lewat ->select() supaya grammar driver yang bertanggung jawab
     * atas quoting-nya: `"condition"` di PostgreSQL, `condition` di MySQL.
     */
    public function assetByCondition(): array
    {
        return $this->once('assetByCondition', fn () => $this->remember(
            'app-counts:asset-condition',
            fn () => Asset::query()
                ->select('condition')
                ->selectRaw('count(*) as total')
                ->groupBy('condition')
                ->pluck('total', 'condition')
                ->map(fn ($n) => (int) $n)
                ->all(),
        ));
    }

    public function reservationPending(): int
    {
        return $this->once('reservationPending', fn () => (int) $this->remember(
            'app-counts:reservation-pending',
            fn () => Reservation::query()
                ->where('status', ReservationStatus::Pending->value)
                ->count(),
        ));
    }

    public function issueOpen(): int
    {
        return $this->once('issueOpen', fn () => (int) $this->remember(
            'app-counts:issue-open',
            fn () => Issue::query()
                ->whereIn('status', [IssueStatus::Open->value, IssueStatus::Investigating->value])
                ->count(),
        ));
    }

    public function ticketOpen(): int
    {
        return $this->once('ticketOpen', fn () => (int) $this->remember(
            'app-counts:ticket-open',
            fn () => MaintenanceTicket::query()
                ->whereIn('status', [
                    MaintenanceStatus::Open->value,
                    MaintenanceStatus::Assigned->value,
                    MaintenanceStatus::InProgress->value,
                    MaintenanceStatus::WaitingParts->value,
                ])
                ->count(),
        ));
    }

    /** Notifikasi belum dibaca user — tidak bergantung role. */
    public function unread(User $user): int
    {
        return $this->once('unread:'.$user->id, fn () => (int) $this->remember(
            'app-counts:unread:'.$user->id,
            fn () => $user->unreadNotifications()->count(),
        ));
    }
}
