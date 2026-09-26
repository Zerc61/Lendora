<?php
// app/Http/Controllers/Admin/BorrowingController.php

namespace App\Http\Controllers\Admin;

use App\Enums\BorrowingStatus;
use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use Illuminate\Http\Request;

class BorrowingController extends Controller
{
    public function index(Request $request)
    {
        $borrowings = Borrowing::query()
            ->with(['borrower', 'items.asset.assetType'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('code', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.borrowings.index', [
            'borrowings' => $borrowings,
            'statuses' => collect(BorrowingStatus::cases()),
        ]);
    }

    public function show(Borrowing $borrowing)
    {
        $borrowing->load([
            'borrower', 'reservation', 'approvedBy', 'checkedOutBy', 'checkedInBy',
            'items.asset.assetType', 'inspections.inspector',
        ]);

        $issues = $borrowing->issues()->with('asset')->get();

        return view('admin.borrowings.show', compact('borrowing', 'issues'));
    }
}
