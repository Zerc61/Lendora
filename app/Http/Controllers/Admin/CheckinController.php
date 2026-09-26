<?php
// app/Http/Controllers/Admin/CheckinController.php

namespace App\Http\Controllers\Admin;

use App\Actions\CheckinBorrowing;
use App\Enums\BorrowingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckinRequest;
use App\Models\Borrowing;

class CheckinController extends Controller
{
    public function index()
    {
        // Antrean: transaksi aktif yang menunggu pengembalian
        $borrowings = Borrowing::where('status', BorrowingStatus::Borrowed)
            ->with(['borrower', 'items.asset.assetType'])
            ->orderBy('due_at')
            ->paginate(10);

        return view('admin.checkin.index', compact('borrowings'));
    }

    public function show(Borrowing $borrowing)
    {
        abort_unless($borrowing->status === BorrowingStatus::Borrowed, 404, 'Transaksi ini tidak sedang berjalan.');

        $borrowing->load(['borrower', 'items.asset.assetType', 'items.inspectionCheckout']);

        return view('admin.checkin.show', compact('borrowing'));
    }

    public function store(CheckinRequest $request, Borrowing $borrowing, CheckinBorrowing $action)
    {
        $action->execute($borrowing, $request->user(), $request->validated('items'), $request->validated('checkin_notes'));

        return redirect()
            ->route('admin.borrowings.show', $borrowing)
            ->with('success', "Check-in {$borrowing->code} berhasil. Issue otomatis dibuat jika ada unit rusak/hilang.");
    }
}
