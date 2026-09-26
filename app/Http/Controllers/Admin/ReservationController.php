<?php
// app/Http/Controllers/Admin/ReservationController.php

namespace App\Http\Controllers\Admin;

use App\Actions\ApproveReservation;
use App\Actions\RejectReservation;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Reservation::class, 'reservation');
    }

    public function index(Request $request)
    {
        $reservations = Reservation::query()
            ->with(['user', 'items.asset.assetType'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'statuses' => collect(ReservationStatus::cases()),
        ]);
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['user', 'items.asset.assetType', 'items.asset.location', 'approvedBy', 'borrowing.items.asset']);

        return view('admin.reservations.show', compact('reservation'));
    }

    public function approve(Request $request, Reservation $reservation, ApproveReservation $action)
    {
        $this->authorize('approve', $reservation);

        $borrowing = $action->execute($reservation, $request->user());

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', "Disetujui. Transaksi peminjaman {$borrowing->code} dibuat — menunggu checkout.");
    }

    public function reject(Request $request, Reservation $reservation, RejectReservation $action)
    {
        $this->authorize('reject', $reservation);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $action->execute($reservation, $request->user(), $validated['reason']);

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', 'Reservasi ditolak.');
    }
}
