<?php
// app/Actions/CancelReservation.php

namespace App\Actions;

use App\Enums\AssetStatus;
use App\Enums\BorrowingStatus;
use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelReservation
{
    public function execute(Reservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            if (! in_array($reservation->status, [ReservationStatus::Pending, ReservationStatus::Approved], true)) {
                throw ValidationException::withMessages(['status' => 'Reservasi yang sudah diproses/ditolak tidak dapat dibatalkan.']);
            }

            $wasApproved = $reservation->status === ReservationStatus::Approved;
            $reservation->update(['status' => ReservationStatus::Cancelled]);

            // Borrowing pending terkait ikut dibatalkan
            Borrowing::where('reservation_id', $reservation->id)
                ->where('status', BorrowingStatus::Pending)
                ->update(['status' => BorrowingStatus::Cancelled]);

            if ($wasApproved) {
                $this->releaseAssets($reservation);
            }
        });
    }

    /** Lepaskan unit kembali ke Available jika tidak ada keterikatan lain */
    private function releaseAssets(Reservation $reservation): void
    {
        $reservation->items->each(function ($item) {
            $asset = Asset::find($item->asset_id);

            if (! $asset || $asset->status !== AssetStatus::Reserved) {
                return;
            }

            // Masih ada reservasi approved lain mendatang untuk unit ini?
            $hasOtherApprovedReservation = $asset->reservationItems()
                ->whereHas('reservation', fn ($q) => $q
                    ->where('status', ReservationStatus::Approved->value)
                    ->whereKeyNot($reservation->id)
                    ->where('end_at', '>=', now()))
                ->exists();

            if (! $hasOtherApprovedReservation) {
                $asset->update(['status' => AssetStatus::Available]);
            }
        });
    }
}
