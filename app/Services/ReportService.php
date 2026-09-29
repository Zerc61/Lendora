<?php

// app/Services/ReportService.php

namespace App\Services;

use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Query dasar laporan peminjaman — dipakai view, summary, dan CSV.
     * Filter: periode (created_at), peminjam, unit aset.
     */
    public function borrowingsQuery(Carbon $from, Carbon $to, ?int $userId = null, ?int $assetId = null): Builder
    {
        return Borrowing::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($userId, fn ($q) => $q->where('borrower_id', $userId))
            ->when($assetId, fn ($q) => $q->whereHas('items', fn ($i) => $i->where('asset_id', $assetId)))
            ->with(['borrower', 'items.asset'])
            ->latest('created_at');
    }

    /** Ringkasan periode (PDF 4M: laporan peminjaman per periode) */
    public function summary(Carbon $from, Carbon $to, ?int $userId = null, ?int $assetId = null): array
    {
        $base = $this->borrowingsQuery($from, $to, $userId, $assetId);

        return [
            'total_pengajuan' => (clone $base)->count(),
            'disetujui' => (clone $base)->whereIn('status', [BorrowingStatus::Approved->value, BorrowingStatus::Borrowed->value, BorrowingStatus::Returned->value])->count(),
            'ditolak' => (clone $base)->where('status', BorrowingStatus::Rejected->value)->count(),
            'selesai_kembali' => (clone $base)->where('status', BorrowingStatus::Returned->value)->count(),
            'masih_berjalan' => (clone $base)->whereIn('status', [BorrowingStatus::Borrowed->value, BorrowingStatus::Overdue->value])->count(),
            'terlambat_sekarang' => (clone $base)->where('status', BorrowingStatus::Overdue->value)->count(),
            'peminjam_unik' => (clone $base)->distinct('borrower_id')->count('borrower_id'),
        ];
    }

    /**
     * Utilisasi per aset (PDF 4M): total waktu unit keluar ÷ panjang periode.
     * Overlap periode otomatis terpotong (LEAST/GREATEST) — aturan transparan.
     */
    public function utilization(Carbon $from, Carbon $to, ?int $assetId = null): Collection
    {
        $periodHours = max(24, (int) $from->diffInHours($to));

        return DB::table('borrowing_items as bi')
            ->join('borrowings as b', 'b.id', '=', 'bi.borrowing_id')
            ->join('assets as a', 'a.id', '=', 'bi.asset_id')
            ->join('asset_types as at', 'at.id', '=', 'a.asset_type_id')
            ->whereNull('a.deleted_at')
            ->when($assetId, fn ($q) => $q->where('bi.asset_id', $assetId))
            ->whereNotNull('b.checked_out_at')
            // Interval peminjaman [checked_out_at, returned_at] beririsan dengan
            // jendela [from, to] kalau dan hanya kalau:
            //   mulai < akhir jendela  DAN  berakhir > awal jendela
            //
            // Dua kondisi SAJA, bukan tiga cabang OR. Versi lama
            // (whereBetween checked_out_at / whereBetween returned_at / belum
            // kembali) MELEWATI peminjaman yang keluar sebelum jendela lalu
            // kembali SESUDAH jendela: checked_out_at tidak di dalam [from,to],
            // returned_at juga tidak, dan returned_at bukan NULL — jadi hilang
            // sama sekali, padahal 100% waktunya jatuh di dalam jendela.
            //
            // Rumus interval di atas otomatis mencakup ketiga kasus lama
            // (dimulai di dalam, dikembalikan di dalam, masih keluar) PLUS
            // kasus yang hilang itu, dan tidak perlu penanganan NULL terpisah:
            // "belum kembali" berarti intervalnya terbuka ke masa depan —
            // selama dimulai sebelum $to, ia pasti beririsan.
            ->where('b.checked_out_at', '<=', $to)
            ->where(function ($end) use ($from) {
                $end->where('b.returned_at', '>=', $from)
                    ->orWhereNull('b.returned_at');
            })
            ->groupBy('bi.asset_id', 'a.asset_code', 'at.name')
            ->selectRaw(
                'a.asset_code, at.name AS type_name, COUNT(DISTINCT b.id) AS borrow_count, '
                // TIMESTAMPDIFF(HOUR, a, b) = (b - a) dalam jam. PostgreSQL
                // tidak punya TIMESTAMPDIFF: EXTRACT(EPOCH FROM (b - a))/3600.
                // `now()::timestamp`, bukan CURRENT_TIMESTAMP — kolomnya
                // `timestamp` tanpa timezone, jadi NOW() (timestamptz) akan
                // membuat COALESCE mengonversi kolom diam-diam ke zona sesi.
                // Urutan placeholder mengikuti urutan kemunculannya di SQL:
                // LEAST (batas atas) muncul lebih dulu, lalu GREATEST (batas bawah).
                .'ROUND(SUM(GREATEST(0, EXTRACT(EPOCH FROM ('
                .'LEAST(COALESCE(b.returned_at, now()::timestamp), ?)'
                .' - GREATEST(b.checked_out_at, ?)'
                .')) / 3600)) / 24, 1) AS days_out',
                [$to, $from]
            )
            ->orderByDesc('days_out')
            ->get()
            ->map(function ($row) use ($periodHours) {
                $row->utilization_pct = min(100, round(($row->days_out * 24 / $periodHours) * 100));

                return $row;
            });
    }

    /** Biaya maintenance per aset (PDF 4M: maintenance cost) */
    public function maintenanceCosts(Carbon $from, Carbon $to): Collection
    {
        return DB::table('maintenance_tickets as t')
            ->join('assets as a', 'a.id', '=', 't.asset_id')
            ->join('asset_types as at', 'at.id', '=', 'a.asset_type_id')
            ->whereNull('a.deleted_at')
            ->whereIn('t.status', ['completed', 'verified'])
            ->whereNotNull('t.completed_at')
            ->whereBetween('t.completed_at', [$from, $to])
            ->groupBy('t.asset_id', 'a.asset_code', 'at.name')
            ->selectRaw('a.asset_code, at.name AS type_name, COUNT(DISTINCT t.id) AS ticket_count, SUM(t.cost) AS total_cost')
            ->orderByDesc('total_cost')
            ->get();
    }

    /** Tren peminjaman 6 bulan terakhir (dashboard) */
    /**
     * Tren check-out 6 bulan terakhir, **selalu 6 baris**.
     *
     * Penting: seri harus kontinu. Kalau hanya bulan yang punya transaksi yang
     * dikembalikan, grafik menampilkan tonggol jauh dari kiri dan bulan kosong
     * terlihat seperti "tidak ada data" padahal hanya 0 transaksi.
     */
    public function borrowingTrend(int $months = 6): Collection
    {
        $since = now()->startOfMonth()->subMonths($months - 1);

        $counts = Borrowing::query()
            ->whereNotNull('checked_out_at')
            ->where('checked_out_at', '>=', $since)
            ->selectRaw("TO_CHAR(checked_out_at, 'YYYY-MM') AS month, COUNT(*) AS total")
            ->groupBy('month')
            ->pluck('total', 'month');

        return collect(range(0, $months - 1))
            ->map(function (int $back) use ($counts) {
                $date = now()->startOfMonth()->subMonths($back);
                $key = $date->format('Y-m');

                return [
                    'month' => $key,
                    'label' => $date->locale('id')->translatedFormat('M Y'),
                    'total' => (int) ($counts[$key] ?? 0),
                ];
            })
            ->reverse()
            ->values();
    }
}
