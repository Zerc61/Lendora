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

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Dibuka',
            self::Assigned => 'Ditetapkan',
            self::InProgress => 'Dikerjakan',
            self::WaitingParts => 'Menunggu Sparepart',
            self::Completed => 'Selesai',
            self::Verified => 'Terverifikasi',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** State machine PDF bag. 9: open → assigned → in_progress → waiting_parts → completed → verified / cancelled */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open         => [self::Assigned, self::Cancelled],
            self::Assigned     => [self::InProgress, self::Cancelled],
            self::InProgress   => [self::WaitingParts, self::Completed, self::Cancelled],
            self::WaitingParts => [self::InProgress, self::Completed, self::Cancelled],
            self::Completed    => [self::Verified, self::Cancelled],
            self::Verified,
            self::Cancelled    => [], // terminal
        };
    }
}
