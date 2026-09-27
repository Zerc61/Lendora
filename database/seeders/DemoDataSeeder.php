<?php
// database/seeders/DemoDataSeeder.php
// Data transaksi contoh untuk QA tampilan (reservasi, peminjaman, issue, maintenance).
// Jalankan: php artisan db:seed --class=DemoDataSeeder
namespace Database\Seeders;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\BorrowingStatus;
use App\Enums\InspectionStage;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\Issue;
use App\Models\MaintenanceTicket;
use App\Models\Organization;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $borrowers = User::role('borrower')->get();
        $staff = User::role('staff')->first() ?? User::first();
        $admin = User::role('admin')->first() ?? User::first();
        $tech = User::role('technician')->first() ?? User::first();
        $assets = Asset::query()->orderBy('id')->get()->values();
        $org = Organization::first();

        if ($borrowers->isEmpty() || $assets->isEmpty()) {
            $this->command?->warn('Pengguna peminjam atau aset belum ada — jalankan seeder utama dulu.');

            return;
        }

        $this->purge(); // idempoten: hapus data demo sebelumnya

        // ── 1. Reservasi di berbagai status ────────────────────────────────
        $reservationPlans = [
            [ReservationStatus::Pending, now()->addDays(2), now()->addDays(4)],
            [ReservationStatus::Pending, now()->addDays(5), now()->addDays(6)],
            [ReservationStatus::Approved, now()->subDay(), now()->addDays(3)],
            [ReservationStatus::Fulfilled, now()->subDays(12), now()->subDays(9)],
            [ReservationStatus::Rejected, now()->subDays(6), now()->subDays(4)],
        ];

        foreach ($reservationPlans as $i => [$status, $start, $end]) {
            $reservation = Reservation::create([
                'organization_id' => $org?->id,
                'user_id'         => $borrowers[$i % $borrowers->count()]->id,
                'code'            => 'RSV-DEMO-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'start_at'        => $start,
                'end_at'          => $end,
                'status'          => $status->value,
                'purpose'         => $status === ReservationStatus::Rejected
                    ? 'Kebutuhan pessoal di luar kebijakan peminjaman'
                    : ['Pelatihan internal', 'Dokumentasi kegiatan', 'Survey lapangan', 'Uji coba perangkat', 'Keperluan presentasi'][$i % 5],
                'approved_by'     => in_array($status, [ReservationStatus::Approved, ReservationStatus::Fulfilled, ReservationStatus::Rejected], true) ? $admin?->id : null,
                'approved_at'     => in_array($status, [ReservationStatus::Approved, ReservationStatus::Fulfilled, ReservationStatus::Rejected], true) ? now()->subDays(2) : null,
                'rejection_reason' => $status === ReservationStatus::Rejected ? 'Waktu tersebut sudah penuh dengan peminjaman lain' : null,
            ]);

            foreach (range(0, min(2, $assets->count() - 1)) as $n) {
                $asset = $assets[($i + $n) % $assets->count()];
                if ($asset->status === AssetStatus::Retired) {
                    continue;
                }
                ReservationItem::create(['reservation_id' => $reservation->id, 'asset_id' => $asset->id]);
            }
        }

        // ── 2. Peminjaman di berbagai status ───────────────────────────────
        $borrowingPlans = [
            [BorrowingStatus::Approved, now()->addDays(3), now()->addDays(1), null],
            [BorrowingStatus::Borrowed, now()->subDays(2), now()->addDays(5), now()->subDays(2)],
            [BorrowingStatus::Borrowed, now()->subDays(9), now()->subDays(2), now()->subDays(9)],
            [BorrowingStatus::Overdue, now()->subDays(12), now()->subDays(4), now()->subDays(12)],
            [BorrowingStatus::Returned, now()->subDays(20), now()->subDays(16), now()->subDays(20)],
            [BorrowingStatus::Pending, now()->addDays(6), now()->addDays(8), null],
        ];

        foreach ($borrowingPlans as $i => [$status, $out, $due, $returned]) {
            $borrowing = Borrowing::create([
                'organization_id' => $org?->id,
                'borrower_id'     => $borrowers[$i % $borrowers->count()]->id,
                'approved_by'     => $admin?->id,
                'code'            => 'BRW-DEMO-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'status'          => $status->value,
                'purpose'         => ['Pelatihan karyawan', 'Dokumentasi', 'Audit internal', 'Presentasi klien', 'Stok opname', 'Uji fungsi'][$i % 6],
                'checked_out_at'  => $out,
                'due_at'          => $due,
                'returned_at'     => $returned,
                'checked_out_by'  => $out ? $staff?->id : null,
                'checked_in_by'   => $returned ? $staff?->id : null,
                'checkin_notes'   => $returned ? 'Kondisi baik, lengkap.' : null,
            ]);

            $count = min(2, $assets->count());
            for ($n = 0; $n < $count; $n++) {
                $asset = $assets[($i * 2 + $n) % $assets->count()];
                BorrowingItem::create([
                    'borrowing_id'  => $borrowing->id,
                    'asset_id'      => $asset->id,
                    'quantity'      => 1,
                    'condition_out' => $asset->condition,
                    'condition_in'  => $returned ? $asset->condition : null,
                    'notes_out'     => 'Checked dalam kondisi baik.',
                ]);
            }

            // Sinkronkan status aset mengikuti peminjaman
            if ($status === BorrowingStatus::Borrowed || $status === BorrowingStatus::Overdue) {
                $assets[($i * 2) % $assets->count()]->update(['status' => AssetStatus::Borrowed]);
            }
        }

        // ── 3. Issue (laporan kerusakan) ──────────────────────────────────
        $issuePlans = [
            [IssueType::Damage, IssueSeverity::High, IssueStatus::Open, 'Layar berkedip dan sewaktu-waktu mati saat digunakan.'],
            [IssueType::Other, IssueSeverity::Medium, IssueStatus::Investigating, 'Aplikasi sering crash saat menyimpan dokumen.'],
            [IssueType::Loss, IssueSeverity::Low, IssueStatus::Resolved, 'Kabel charger putus, sudah diganti.'],
            [IssueType::Damage, IssueSeverity::Critical, IssueStatus::Open, 'Casing retak parah setelah jatuh dari meja.'],
        ];

        foreach ($issuePlans as $i => [$type, $severity, $status, $description]) {
            Issue::create([
                'organization_id' => $org?->id,
                'asset_id'        => $assets[$i % $assets->count()]->id,
                'reported_by'     => $borrowers[$i % $borrowers->count()]->id,
                'code'            => 'ISS-DEMO-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'type'            => $type->value,
                'severity'        => $severity->value,
                'status'          => $status->value,
                'description'     => $description,
                'resolution'      => $status === IssueStatus::Resolved ? 'Ganti komponen dari stok cadangan.' : null,
                'resolved_by'     => $status === IssueStatus::Resolved ? $tech?->id : null,
                'resolved_at'     => $status === IssueStatus::Resolved ? now()->subDays(1) : null,
            ]);
        }

        // ── 4. Tiket maintenance di berbagai status ───────────────────────
        $ticketPlans = [
            [MaintenanceType::Corrective, MaintenancePriority::Critical, MaintenanceStatus::Open, 'Power supply tidak menyala sama sekali.'],
            [MaintenanceType::Preventive, MaintenancePriority::Medium, MaintenanceStatus::Assigned, 'Pengecekan berkala dan pembersihan unit.'],
            [MaintenanceType::Corrective, MaintenancePriority::High, MaintenanceStatus::InProgress, 'Ganti baterai CMOS yang drop setiap restart.'],
            [MaintenanceType::Corrective, MaintenancePriority::Low, MaintenanceStatus::WaitingParts, 'Butuh modul RAM 8GB tambahan.'],
            [MaintenanceType::Corrective, MaintenancePriority::Medium, MaintenanceStatus::Completed, 'Ganti keyboard yang tidak responsif.'],
        ];

        foreach ($ticketPlans as $i => [$type, $priority, $status, $description]) {
            MaintenanceTicket::create([
                'organization_id' => $org?->id,
                'asset_id'        => $assets[$i % $assets->count()]->id,
                'reported_by'     => $staff?->id,
                'technician_id'   => $status !== MaintenanceStatus::Open ? $tech?->id : null,
                'code'            => 'TKT-DEMO-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'type'            => $type->value,
                'priority'        => $priority->value,
                'status'          => $status->value,
                'description'     => $description,
                'diagnosis'       => in_array($status, [MaintenanceStatus::InProgress, MaintenanceStatus::WaitingParts, MaintenanceStatus::Completed], true)
                    ? 'Komponen utama tidak bekerja optimal, perlu penggantian.'
                    : null,
                'cost'            => $status === MaintenanceStatus::Completed ? 450000 : 0,
                'scheduled_at'    => now()->addDays($i),
                'completed_at'    => $status === MaintenanceStatus::Completed ? now()->subDays(1) : null,
            ]);
        }

        // ── 5. Variasi kondisi aset agar grafik distribusi terlihat wajar ─
        Asset::query()->orderBy('id')->get()->each(function (Asset $asset, int $index) {
            $conditions = [
                AssetCondition::Excellent, AssetCondition::Good, AssetCondition::Good,
                AssetCondition::Fair, AssetCondition::Poor, AssetCondition::Broken,
            ];
            $asset->update(['condition' => $conditions[$index % count($conditions)]]);
        });

        $this->command?->info('Demo data dibuat: 5 reservasi, 6 peminjaman, 4 issue, 5 tiket maintenance.');
        $this->command?->info('Waktu acuan: ' . Carbon::now()->toDateTimeString());
    }

    /** Hapus jejak demo sebelumnya supaya seeder bisa dijalankan berulang. */
    private function purge(): void
    {
        $reservationIds = Reservation::where('code', 'like', 'RSV-DEMO-%')->pluck('id');
        ReservationItem::whereIn('reservation_id', $reservationIds)->delete();
        Reservation::whereIn('id', $reservationIds)->delete();

        $borrowingIds = Borrowing::where('code', 'like', 'BRW-DEMO-%')->pluck('id');
        BorrowingItem::whereIn('borrowing_id', $borrowingIds)->delete();
        Borrowing::whereIn('id', $borrowingIds)->delete();

        MaintenanceTicket::where('code', 'like', 'TKT-DEMO-%')->delete();
        Issue::where('code', 'like', 'ISS-DEMO-%')->delete();
    }
}
