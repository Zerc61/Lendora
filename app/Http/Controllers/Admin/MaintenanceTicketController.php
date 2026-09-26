<?php
// app/Http/Controllers/Admin/MaintenanceTicketController.php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateMaintenanceTicket;
use App\Actions\TransitionMaintenanceTicket;
use App\Enums\AssetStatus;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceTicketRequest;
use App\Http\Requests\UpdateMaintenanceTicketRequest;
use App\Models\Asset;
use App\Models\Issue;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Http\Request;

class MaintenanceTicketController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(MaintenanceTicket::class, 'ticket');
    }

    public function index(Request $request)
    {
        $tickets = MaintenanceTicket::query()
            ->with(['asset.assetType', 'technician', 'reportedBy'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('technician_id'), fn ($q) => $q->where('technician_id', $request->technician_id))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'statuses' => collect(MaintenanceStatus::cases()),
            'types' => collect(MaintenanceType::cases()),
            'technicians' => User::role('technician')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request)
    {
        // Tiket hanya untuk unit available/damaged (aturan transparan)
        $assets = Asset::whereIn('status', [AssetStatus::Available->value, AssetStatus::Damaged->value])
            ->with('assetType')
            ->orderBy('asset_code')
            ->get();

        // Issue damage yang belum punya tiket
        $issues = Issue::whereIn('status', [IssueStatus::Open->value, IssueStatus::Investigating->value])
            ->where('type', IssueType::Damage->value)
            ->whereNotIn('id', MaintenanceTicket::whereNotNull('issue_id')->pluck('issue_id'))
            ->with('asset')
            ->get();

        return view('admin.tickets.create', [
            'assets' => $assets,
            'issues' => $issues,
            'types' => collect(MaintenanceType::cases()),
            'priorities' => collect(MaintenancePriority::cases()),
            'selectedAssetId' => $request->query('asset'),
            'selectedIssueId' => $request->query('issue'),
        ]);
    }

    public function store(StoreMaintenanceTicketRequest $request, CreateMaintenanceTicket $action)
    {
        $ticket = $action->execute($request->user(), $request->validated());

        return redirect()
            ->route('admin.tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->code} dibuat. Unit {$ticket->asset->asset_code} dikunci dari peminjaman.");
    }

    public function show(MaintenanceTicket $ticket)
    {
        $ticket->load(['asset.assetType', 'issue', 'reportedBy', 'technician', 'logs.user']);

        return view('admin.tickets.show', [
            'ticket' => $ticket,
            'technicians' => User::role('technician')->orderBy('name')->get(['id', 'name']),
            'totalCost' => (float) $ticket->logs()->sum('cost'),
        ]);
    }

    public function edit(MaintenanceTicket $ticket)
    {
        return view('admin.tickets.edit', [
            'ticket' => $ticket,
            'types' => collect(MaintenanceType::cases()),
            'priorities' => collect(MaintenancePriority::cases()),
        ]);
    }

    public function update(UpdateMaintenanceTicketRequest $request, MaintenanceTicket $ticket)
    {
        $ticket->update($request->validated());

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Tiket diperbarui.');
    }

    /** Transisi workflow: assign / start / wait_parts / complete / verify / cancel */
    public function transition(Request $request, MaintenanceTicket $ticket, TransitionMaintenanceTicket $action)
    {
        $data = $request->validate([
            'action' => ['required', 'in:assign,start,wait_parts,complete,verify,cancel'],
            'technician_id' => ['nullable', 'integer', 'exists:users,id'],
            'final_condition' => ['nullable', 'in:excellent,good,fair,poor,broken'],
            'diagnosis' => ['nullable', 'string', 'max:1000'],
        ]);

        $action->execute($ticket, $request->user(), $data['action'], $data);

        return back()->with('success', "Tiket {$ticket->code} diperbarui.");
    }

    /** Work log: catatan pekerjaan + biaya/sparepart (PDF 4J) */
    public function addLog(Request $request, MaintenanceTicket $ticket)
    {
        $this->authorize('work', $ticket);

        $allowed = ['assigned', 'in_progress', 'waiting_parts'];

        if (! in_array($ticket->status->value, $allowed, true)) {
            return back()->withErrors('Work log hanya dapat ditambahkan saat tiket sedang dikerjakan.');
        }

        $data = $request->validate([
            'action' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'performed_at' => ['nullable', 'date'],
        ]);

        $ticket->logs()->create([
            'user_id' => $request->user()->id,
            'action' => $data['action'],
            'note' => $data['note'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'performed_at' => $data['performed_at'] ?? now(),
        ]);

        return back()->with('success', 'Work log ditambahkan.');
    }
}
