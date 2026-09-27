<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Enums\AssetCondition;
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

/**
 * Dashboard per peran — tiap peran punya beranda sendiri (PDF bag. 3):
 *   admin      → kendali penuh: kondisi inventori, tren, insight
 *   staff      → fokus antrean: apa yang harus diproses sekarang
 *   technician → meja kerja: tiket yang dikerjakan
 *   borrower   → alur peminjam: ajukan, pantau, kembali
 */
class DashboardController extends Controller
{
    public function __invoke(ReportService $reports)
    {
        $user = auth()->user();

        return match ($user->primaryRole()) {
            'admin' => $this->admin($user, $reports),
            'staff' => $this->staff($user),
            'technician' => $this->technician($user),
            default => $this->borrower($user),
        };
    }

    /** Admin: ringkasan operasional lengkap. */
    private function admin($user, ReportService $reports)
    {
        $stats = [
            'total'        => Asset::count(),
            'available'    => Asset::where('status', AssetStatus::Available->value)->count(),
            'reserved'     => Asset::where('status', AssetStatus::Reserved->value)->count(),
            'borrowed'     => Asset::where('status', AssetStatus::Borrowed->value)->count(),
            'unhealthy'    => Asset::whereIn('status', [AssetStatus::Maintenance->value, AssetStatus::Damaged->value, AssetStatus::Lost->value])->count(),
            'reservations' => Reservation::where('status', ReservationStatus::Pending->value)->count(),
            'pending'      => Borrowing::where('status', BorrowingStatus::Pending->value)->count(),
            'active'       => Borrowing::where('status', BorrowingStatus::Borrowed->value)->count(),
            'overdue'      => Borrowing::where('status', BorrowingStatus::Borrowed->value)
                ->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'issues'       => Issue::whereIn('status', [IssueStatus::Open->value, IssueStatus::Investigating->value])->count(),
            'tickets'      => MaintenanceTicket::whereIn('status', [
                MaintenanceStatus::Open->value,
                MaintenanceStatus::Assigned->value,
                MaintenanceStatus::InProgress->value,
                MaintenanceStatus::WaitingParts->value,
            ])->count(),
        ];

        $statusDistribution = collect(AssetStatus::cases())
            ->map(fn ($s) => [
                'status' => $s,
                'count'  => Asset::where('status', $s->value)->count(),
            ]);

        $condition = collect(AssetCondition::cases())
            ->map(fn ($c) => [
                'condition' => $c,
                'count'     => Asset::where('condition', $c->value)->count(),
            ]);

        // Skor kondisi rata-rata (dihitung di PHP agar bebas dari keyword SQL "condition")
        $totalAssets = $stats['total'];
        $averageCondition = $totalAssets
            ? (int) round($condition->sum(fn ($row) => $row['condition']->score() * $row['count']) / $totalAssets)
            : 0;

        return view('admin.dashboard', [
            'user'               => $user,
            'stats'              => $stats,
            'availabilityRate'   => $stats['total'] ? (int) round($stats['available'] / $stats['total'] * 100) : 0,
            'overdueRate'        => $stats['active'] ? (int) round($stats['overdue'] / $stats['active'] * 100) : 0,
            'healthScore'        => $stats['total'] ? max(0, 100 - (int) round($stats['unhealthy'] / $stats['total'] * 100)) : 0,
            'averageCondition'   => $averageCondition,
            'statusDistribution' => $statusDistribution,
            'conditionDistribution' => $condition,
            'trend'              => $reports->borrowingTrend(),
            'topAssets'          => Asset::withCount('borrowingItems')
                ->orderByDesc('borrowing_items_count')
                ->limit(5)
                ->get(),
            'recentIssues'       => Issue::with('asset')->latest()->limit(5)->get(),
            'recentTickets'      => MaintenanceTicket::with('asset')->latest()->limit(5)->get(),
        ]);
    }

    /** Staff: apa yang harus diproses hari ini. */
    private function staff($user)
    {
        $toCheckout = Borrowing::with(['borrower', 'items'])
            ->where('status', BorrowingStatus::Approved->value)
            ->oldest('created_at')
            ->limit(6)->get();

        $toCheckin = Borrowing::with(['borrower', 'items'])
            ->whereIn('status', [BorrowingStatus::Borrowed->value, BorrowingStatus::Overdue->value])
            ->orderBy('due_at')
            ->limit(6)->get();

        $pendingReservations = Reservation::with(['user', 'items'])
            ->where('status', ReservationStatus::Pending->value)
            ->oldest()
            ->limit(6)->get();

        return view('staff.dashboard', [
            'user'        => $user,
            'toCheckout'  => $toCheckout,
            'toCheckin'   => $toCheckin,
            'reservations' => $pendingReservations,
            'stats'       => [
                'checkout'      => Borrowing::where('status', BorrowingStatus::Approved->value)->count(),
                'checkin'       => Borrowing::whereIn('status', [BorrowingStatus::Borrowed->value, BorrowingStatus::Overdue->value])->count(),
                'overdue'       => Borrowing::where('status', BorrowingStatus::Overdue->value)
                    ->orWhere(fn ($q) => $q->where('status', BorrowingStatus::Borrowed->value)->whereNotNull('due_at')->where('due_at', '<', now()))
                    ->count(),
                'reservations'  => Reservation::where('status', ReservationStatus::Pending->value)->count(),
                'today'         => Borrowing::whereDate('checked_out_at', today())->count(),
                'returned'      => Borrowing::whereDate('returned_at', today())->count(),
            ],
        ]);
    }

    /** Teknisi: tiket yang menjadi tanggung jawabnya. */
    private function technician($user)
    {
        $mine = MaintenanceTicket::with(['asset'])
            ->where(function ($query) use ($user) {
                $query->where('technician_id', $user->id)
                    ->orWhereNull('technician_id');
            })
            ->whereIn('status', [
                MaintenanceStatus::Assigned->value,
                MaintenanceStatus::InProgress->value,
                MaintenanceStatus::WaitingParts->value,
            ])
            ->orderByRaw("case status when 'in_progress' then 0 when 'assigned' then 1 else 2 end")
            ->orderByDesc('priority')
            ->limit(8)->get();

        $inProgress = MaintenanceTicket::where('technician_id', $user->id)
            ->whereIn('status', [MaintenanceStatus::Assigned->value, MaintenanceStatus::InProgress->value])
            ->count();

        return view('technician.dashboard', [
            'user'   => $user,
            'tickets' => $mine,
            'stats'  => [
                'mine'         => MaintenanceTicket::where('technician_id', $user->id)->count(),
                'in_progress'  => $inProgress,
                'waiting_parts' => MaintenanceTicket::where('technician_id', $user->id)
                    ->where('status', MaintenanceStatus::WaitingParts->value)->count(),
                'unassigned'   => MaintenanceTicket::whereNull('technician_id')
                    ->where('status', MaintenanceStatus::Open->value)->count(),
            ],
            'issues' => Issue::with('asset')
                ->whereIn('status', [IssueStatus::Open->value, IssueStatus::Investigating->value])
                ->latest()->limit(5)->get(),
        ]);
    }

    /** Peminjam: alur beserta, danulantakan. */
    private function borrower($user)
    {
        return view('borrower.dashboard', [
            'user'          => $user,
            'reservations'  => Reservation::with('items')
                ->where('user_id', $user->id)
                ->whereIn('status', [ReservationStatus::Pending->value, ReservationStatus::Approved->value])
                ->latest()
                ->limit(5)->get(),
            'activeBorrowings' => Borrowing::with('items')
                ->where('borrower_id', $user->id)
                ->whereIn('status', [BorrowingStatus::Approved->value, BorrowingStatus::Borrowed->value, BorrowingStatus::Overdue->value])
                ->latest()
                ->limit(5)->get(),
            'history'       => Borrowing::with('items')
                ->where('borrower_id', $user->id)
                ->where('status', BorrowingStatus::Returned->value)
                ->latest()
                ->limit(4)->get(),
            'stats'         => [
                'active'    => Borrowing::where('borrower_id', $user->id)
                    ->whereIn('status', [BorrowingStatus::Borrowed->value, BorrowingStatus::Overdue->value])->count(),
                'overdue'   => Borrowing::where('borrower_id', $user->id)
                    ->where('status', BorrowingStatus::Overdue->value)->count(),
                'reservasi' => Reservation::where('user_id', $user->id)
                    ->whereIn('status', [ReservationStatus::Pending->value, ReservationStatus::Approved->value])->count(),
                'total'     => Borrowing::where('borrower_id', $user->id)->count(),
            ],
        ]);
    }
}
