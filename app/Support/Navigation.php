<?php
// app/Support/Navigation.php
namespace App\Support;

use App\Enums\BorrowingStatus;
use App\Enums\MaintenanceStatus;
use App\Models\Issue;
use App\Models\MaintenanceTicket;
use App\Models\User;

/**
 * Definisi navigasi per role (PDF bag. 3 — role & permission).
 *
 * Setiap role punya shell/layout berbeda:
 *   admin      → konsol penuh (sidebar grup, insight, master data)
 *   staff      → fokus antrean kerja (serah-terima, antrean reservasi, issue)
 *   technician → fokus pekerjaan (tiket yang dikerjakan, aset untuk context)
 *   borrower   → mobile-first (bottom nav, alur reservasi/peminjaman)
 */
final class Navigation
{
    /**
     * Sidebar / daftar menu lengkap.
     *
     * @return array<int, array{label: string, items: array<int, array<string, mixed>>}>
     */
    public static function for(User $user): array
    {
        return match ($user->primaryRole()) {
            'admin' => self::admin($user),
            'staff' => self::staff($user),
            'technician' => self::technician($user),
            default => self::borrower($user),
        };
    }

    /**
     * Jumlah tiket per status untuk workbar teknisi.
     *
     * @return array<string, int>
     */
    public static function ticketStatusCounts(User $user): array
    {
        if (! $user->can('maintenance.view')) {
            return [];
        }

        $counts = MaintenanceTicket::query()
            ->where(function ($query) use ($user) {
                $query->where('technician_id', $user->id)->orWhereNull('technician_id');
            })
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($n) => (int) $n)
            ->all();

        $out = [];
        foreach (MaintenanceStatus::cases() as $status) {
            $out[$status->value] = $counts[$status->value] ?? 0;
        }

        return $out;
    }

    /** Item ringkas untuk bottom nav (mobile). */
    public static function primary(User $user): array
    {
        return match ($user->primaryRole()) {
            'admin', 'staff' => array_values(array_filter([
                self::i('dashboard', 'Beranda', 'grid', 'dashboard'),
                self::i('admin.checkout.index', 'Check-out', 'out', 'admin.checkout.*', self::checkoutQueue($user), 'brand'),
                self::i('admin.checkin.index', 'Check-in', 'in', 'admin.checkin.*', self::checkinQueue($user), 'brand'),
                self::i('notifications.index', 'Notifikasi', 'bell', 'notifications.*', self::unread($user), 'alert'),
            ])),
            'technician' => array_values(array_filter([
                self::i('dashboard', 'Beranda', 'grid', 'dashboard'),
                self::i('admin.tickets.index', 'Tiket', 'wrench', 'admin.tickets.*', self::ticketQueue($user), 'brand'),
                self::i('admin.assets.index', 'Aset', 'box', 'admin.assets.*'),
                self::i('notifications.index', 'Notifikasi', 'bell', 'notifications.*', self::unread($user), 'alert'),
            ])),
            default => array_values(array_filter([
                self::i('dashboard', 'Beranda', 'grid', 'dashboard'),
                self::i('my.reservations.index', 'Reservasi', 'calendar', 'my.reservations.*'),
                self::i('my.borrowings.index', 'Pinjaman', 'bag', 'my.borrowings.*'),
                self::i('notifications.index', 'Notifikasi', 'bell', 'notifications.*', self::unread($user), 'alert'),
                self::i('profile.index', 'Profil', 'user', 'profile.*'),
            ])),
        };
    }

    // ── Admin: konsol lengkap ────────────────────────────────────────────
    private static function admin(User $user): array
    {
        return [
            self::g('Ringkasan', [
                self::i('dashboard', 'Dashboard', 'grid', 'dashboard'),
                self::i('notifications.index', 'Notifikasi', 'bell', 'notifications.*', self::unread($user), 'alert'),
            ]),
            self::g('Transaksi', [
                self::i('admin.reservations.index', 'Reservasi', 'calendar', 'admin.reservations.*', self::reservationQueue($user), 'warn', 'reservation.approve'),
                self::i('admin.borrowings.index', 'Peminjaman', 'bag', 'admin.borrowings.*', permission: 'borrowing.view'),
                self::i('admin.checkout.index', 'Check-out', 'out', 'admin.checkout.*', self::checkoutQueue($user), 'brand', 'checkout.perform'),
                self::i('admin.checkin.index', 'Check-in', 'in', 'admin.checkin.*', self::checkinQueue($user), 'brand', 'checkin.perform'),
            ]),
            self::g('Inventori', [
                self::i('admin.assets.index', 'Aset', 'box', 'admin.assets.*', permission: 'asset.view'),
                self::i('scan', 'Scan QR', 'scan', 'scan*', permission: 'asset.view'),
                self::i('admin.issues.index', 'Issue', 'alert', 'admin.issues.*', self::issueQueue($user), 'alert', 'issue.view'),
                self::i('admin.tickets.index', 'Maintenance', 'wrench', 'admin.tickets.*', self::ticketQueue($user), 'warn', 'maintenance.view'),
            ]),
            self::g('Insight', [
                self::i('admin.reports.index', 'Laporan', 'chart', 'admin.reports.*', permission: 'report.view'),
                self::i('admin.audit-logs.index', 'Audit Log', 'scroll', 'admin.audit-logs.*', permission: 'audit.view'),
            ]),
            self::g('Master Data', [
                self::i('admin.asset-types.index', 'Tipe Aset', 'layers', 'admin.asset-types.*', permission: 'asset-type.manage'),
                self::i('admin.categories.index', 'Kategori', 'tag', 'admin.categories.*', permission: 'category.manage'),
                self::i('admin.locations.index', 'Lokasi', 'pin', 'admin.locations.*', permission: 'location.manage'),
                self::i('admin.users.index', 'Pengguna', 'users', 'admin.users.*', permission: 'user.manage'),
                self::i('admin.organizations.index', 'Organisasi', 'building', 'admin.organizations.*', permission: 'organization.manage'),
            ]),
        ];
    }

    // ── Staff: fokus antrean kerja ────────────────────────────────────────
    private static function staff(User $user): array
    {
        return [
            self::g('Antrean Kerja', [
                self::i('admin.checkout.index', 'Check-out', 'out', 'admin.checkout.*', self::checkoutQueue($user), 'brand', 'checkout.perform'),
                self::i('admin.checkin.index', 'Check-in', 'in', 'admin.checkin.*', self::checkinQueue($user), 'brand', 'checkin.perform'),
                self::i('admin.reservations.index', 'Reservasi Masuk', 'calendar', 'admin.reservations.*', self::reservationQueue($user), 'warn', 'reservation.approve'),
                self::i('admin.issues.index', 'Laporan Kerusakan', 'alert', 'admin.issues.*', self::issueQueue($user), 'alert', 'issue.view'),
            ]),
            self::g('Inventori', [
                self::i('admin.assets.index', 'Aset', 'box', 'admin.assets.*', permission: 'asset.view'),
                self::i('scan', 'Scan QR', 'scan', 'scan*', permission: 'asset.view'),
                self::i('admin.tickets.index', 'Maintenance', 'wrench', 'admin.tickets.*', self::ticketQueue($user), 'warn', 'maintenance.view'),
                self::i('admin.borrowings.index', 'Peminjaman', 'bag', 'admin.borrowings.*', permission: 'borrowing.view'),
            ]),
            self::g('Lainnya', [
                self::i('dashboard', 'Dashboard', 'grid', 'dashboard'),
                self::i('notifications.index', 'Notifikasi', 'bell', 'notifications.*', self::unread($user), 'alert'),
            ]),
        ];
    }

    // ── Teknisi: fokus pekerjaan ─────────────────────────────────────────
    private static function technician(User $user): array
    {
        $mine = MaintenanceTicket::where('technician_id', $user->id)
            ->whereIn('status', [MaintenanceStatus::Assigned->value, MaintenanceStatus::InProgress->value, MaintenanceStatus::WaitingParts->value])
            ->count();

        return [
            self::g('Pekerjaan Saya', [
                self::i('admin.tickets.index', 'Tiket Saya', 'wrench', 'admin.tickets.*', $mine ?: null, 'brand', 'maintenance.view'),
                self::i('admin.assets.index', 'Aset', 'box', 'admin.assets.*', permission: 'asset.view'),
                self::i('admin.issues.index', 'Issue Aset', 'alert', 'admin.issues.*', self::issueQueue($user), 'alert', 'issue.view'),
            ]),
            self::g('Lainnya', [
                self::i('dashboard', 'Beranda', 'grid', 'dashboard'),
                self::i('notifications.index', 'Notifikasi', 'bell', 'notifications.*', self::unread($user), 'alert'),
            ]),
        ];
    }

    // ── Peminjam: mobile-first ───────────────────────────────────────────
    private static function borrower(User $user): array
    {
        return [
            self::g('Menu', [
                self::i('dashboard', 'Beranda', 'grid', 'dashboard'),
                self::i('my.reservations.create', 'Ajukan Reservasi', 'plus', 'my.reservations.create', permission: 'reservation.create'),
                self::i('my.reservations.index', 'Reservasi Saya', 'calendar', 'my.reservations.*', permission: 'reservation.view'),
                self::i('my.borrowings.index', 'Peminjaman Saya', 'bag', 'my.borrowings.*'),
                self::i('admin.assets.index', 'Katalog Aset', 'box', 'admin.assets.*', permission: 'asset.view'),
                self::i('scan', 'Scan QR', 'scan', 'scan*', permission: 'asset.view'),
                self::i('notifications.index', 'Notifikasi', 'bell', 'notifications.*', self::unread($user), 'alert'),
                self::i('profile.index', 'Profil', 'user', 'profile.*'),
            ]),
        ];
    }

    // ── Item & group ─────────────────────────────────────────────────────
    private static function i(
        string $route,
        string $label,
        string $icon,
        string $match,
        ?int $count = null,
        ?string $tone = null,
        ?string $permission = null,
    ): array {
        return [
            'route' => $route,
            'label' => $label,
            'icon' => $icon,
            'match' => $match,
            'count' => $count,
            'tone' => $tone,
            'permission' => $permission,
            'url' => route($route),
        ];
    }

    /** Buang item yang tidak diizinkan & grup yang jadi kosong. */
    private static function g(string $label, array $items): array
    {
        $items = array_values(array_filter(
            $items,
            fn (array $i) => $i['permission'] === null || auth()->user()?->can($i['permission'])
        ));

        return ['label' => $label, 'items' => $items];
    }

    // ── Badge counter ────────────────────────────────────────────────────
    //
    // SEMUA badge dihitung sekali per request lewat badges(), bukan sekali per
    // item menu. Sebelumnya setiap helper di bawah menjalankan COUNT sendiri,
    // dan karena argumen dievaluasi SEBELUM self::i() menyaring berdasarkan
    // permission, satu render admin sudah menjalankan 6 COUNT — lalu
    // bottomnav, notification-btn, dan user-menu (dimuat 2x) memanggilnya
    // lagi. Total ~13 query per halaman, sebagian besar identik.
    //
    // Sekarang: 5 query (dibatasi satu per tabel, lewat GROUP BY status),
    // di-memoize sehingga panggilan berikutnya pada halaman yang sama gratis.
    //
    // PENTING: angka badge disimpan apa adanya, sedangkan penyaringan
    // permission tetap terjadi di pemanggil (badge()). Dengan begitu tabel
    // cukup dihitung SEKALI untuk semua peran, dan user tanpa izin tetap
    // melihat nol — bukan angka antrean yang tidak boleh dia ketahui.

    public static function badges(User $user): array
    {
        // Di-resolve lewat container, bukan static. Instance-nya singleton,
        // jadi memo-nya hidup selama satu request lalu ikut hilang — inilah
        // yang membuatnya aman terhadap test, karena rollback database tidak
        // menyentuh static. Navigation sendiri tidak menyimpan cache.
        $counts = app(AppCounts::class);
        $borrowing = $counts->borrowingByStatus();

        return [
            'unread'      => $counts->unread($user),
            'reservation' => $counts->reservationPending(),
            'checkout'    => $borrowing[BorrowingStatus::Approved->value] ?? 0,
            'checkin'     => ($borrowing[BorrowingStatus::Borrowed->value] ?? 0)
                + ($borrowing[BorrowingStatus::Overdue->value] ?? 0),
            'ticket'      => $counts->ticketOpen(),
            'issue'       => $counts->issueOpen(),
        ];
    }

    private static function badge(User $user, string $key, ?string $permission = null): ?int
    {
        if ($permission !== null && ! $user->can($permission)) {
            return null;
        }

        $n = self::badges($user)[$key] ?? 0;

        return $n ?: null;
    }

    private static function unread(User $user): ?int
    {
        return self::badge($user, 'unread');
    }

    private static function reservationQueue(User $user): ?int
    {
        return self::badge($user, 'reservation', 'reservation.approve');
    }

    private static function checkoutQueue(User $user): ?int
    {
        return self::badge($user, 'checkout', 'checkout.perform');
    }

    private static function checkinQueue(User $user): ?int
    {
        return self::badge($user, 'checkin', 'checkin.perform');
    }

    private static function ticketQueue(User $user): ?int
    {
        return self::badge($user, 'ticket', 'maintenance.view');
    }

    private static function issueQueue(User $user): ?int
    {
        return self::badge($user, 'issue', 'issue.view');
    }
}
