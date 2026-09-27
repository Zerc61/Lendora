<?php
// app/Enums/BorrowingStatus.php
namespace App\Enums;

enum BorrowingStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Borrowed = 'borrowed';
    case Returned = 'returned';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Disetujui',
            self::Borrowed => 'Dipinjam',
            self::Returned => 'Dikembalikan',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
            self::Overdue => 'Terlambat',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Approved => 'info',
            self::Borrowed => 'brand',
            self::Returned => 'ok',
            self::Rejected, self::Cancelled => 'muted',
            self::Overdue => 'bad',
        };
    }
}
