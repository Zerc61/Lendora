<?php
// app/Services/ReservationConflictChecker.php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\ReservationItem;
use Illuminate\Support\Carbon;

class ReservationConflictChecker
{
    /**
     * Cek apakah sebuah unit sudah direservasi (pending/approved)
     * pada rentang waktu yang overlap. (PDF 5.2)
     *
     * PENTING: panggil di dalam DB::transaction SETELAH asset di-lock (lockForUpdate)
     * agar request bersamaan pada aset yang sama diserialisasi → tidak ada double booking.
     */
    public function conflictForAsset(Asset $asset, Carbon $start, Carbon $end, ?int $ignoreReservationId = null): bool
    {
        return ReservationItem::query()
            ->where('asset_id', $asset->id)
            ->whereHas('reservation', fn ($q) => $q
                ->whereIn('status', [ReservationStatus::Pending->value, ReservationStatus::Approved->value])
                ->when($ignoreReservationId, fn ($w) => $w->whereKeyNot($ignoreReservationId))
                ->overlapping($start->toDateTimeString(), $end->toDateTimeString()))
            ->exists();
    }
}
