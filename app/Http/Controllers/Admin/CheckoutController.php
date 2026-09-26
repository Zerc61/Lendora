<?php
// app/Http/Controllers/Admin/CheckoutController.php

namespace App\Http\Controllers\Admin;

use App\Actions\CheckoutBorrowing;
use App\Enums\BorrowingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Borrowing;

class CheckoutController extends Controller
{
    public function index()
    {
        // Antrean: transaksi yang disetujui tapi unit belum diserahkan
        $borrowings = Borrowing::where('status', BorrowingStatus::Pending)
            ->with(['borrower', 'items.asset.assetType'])
            ->orderBy('due_at')
            ->paginate(10);

        return view('admin.checkout.index', compact('borrowings'));
    }

    public function show(Borrowing $borrowing)
    {
        abort_unless($borrowing->status === BorrowingStatus::Pending, 404, 'Transaksi ini tidak menunggu checkout.');

        $borrowing->load(['borrower', 'reservation', 'items.asset.assetType']);

        return view('admin.checkout.show', compact('borrowing'));
    }

    public function store(CheckoutRequest $request, Borrowing $borrowing, CheckoutBorrowing $action)
    {
        $action->execute($borrowing, $request->user(), $request->validated('items'));

        return redirect()
            ->route('admin.borrowings.show', $borrowing)
            ->with('success', "Check-out {$borrowing->code} berhasil — status aset menjadi Dipinjam.");
    }
}
