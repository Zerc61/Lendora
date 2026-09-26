<?php
// app/Actions/CreateReservation.php

namespace App\Actions;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Reservation;
use App\Models\User;
use App\Services\RecordCodeGenerator;
use App\Services\ReservationConflictChecker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateReservation
{
    public function __construct(
        private ReservationConflictChecker $conflictChecker,
        private RecordCodeGenerator $codes,
    ) {}

    public function execute(User $user, Carbon $start, Carbon $end, string $purpose, array $assetIds): Reservation
    {
        if (! $user->organization_id) {
            throw ValidationException::withMessages(['asset_ids' => 'Akun Anda tidak terhubung ke organisasi mana pun.']);
        }

        // ── Transaction + lock = anti double booking (PDF 5.2 & prinsip backend bag. 7) ──
        return DB::transaction(function () use ($user, $start, $end, $purpose, $assetIds) {
            $assets = Asset::whereIn('id', $assetIds)->lockForUpdate()->get();

            if ($assets->count() !== count(array_unique($assetIds))) {
                throw ValidationException::withMessages(['asset_ids' => 'Ada aset yang tidak ditemukan.']);
            }

            foreach ($assets as $asset) {
                // borrowed/maintenance/damaged/lost/retired tidak boleh direserve (PDF bag. 13)
                if (! in_array($asset->status, [AssetStatus::Available, AssetStatus::Reserved], true)) {
                    throw ValidationException::withMessages([
                        'asset_ids' => "Aset {$asset->asset_code} sedang {$asset->status->label()} dan tidak dapat direserve.",
                    ]);
                }

                if ($this->conflictChecker->conflictForAsset($asset, $start, $end)) {
                    throw ValidationException::withMessages([
                        'asset_ids' => "Aset {$asset->asset_code} sudah direservasi pada rentang waktu tersebut. Pilih waktu/unit lain.",
                    ]);
                }
            }

            $reservation = Reservation::create([
                'code' => $this->codes->next('RSV', 'reservations'),
                'organization_id' => $user->organization_id,
                'user_id' => $user->id,
                'start_at' => $start,
                'end_at' => $end,
                'status' => 'pending',
                'purpose' => $purpose,
            ]);

            foreach ($assets as $asset) {
                $reservation->items()->create(['asset_id' => $asset->id, 'quantity' => 1]);
            }

            return $reservation;
        });
    }
}
