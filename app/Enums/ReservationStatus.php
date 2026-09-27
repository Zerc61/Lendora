<?php
// app/Enums/ReservationStatus.php
namespace App\Enums;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Fulfilled = 'fulfilled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
            self::Fulfilled => 'Selesai Dipinjam',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Approved => 'ok',
            self::Rejected, self::Cancelled, self::Expired => 'muted',
            self::Fulfilled => 'brand',
        };
    }
}
