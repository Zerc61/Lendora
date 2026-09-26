<?php
// app/Enums/IssueStatus.php
namespace App\Enums;

enum IssueStatus: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
    case Rejected = 'rejected';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::Investigating => 'Diselidiki',
            self::Resolved => 'Terselesaikan',
            self::Rejected => 'Ditolak',
            self::Closed => 'Ditutup',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** State machine PDF bag. 9: open → investigating → resolved / rejected / closed */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open          => [self::Investigating],
            self::Investigating => [self::Resolved, self::Rejected, self::Closed],
            self::Resolved,
            self::Rejected,
            self::Closed        => [], // terminal
        };
    }
}
