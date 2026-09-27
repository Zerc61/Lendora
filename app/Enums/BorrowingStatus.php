<?php
// app/Enums/BorrowingStatus.php
namespace App\Enums;

/**
 * Status transaksi peminjaman.
 *
 * Alur: pending → approved → borrowed → returned, dengan overdue sebagai
 * turunan dari borrowed, serta rejected/cancelled sebelum serah-terima.
 *
 * Penting: peminjaman lahir dari persetujuan reservasi (App\Actions\
 * ApproveReservation) langsung pada status Approved — bukan Pending.
 * Antrean check-out, tombol aksi, dan semua penghitung "siap check-out"
 * menyaring status ini, jadi memakai nilai lain membuat transaksi terlihat
 * di daftar namun tidak pernah terhitung.
 */
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
