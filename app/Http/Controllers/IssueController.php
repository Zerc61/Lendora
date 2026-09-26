<?php
// app/Http/Controllers/IssueController.php

namespace App\Http\Controllers;

use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueRequest;
use App\Models\Asset;
use App\Models\Issue;
use App\Services\RecordCodeGenerator;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    /** Form pelaporan masalah — quick action dari QR/detail aset (PDF 4E) */
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
            'code'            => $codes->next('ISS', 'issues'),
            'organization_id' => auth()->user()->organization_id ?? $asset->organization_id,
            'asset_id'        => $asset->id,
            'reported_by'     => auth()->id(),
            'type'            => IssueType::from($request->validated('type')),
            'severity'        => IssueSeverity::from($request->validated('severity')),
            'status'          => IssueStatus::Open,
            'description'     => $request->validated('description'),
        ]);

        return redirect()
            ->route('admin.assets.show', $asset)
            ->with('success', "Issue {$issue->code} berhasil dilaporkan.");
    }
}
