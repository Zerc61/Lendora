<?php
// app/Policies/MaintenanceTicketPolicy.php

namespace App\Policies;

use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceTicket;
use App\Models\User;

class MaintenanceTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('maintenance.view');
    }

    public function view(User $user, MaintenanceTicket $ticket): bool
    {
        return $user->can('maintenance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('maintenance.create');
    }

    public function update(User $user, MaintenanceTicket $ticket): bool
    {
        return $user->can('maintenance.create') && $ticket->status === MaintenanceStatus::Open;
    }

    /** Kerja tiket: teknisi yang ditugaskan, atau admin */
    public function work(User $user, MaintenanceTicket $ticket): bool
    {
        if (! $user->can('maintenance.work')) {
            return false;
        }

        return $user->can('maintenance.create') || $user->id === $ticket->technician_id;
    }
}
