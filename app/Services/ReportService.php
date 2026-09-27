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
            'total_pengajuan'    => (clone $base)->count(),
            'disetujui'          => (clone $base)->whereIn('status', [BorrowingStatus::Approved->value, BorrowingStatus::Borrowed->value, BorrowingStatus::Returned->value])->count(),
            'ditolak'            => (clone $base)->where('status', BorrowingStatus::Rejected->value)->count(),
            'selesai_kembali'    => (clone $base)->where('status', BorrowingStatus::Returned->value)->count(),
            'masih_berjalan'     => (clone $base)->whereIn('status', [BorrowingStatus::Borrowed->value, BorrowingStatus::Overdue->value])->count(),
            'terlambat_sekarang' => (clone $base)->where('status', BorrowingStatus::Overdue->value)->count(),
            'peminjam_unik'      => (clone $base)->distinct('borrower_id')->count('borrower_id'),
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
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('b.checked_out_at', [$from, $to])
                    ->orWhereBetween('b.returned_at', [$from, $to])
                    ->orWhere(fn ($w) => $w->where('b.checked_out_at', '<', $from)->whereNull('b.returned_at'));
            })
            ->groupBy('bi.asset_id', 'a.asset_code', 'at.name')
            ->selectRaw(
                'a.asset_code, at.name AS type_name, COUNT(DISTINCT b.id) AS borrow_count, '
                .'ROUND(SUM(GREATEST(0, TIMESTAMPDIFF(HOUR, GREATEST(b.checked_out_at, ?), LEAST(COALESCE(b.returned_at, NOW()), ?)))) / 24, 1) AS days_out',
                [$from, $to]
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
            ->selectRaw("DATE_FORMAT(checked_out_at, '%Y-%m') AS month, COUNT(*) AS total")
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
