<?php
// app/Console/Commands/ExpireReservations.php

namespace App\Console\Commands;

use App\Enums\AssetStatus;
use App\Enums\BorrowingStatus;
use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireReservations extends Command
{
    protected $signature = 'lendora:expire-reservations';
    protected $description = 'Kedaluwarsakan reservasi yang lewat end_at tanpa diproses + lepaskan unit (state machine bag. 9)';

    public function handle(): void
    {
        $expired = 0;

        // Pending yang tidak pernah disetujui (per-model agar observer audit berjalan)
        Reservation::where('status', ReservationStatus::Pending)
            ->where('end_at', '<', now())
            ->chunkById(100, function ($reservations) {
                foreach ($reservations as $reservation) {
                    $reservation->update(['status' => ReservationStatus::Expired]);
                }
            });

        // Approved tapi borrowing masih pending (tidak pernah di-checkout)
        Reservation::where('status', ReservationStatus::Approved)
            ->where('end_at', '<', now())
            ->with(['items', 'borrowing'])
            ->chunkById(100, function ($reservations) use (&$expired) {
                foreach ($reservations as $reservation) {
                    DB::transaction(function () use ($reservation, &$expired) {
                        $borrowing = $reservation->borrowing;

                        if ($borrowing && $borrowing->status === BorrowingStatus::Pending) {
                            $borrowing->update(['status' => BorrowingStatus::Cancelled]);
                        }

                        if ($borrowing && in_array($borrowing->status, [BorrowingStatus::Borrowed, BorrowingStatus::Overdue], true)) {
                            $reservation->update(['status' => ReservationStatus::Fulfilled]);

                            return;
                        }

                        $reservation->update(['status' => ReservationStatus::Expired]);

                        // Lepaskan unit Reserved → Available jika tidak ada reservasi approved lain
                        $reservation->items->each(function ($item) use ($reservation) {
                            $asset = Asset::whereKey($item->asset_id)->lockForUpdate()->first();

                            if (! $asset || $asset->status !== AssetStatus::Reserved) {
                                return;
                            }

                            $hasOther = $asset->reservationItems()
                                ->whereHas('reservation', fn ($q) => $q
                                    ->where('status', ReservationStatus::Approved->value)
                                    ->whereKeyNot($reservation->id)
                                    ->where('end_at', '>=', now()))
                                ->exists();

                            if (! $hasOther) {
                                $asset->update(['status' => AssetStatus::Available]);
                            }
                        });

                        $expired++;
                    });
                }
            });

        $this->info("Reservasi diproses: {$expired} expired.");
    }
}
