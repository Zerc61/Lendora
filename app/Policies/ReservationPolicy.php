<?php
// app/Policies/ReservationPolicy.php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        // Daftar reservasi admin = antrean approval, hanya yang boleh approve
        return $user->can('reservation.approve');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->can('reservation.view') || $user->id === $reservation->user_id;
    }

    public function approve(User $user, Reservation $reservation): bool
    {
        return $user->can('reservation.approve');
    }

    public function reject(User $user, Reservation $reservation): bool
    {
        return $user->can('reservation.approve');
    }
}
