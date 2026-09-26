<?php
// app/Http/Controllers/Borrower/BorrowingController.php

namespace App\Http\Controllers\Borrower;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;

class BorrowingController extends Controller
{
    public function index()
    {
        $borrowings = Borrowing::where('borrower_id', auth()->id())
            ->with(['items.asset.assetType'])
            ->latest()
            ->paginate(10);

        return view('borrower.borrowings.index', compact('borrowings'));
    }

    public function show(Borrowing $borrowing)
    {
        abort_unless($borrowing->borrower_id === auth()->id(), 403);

        $borrowing->load([
            'items.asset.assetType', 'reservation', 'approvedBy', 'checkedOutBy', 'checkedInBy',
            'inspections.inspector',
        ]);

        $issues = $borrowing->issues()->with('asset')->get();

        return view('borrower.borrowings.show', compact('borrowing', 'issues'));
    }
}
