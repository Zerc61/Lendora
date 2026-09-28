<?php
// app/App/Support/AppCounts.php

namespace App\Support;

use App\Enums\BorrowingStatus;
use App\Enums\IssueStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Issue;
use App\Models\MaintenanceTicket;
use App\Models\Reservation;
use App\Models\User;

/**
 * Penghitung counter global: SATU query per tabel, di-memoize per request.
 *
 * Kenapa kelas ini ada: dashboard dan layout navigasi membutuhkan angka yang
 * PERSIS SAMA (antrean reservasi, tiket, issue, status peminjaman). Tanpa
 * kelas ini tiap pihak menghitung sendiri — dashboard menjalankan 22 COUNT,
 * lalu Navigation menjalankan ~6 lagi untuk angka yang identik. Terhadap
 * Aiven Singapore (~60ms per round-trip) duplikasi itu mahal: 28 round-trip
 * untuk data yang cukup dibaca dalam 5.
 *
 * DI-DAFTARKAN SEBAGAI SINGLETON di container, bukan pakai static. Itu
 * disengaja: container Laravel dibangun ulang tiap request (dan tiap test),
 * sehingga memo ikut hilang otomatis. Versi static pernah dipakai di sini dan
 * langsung menggigit — test kedua membaca angka dari database test pertama
 * karena rollback tidak menyentuh static.
 *
 * Angka adalah "snapshot per request", bukan cache lintas request. Badge
 * bersifat informatif dan hanya bertahan satu halaman, jadi staleness antar
 * request tidak berarti — sedangkan cache lintas request butuh invalidation
 * yang rawan menampilkan angka antrean yang salah untuk keputusan operasional.
 *
 * Angka TIDAK disaring per permission di sini. Penyaringan ada di pemanggil
 * (Navigation::badge()) supaya tabel cukup dihitung satu kali untuk semua peran.
 */
class AppCounts
{
    /** @var array<string, mixed> */
    private array $memo = [];

    private function once(string $key, callable $resolve): mixed
    {
        return $this->memo[$key] ??= $resolve();
    }

    /** Jumlah peminjaman per status — 1 query, menutup beberapa kebutuhan. */
    public function borrowingByStatus(): array
    {
        return $this->once('borrowingByStatus', fn () => Borrowing::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($n) => (int) $n)
            ->all());
    }

    /** Aset per status. Total aset ikut didapat dari penjumlahannya, jadi
     *  dashboard tidak perlu COUNT(*) tersendiri. */
    public function assetByStatus(): array
    {
        return $this->once('assetByStatus', fn () => Asset::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($n) => (int) $n)
            ->all());
    }

    /** Aset per kondisi. `condition` itu reserved word MySQL → WAJIB backtick. */
    public function assetByCondition(): array
    {
        return $this->once('assetByCondition', fn () => Asset::query()
            ->selectRaw('`condition`, count(*) as total')
            ->groupBy('condition')
            ->pluck('total', 'condition')
            ->map(fn ($n) => (int) $n)
            ->all());
    }

    public function reservationPending(): int
    {
        return $this->once('reservationPending', fn () => (int) Reservation::query()
            ->where('status', ReservationStatus::Pending->value)
            ->count());
    }

    public function issueOpen(): int
    {
        return $this->once('issueOpen', fn () => (int) Issue::query()
            ->whereIn('status', [IssueStatus::Open->value, IssueStatus::Investigating->value])
            ->count());
    }

    public function ticketOpen(): int
    {
        return $this->once('ticketOpen', fn () => (int) MaintenanceTicket::query()
            ->whereIn('status', [
                MaintenanceStatus::Open->value,
                MaintenanceStatus::Assigned->value,
                MaintenanceStatus::InProgress->value,
                MaintenanceStatus::WaitingParts->value,
            ])
            ->count());
    }

    /** Notifikasi belum dibaca user — tidak bergantung role. */
    public function unread(User $user): int
    {
        return $this->once('unread:'.$user->id, fn () => (int) $user->unreadNotifications()->count());
    }
}
