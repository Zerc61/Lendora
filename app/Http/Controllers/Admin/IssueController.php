<?php
// app/Http/Controllers/Admin/IssueController.php

namespace App\Http\Controllers\Admin;

use App\Actions\TransitionIssue;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueRequest;
use App\Models\Asset;
use App\Models\Issue;
use App\Models\MaintenanceTicket;
use App\Services\RecordCodeGenerator;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Issue::class, 'issue');
    }

    public function index(Request $request)
    {
        $issues = Issue::query()
            ->with(['asset.assetType', 'reportedBy'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.issues.index', [
            'issues' => $issues,
            'statuses' => collect(IssueStatus::cases()),
            'types' => collect(IssueType::cases()),
        ]);
    }

    /** Form pelaporan — quick action dari QR/detail aset (PDF 4E, dari Phase 5) */
    public function create(Request $request)
    {
        return view('admin.issues.create', [
            'assets' => Asset::orderBy('asset_code')->get(['id', 'asset_code']),
            'selectedAssetId' => $request->query('asset'),
        ]);
    }

    public function store(StoreIssueRequest $request, RecordCodeGenerator $codes)
    {
        $asset = Asset::findOrFail($request->validated('asset_id'));

        $issue = Issue::create([
            'code' => $codes->next('ISS', 'issues'),
            'organization_id' => auth()->user()->organization_id ?? $asset->organization_id,
            'asset_id' => $asset->id,
            'reported_by' => auth()->id(),
            'type' => IssueType::from($request->validated('type')),
            'severity' => IssueSeverity::from($request->validated('severity')),
            'status' => IssueStatus::Open,
            'description' => $request->validated('description'),
        ]);

        return redirect()
            ->route('admin.issues.show', $issue)
            ->with('success', "Issue {$issue->code} berhasil dilaporkan.");
    }

    public function show(Issue $issue)
    {
        $issue->load(['asset.assetType', 'reportedBy', 'resolvedBy', 'borrowing']);

        $tickets = MaintenanceTicket::where('issue_id', $issue->id)->get();

        return view('admin.issues.show', compact('issue', 'tickets'));
    }

    public function transition(Request $request, Issue $issue, TransitionIssue $action)
    {
        $this->authorize('transition', $issue);

        $data = $request->validate([
            'action' => ['required', 'in:investigate,resolve,reject,close'],
            'resolution' => ['nullable', 'string', 'max:1000'],
            'loss_outcome' => ['nullable', 'in:recovered,retired'],
        ]);

        $action->execute(
            $issue,
            $request->user(),
            $data['action'],
            $data['resolution'] ?? null,
            $data['loss_outcome'] ?? null,
        );

        return back()->with('success', "Issue {$issue->code} diperbarui.");
    }
}
