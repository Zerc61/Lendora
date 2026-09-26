<?php
// app/Http/Controllers/Borrower/ReservationController.php

namespace App\Http\Controllers\Borrower;

use App\Actions\CancelReservation;
use App\Actions\CreateReservation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Asset;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index()
    {
        $reservations = Reservation::where('user_id', auth()->id())
            ->with(['items.asset.assetType', 'approvedBy'])
            ->latest()
            ->paginate(10);

        return view('borrower.reservations.index', compact('reservations'));
    }

    public function create()
    {
        // Borrower hanya melihat unit yang available (PDF alur 5.1: browse asset → pilih unit)
        $assets = Asset::available()
            ->with(['assetType.category', 'location'])
            ->orderBy('asset_code')
            ->get();

        return view('borrower.reservations.create', compact('assets'));
    }

    public function store(StoreReservationRequest $request, CreateReservation $action)
    {
        $reservation = $action->execute(
            $request->user(),
            $request->date('start_at'),
            $request->date('end_at'),
            $request->validated('purpose'),
            $request->validated('asset_ids'),
        );

        return redirect()
            ->route('my.reservations.index')
            ->with('success', "Reservasi {$reservation->code} berhasil diajukan. Menunggu persetujuan admin.");
    }

    public function show(Reservation $reservation)
    {
        abort_unless($reservation->user_id === auth()->id(), 403);

        $reservation->load(['items.asset.assetType', 'approvedBy']);

        return view('borrower.reservations.show', compact('reservation'));
    }

    public function cancel(Request $request, Reservation $reservation, CancelReservation $action)
    {
        // Pemilik reservasi, atau admin yang berhak approve
        abort_unless(
            $reservation->user_id === $request->user()->id || $request->user()->can('reservation.approve'),
            403
        );

        $action->execute($reservation);

        return back()->with('success', "Reservasi {$reservation->code} dibatalkan.");
    }
}
