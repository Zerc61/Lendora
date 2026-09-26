<?php
// app/Enums/MaintenanceStatus.php
namespace App\Enums;

enum MaintenanceStatus: string
{
    case Open = 'open';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case WaitingParts = 'waiting_parts';
    case Completed = 'completed';
    case Verified = 'verified';
    case Cancelled = 'cancelled';
}