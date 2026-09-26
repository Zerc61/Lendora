<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\BorrowingStatus;
use App\Enums\IssueStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Issue;
use App\Models\MaintenanceTicket;
use App\Models\Reservation;
use App\Services\ReportService;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports)
    {
        $stats = [
            'Total Aset'          => Asset::count(),
            'Tersedia'            => Asset::where('status', AssetStatus::Available->value)->count(),
            'Direservasi'         => Asset::where('status', AssetStatus::Reserved->value)->count(),
            'Dipinjam'            => Asset::where('status', AssetStatus::Borrowed->value)->count(),
            'Maintenance/Rusak'   => Asset::whereIn('status', [AssetStatus::Maintenance->value, AssetStatus::Damaged->value])->count(),
            'Reservasi Pending'   => Reservation::where('status', ReservationStatus::Pending->value)->count(),
            'Peminjaman Pending'  => Borrowing::where('status', BorrowingStatus::Pending->value)->count(),
            'Peminjaman Aktif'    => Borrowing::where('status', BorrowingStatus::Borrowed->value)->count(),
            'Terlambat (Overdue)' => Borrowing::where('status', BorrowingStatus::Borrowed->value)->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'Issue Terbuka'       => Issue::where('status', IssueStatus::Open->value)->count(),
            'Tiket Maintenance'   => MaintenanceTicket::whereIn('status', [MaintenanceStatus::Open->value, MaintenanceStatus::InProgress->value])->count(),
        ];

        // ── Analytics (PDF 4M: dashboard kondisi operasional utama) ──
        $statusDistribution = collect(AssetStatus::cases())
            ->map(fn ($s) => [
                'status' => $s,
                'count'  => Asset::where('status', $s->value)->count(),
            ]);

        return view('admin.dashboard', [
            'user'               => auth()->user(),
            'stats'              => $stats,
            'statusDistribution' => $statusDistribution,
            'trend'              => $reports->borrowingTrend(),
            'topAssets'          => Asset::withCount('borrowingItems')
                                    ->orderByDesc('borrowing_items_count')
                                    ->limit(5)
                                    ->get(),
        ]);
    }
}
