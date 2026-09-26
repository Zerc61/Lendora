<?php
// app/Actions/RejectReservation.php

namespace App\Actions;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectReservation
{
    public function execute(Reservation $reservation, User $rejector, string $reason): void
    {
        DB::transaction(function () use ($reservation, $rejector, $reason) {
            if ($reservation->status !== ReservationStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Hanya reservasi berstatus pending yang dapat ditolak.']);
            }

            $reservation->update([
                'status' => ReservationStatus::Rejected,
                'approved_by' => $rejector->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);
        });
    }
}
