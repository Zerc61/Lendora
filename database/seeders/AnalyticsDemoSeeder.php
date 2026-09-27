<?php

// database/seeders/AnalyticsDemoSeeder.php
namespace Database\Seeders;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Enums\UserStatus;
use App\Models\Asset;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data historis 12 bulan untuk membuktikan sistem analitik bekerja.
 *
 * Seeder ini sengaja membuat data yang SPARSA dan TIDAK MERATA pada bulan
 * sebelumnya — justru itu yang membuktikan dua hal:
 *
 *  1. ReportService::borrowingTrend() mengisi bulan kosong dengan 0 sehingga
 *     grafik tetap 6 batang penuh. Kalau tidak, grafik bolong dan analytics
 *    .bohong (seolah tidak ada transaksi).
 *  2. Analitik lain (utilisasi aset, biaya maintenance, distribusi kondisi)
 *     benar-benar dihitung dari transaksi nyata, bukan angka dummy.
 *
 * Pola musiman: libur sekolah (Juni–Juli & Desember) sepi, tahun ajaran sibuk.
 * Idempotent — ditandai lewat kode transaksi ANALYTICS-* sehingga bisa dihapus
 * lalu dibuat ulang tanpa meninggalkan sisa.
 */
class AnalyticsDemoSeeder extends Seeder
{
    private const PREFIX = 'ANA';

    private const MONTHS = 12;

    public function run(): void
    {
        $students = User::whereHas('schoolClass')
            ->where('status', UserStatus::Active)
            ->pluck('id')
            ->all();

        $assets = Asset::where('status', '!=', AssetStatus::Retired)->get(['id', 'status', 'organization_id']);

        if (! $students || $assets->isEmpty()) {
            $this->command?->warn('AnalyticsDemoSeeder: perlu SchoolStructureSeeder & MasterDataSeeder lebih dulu.');

            return;
        }

        $this->purge();

        $created = 0;
        $now = now();

        for ($back = self::MONTHS - 1; $back >= 0; $back--) {
            $monthStart = $now->copy()->startOfMonth()->subMonths($back);

            foreach ($this->demandFor($monthStart) as $n) {
                for ($i = 0; $i < $n; $i++) {
                    if ($this->makeBorrowing($monthStart, $students, $assets)) {
                        $created++;
                    }
                }
            }
        }

        $this->makeMaintenanceHistory();
        $this->makeRecentQueue();

        $this->command?->info(sprintf(
            'AnalyticsDemoSeeder: %d transaksi, %d tiket maintenance%s',
            $created,
            MaintenanceTicket::where('code', 'like', 'TKT-'.self::PREFIX.'-%')->count(),
            PHP_EOL,
        ));
    }

    /**
     * Permintaan per bulan mengikuti kalender sekolah, bukan angka acak rata:
     *
     *   Jan–Mei  → aktif
     *   Jun–Jul  → libur midsemester, turun
     *   Agu–Nov  → tahun ajaran baru, paling sibuk
     *   Des      → libur akhir tahun, turun
     *
     * Pola ini penting: kalau tiap bulan sama saja, grafik tren hanya
     * membuktikan bahwa bars bekerja — bukan bahwa query pengelompokannya benar.
     */
    private function demandFor(Carbon $monthStart): array
    {
        $month = (int) $monthStart->month;

        // Juni sengaja dibuat 0. Selain realistis (libur semester + tahun
        // ajaran baru), ini membuktikan ReportService::borrowingTrend() mengisi
        // bulan kosong dengan 0 — kalau tidak, batang Juni hilang dan grafik
        // terlihat bolong.
        if ($month === 6) {
            return [0];
        }

        $base = match (true) {
            in_array($month, [6, 7], true) => random_int(1, 3), // libur midsemester
            $month === 12 => random_int(2, 4),                   // libur akhir tahun
            $month === 8 => random_int(16, 24),                  // tahun ajaran baru, paling sibuk
            $month === 11 => random_int(12, 18),
            default => random_int(7, 13),
        };

        return [$base];
    }

    /** @return bool true bila transaksi tersimpan */
    private function makeBorrowing(Carbon $monthStart, array $studentIds, $assets): bool
    {
        $now = now();
        $out = $monthStart->copy()->addDays(random_int(0, 27))->setTime(random_int(7, 16), random_int(0, 59));

        // Jangan buat transaksi di masa depan.
        if ($out->greaterThan($now->copy()->subDays(2))) {
            return false;
        }

        $studentId = $studentIds[array_rand($studentIds)];

        // 1–3 unit per transaksi, unit harus tersedia & tidak terpakai bentrok.
        $pool = $assets->where('status', AssetStatus::Available)->shuffle()->take(random_int(1, 3));
        if ($pool->isEmpty()) {
            return false;
        }

        $code = $this->nextCode('BRW');
        $dueAt = $out->copy()->addDays(random_int(3, 21));

        // Status: 70% sudah kembali, 20% masih dipinjam, 10% terlambat.
        $roll = random_int(1, 100);
        $returned = $roll <= 70;
        $overdue = $roll > 90;

        $row = [
            'code' => $code,
            // borrowings.organization_id NOT NULL — mengikuti unit yang dipinjam.
            'organization_id' => $pool->first()->organization_id,
            'borrower_id' => $studentId,
            'status' => $returned ? 'returned' : ($overdue ? 'overdue' : 'borrowed'),
            'purpose' => $this->purpose(),
            'checked_out_at' => $out,
            'due_at' => $dueAt,
            'created_at' => $out->copy()->subDays(random_int(1, 5)),
            'updated_at' => $now,
        ];

        if ($returned) {
            $returnedAt = $dueAt->copy()->addDays(random_int(-2, 4));
            if ($returnedAt->greaterThan($now)) {
                $returnedAt = $now->copy()->subHours(random_int(2, 60));
            }
            if ($returnedAt->lessThan($out)) {
                $returnedAt = $out->copy()->addDay();
            }
            $row['returned_at'] = $returnedAt;
        }

        $id = DB::table('borrowings')->insertGetId($row);

        $items = [];
        foreach ($pool as $asset) {
            $items[] = [
                'borrowing_id' => $id,
                'asset_id' => $asset->id,
                'quantity' => 1,
                'condition_out' => $this->condition()->value,
                'condition_in' => $returned ? $this->condition()->value : null,
                'notes_out' => 'Kondisi saat diserahkan.',
                'notes_in' => $returned ? 'Kondisi saat dikembalikan.' : null,
                'created_at' => $row['created_at'],
                'updated_at' => $now,
            ];
        }
        DB::table('borrowing_items')->insert($items);

        return true;
    }

    /** Riwayat biaya maintenance sepanjang 12 bulan (grafik biaya di Laporan). */
    private function makeMaintenanceHistory(): void
    {
        $assets = Asset::where('status', '!=', AssetStatus::Retired)->get(['id', 'organization_id']);
        // maintenance_tickets.reported_by NOT NULL — butuh user pelapor.
        $staffIds = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['staff', 'technician']))
            ->pluck('id')->all();
        $reporterId = $staffIds[0] ?? null;

        if (! $reporterId) {
            $this->command?->warn('AnalyticsDemoSeeder: butuh user staff/technisi untuk reported_by.');

            return;
        }

        $types = collect(MaintenanceType::cases())->map(fn ($c) => $c->value)->all();
        $priorities = collect(MaintenancePriority::cases())->map(fn ($c) => $c->value)->all();
        $now = now();
        $rows = [];

        for ($back = self::MONTHS - 1; $back >= 0; $back--) {
            $monthStart = $now->copy()->startOfMonth()->subMonths($back);
            $count = $monthStart->month === 8 ? random_int(5, 8) : random_int(1, 4);

            for ($i = 0; $i < $count; $i++) {
                $done = $monthStart->copy()->addDays(random_int(0, 27))->setTime(random_int(8, 17), 0);
                if ($done->greaterThan($now->copy()->subDay())) {
                    continue;
                }
                $priority = $priorities[array_rand($priorities)];
                $asset = $assets[random_int(0, $assets->count() - 1)];

                $rows[] = [
                    'organization_id' => $asset->organization_id,
                    'asset_id' => $asset->id,
                    'reported_by' => $reporterId,
                    'technician_id' => $reporterId,
                    'code' => $this->nextCode('TKT'),
                    'type' => $types[array_rand($types)],
                    'priority' => $priority,
                    'status' => MaintenanceStatus::Verified->value,
                    'description' => 'Perawatan berkala terjadwal.',
                    'diagnosis' => 'Komponen aus perlu penggantian.',
                    'cost' => $priority === 'critical' ? random_int(800, 2500) * 1000
                        : ($priority === 'high' ? random_int(300, 900) * 1000 : random_int(50, 300) * 1000),
                    'scheduled_at' => $done->copy()->subDays(2),
                    'completed_at' => $done,
                    'created_at' => $monthStart,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('maintenance_tickets')->insert($chunk);
        }
    }

    /** Antrean aktif (approved) supaya kartu antrean dashboard tidak kosong. */
    private function makeRecentQueue(): void
    {
        $students = User::whereHas('schoolClass')->pluck('id')->all();
        $assets = Asset::where('status', AssetStatus::Available)->get(['id', 'organization_id']);
        $now = now();

        for ($i = 0; $i < 6; $i++) {
            $dueAt = $now->copy()->addDays(random_int(2, 14));
            $asset = $assets[random_int(0, $assets->count() - 1)];
            $id = DB::table('borrowings')->insertGetId([
                'code' => $this->nextCode('BRW'),
                'organization_id' => $asset->organization_id,
                'borrower_id' => $students[array_rand($students)],
                'status' => 'approved', // disetujui, belum diserahkan
                'purpose' => $this->purpose(),
                'due_at' => $dueAt,
                'created_at' => $now->copy()->subDays(random_int(1, 6)),
                'updated_at' => $now,
            ]);

            DB::table('borrowing_items')->insert([
                'borrowing_id' => $id,
                'asset_id' => $asset->id,
                'quantity' => 1,
                'condition_out' => AssetCondition::Good->value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** Hapus data ANALYTICS-* supaya seeder bisa dijalankan ulang. */
    private function purge(): void
    {
        $borrowings = DB::table('borrowings')
            ->where('code', 'like', 'BRW-'.self::PREFIX.'-%')
            ->pluck('id');
        if ($borrowings->isNotEmpty()) {
            DB::table('borrowing_items')->whereIn('borrowing_id', $borrowings)->delete();
            DB::table('borrowings')->whereIn('id', $borrowings)->delete();
        }

        $tickets = DB::table('maintenance_tickets')
            ->where('code', 'like', 'TKT-'.self::PREFIX.'-%')
            ->pluck('id');
        if ($tickets->isNotEmpty()) {
            DB::table('maintenance_logs')->whereIn('maintenance_ticket_id', $tickets)->delete();
            DB::table('maintenance_tickets')->whereIn('id', $tickets)->delete();
        }
    }

    /**
     * Kode transaksi demo. Penanda PREFIX wajib ikut di dalam kode —
     * tanpa itu `LIKE 'ANA-%'` tidak akan pernah cocok sehingga purge
     * diam-diam tidak menghapus apa pun dan seeder tidak idempotent.
     */
    private function nextCode(string $prefix): string
    {
        return $prefix.'-'.self::PREFIX.'-'.Str::upper(Str::random(6));
    }

    /** @return array<int, string> */
    private function purposes(): array
    {
        return [
            'Pelatihan internal',
            'Dokumentasi kegiatan sekolah',
            'Survey lapangan',
            'Presentasi kelas',
            'Uji coba perangkat',
            'Inventaris ruang kelas',
        ];
    }

    private function purpose(): string
    {
        return $this->purposes()[array_rand($this->purposes())];
    }

    private function condition(): AssetCondition
    {
        // Condong ke "good" supaya health score terpusat realistis, bukan semua rusak.
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 55 => AssetCondition::Good,
            $roll <= 80 => AssetCondition::Fair,
            $roll <= 92 => AssetCondition::Excellent,
            $roll <= 98 => AssetCondition::Poor,
            default => AssetCondition::Broken,
        };
    }
}
