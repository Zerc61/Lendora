<?php
// app/Actions/ApproveReservation.php

namespace App\Actions;

use App\Enums\AssetStatus;
use App\Enums\BorrowingStatus;
use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusNotification;
use App\Services\RecordCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveReservation
{
    public function __construct(private RecordCodeGenerator $codes) {}

    public function execute(Reservation $reservation, User $approver): Borrowing
    {
        return DB::transaction(function () use ($reservation, $approver) {
            if ($reservation->status !== ReservationStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Hanya reservasi berstatus pending yang dapat disetujui.']);
            }

            $reservation->update([
                'status' => ReservationStatus::Approved,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            // State machine: available → reserved (jika unit memang available)
            $reservation->items->each(function ($item) {
                $asset = Asset::whereKey($item->asset_id)->lockForUpdate()->first();
                if ($asset && $asset->status === AssetStatus::Available) {
                    $asset->update(['status' => AssetStatus::Reserved]);
                }
            });

            // Transaksi peminjaman dibuat otomatis — status "disetujui": reservasi
            // sudah disetujui, unit tinggal menunggu serah-terima fisik operator.
            // Jangan pakai Pending: antrean check-out (CheckoutController) dan
            // penghitung "siap check-out" menyaring status Approved, sehingga
            // transaksi akan tampil di daftar tapi tidak pernah terhitung.
            $borrowing = Borrowing::create([
                'code' => $this->codes->next('BRW', 'borrowings'),
                'organization_id' => $reservation->organization_id,
                'reservation_id' => $reservation->id,
                'borrower_id' => $reservation->user_id,
                'approved_by' => $approver->id,
                'status' => BorrowingStatus::Approved,
                'purpose' => $reservation->purpose,
                'due_at' => $reservation->end_at, // batas kembali = akhir reservasi
            ]);

            foreach ($reservation->items as $item) {
                $borrowing->items()->create([
                    'asset_id' => $item->asset_id,
                    'quantity' => $item->quantity,
                ]);
            }

            // PDF 4G: notifikasi status pengajuan ke peminjam
            $reservation->user->notify(new ReservationStatusNotification($reservation, 'approved'));

            return $borrowing;
        });
    }
}
