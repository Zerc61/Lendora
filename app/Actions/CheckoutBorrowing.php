<?php
// app/Actions/CheckoutBorrowing.php

namespace App\Actions;

use App\Enums\AssetStatus;
use App\Enums\BorrowingStatus;
use App\Enums\InspectionStage;
use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\AssetInspection;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutBorrowing
{
    /**
     * Digital Check-out / Handover (PDF 4H):
     * operator verifikasi + checklist kondisi awal → semua dalam satu transaction.
     */
    public function execute(Borrowing $borrowing, User $operator, array $itemsInput): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $operator, $itemsInput) {
            if ($borrowing->status !== BorrowingStatus::Approved) {
                throw ValidationException::withMessages(['status' => 'Peminjaman ini sudah diserah-terima atau dibatalkan.']);
            }

            foreach ($borrowing->items as $item) {
                $data = $itemsInput[$item->id] ?? null;

                if (! isset($data['condition_out'])) {
                    throw ValidationException::withMessages(['items' => "Kondisi awal unit {$item->asset->asset_code} wajib diisi."]);
                }

                $asset = Asset::whereKey($item->asset_id)->lockForUpdate()->first();

                // Inspeksi kondisi sebelum serah-terima (PDF 4H)
                AssetInspection::create([
                    'asset_id' => $asset->id,
                    'borrowing_id' => $borrowing->id,
                    'inspector_id' => $operator->id,
                    'stage' => InspectionStage::Checkout,
                    'condition' => $data['condition_out'],
                    'notes' => $data['notes_out'] ?? null,
                    'inspected_at' => now(),
                ]);

                $asset->update(['status' => AssetStatus::Borrowed]);

                $item->update([
                    'condition_out' => $data['condition_out'],
                    'notes_out' => $data['notes_out'] ?? null,
                ]);
            }

            $borrowing->update([
                'status' => BorrowingStatus::Borrowed,
                'checked_out_at' => now(),
                'checked_out_by' => $operator->id,
            ]);

            // Reservasi sumber → fulfilled (state machine bag. 9)
            if ($borrowing->reservation && $borrowing->reservation->status === ReservationStatus::Approved) {
                $borrowing->reservation->update(['status' => ReservationStatus::Fulfilled]);
            }

            return $borrowing;
        });
    }
}
