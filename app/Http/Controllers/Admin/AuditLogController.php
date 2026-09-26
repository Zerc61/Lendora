<?php
// app/Http/Controllers/Admin/AuditLogController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Borrowing;
use App\Models\Issue;
use App\Models\MaintenanceTicket;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * PDF 4L: audit log READ-ONLY — hanya index, tidak ada edit/delete.
     */
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->with(['actor'])
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', "%{$request->action}%"))
            ->when($request->filled('actor_id'), fn ($q) => $q->where('actor_id', $request->actor_id))
            ->when($request->filled('subject_type'), fn ($q) => $q->where('subject_type', $request->subject_type))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'actors' => User::orderBy('name')->get(['id', 'name']),
            'subjects' => collect([
                Asset::class => 'Aset',
                Reservation::class => 'Reservasi',
                Borrowing::class => 'Peminjaman',
                MaintenanceTicket::class => 'Tiket Maintenance',
                Issue::class => 'Issue',
            ]),
        ]);
    }
}
