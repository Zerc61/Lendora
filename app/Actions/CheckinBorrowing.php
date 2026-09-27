<?php
// app/Actions/CheckinBorrowing.php

namespace App\Actions;

use App\Enums\AssetStatus;
use App\Enums\BorrowingStatus;
use App\Enums\InspectionStage;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Models\Asset;
use App\Models\AssetInspection;
use App\Models\Borrowing;
use App\Models\Issue;
use App\Models\User;
use App\Services\RecordCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckinBorrowing
{
    public function __construct(private RecordCodeGenerator $codes) {}

    /**
     * Digital Check-in / Return (PDF 4I + alur 5.3/5.4):
     * inspeksi kondisi kembali; kerusakan/hilang → Issue otomatis + asset keluar dari availability.
     */
    public function execute(Borrowing $borrowing, User $operator, array $itemsInput, ?string $generalNotes = null): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $operator, $itemsInput, $generalNotes) {
            // Borrowed ATAU Overdue (hasil scheduler) tetap bisa di-check-in
            if (! in_array($borrowing->status, [BorrowingStatus::Borrowed, BorrowingStatus::Overdue], true)) {
                throw ValidationException::withMessages(['status' => 'Peminjaman ini tidak sedang berjalan.']);
            }

            foreach ($borrowing->items as $item) {
                $data = $itemsInput[$item->id] ?? null;

                if (! isset($data['condition_in'], $data['outcome'])) {
                    throw ValidationException::withMessages(['items' => "Hasil pemeriksaan unit {$item->asset->asset_code} wajib diisi lengkap."]);
                }

                $asset = Asset::whereKey($item->asset_id)->lockForUpdate()->first();

                // Inspeksi kondisi setelah kembali (PDF 4I)
                AssetInspection::create([
                    'asset_id' => $asset->id,
                    'borrowing_id' => $borrowing->id,
                    'inspector_id' => $operator->id,
                    'stage' => InspectionStage::CheckIn,
                    'condition' => $data['condition_in'],
                    'checklist' => ['outcome' => $data['outcome']],
                    'notes' => $data['notes_in'] ?? null,
                    'inspected_at' => now(),
                ]);

                $item->update([
                    'condition_in' => $data['condition_in'],
                    'notes_in' => $data['notes_in'] ?? null,
                ]);

                match ($data['outcome']) {
                    'ok' => $this->releaseAsset($asset, $borrowing),
                    'damaged' => $this->markProblem($asset, $borrowing, $operator, $item, IssueType::Damage, $data['notes_in'] ?? null),
                    'lost' => $this->markProblem($asset, $borrowing, $operator, $item, IssueType::Loss, $data['notes_in'] ?? null),
                };
            }

            $borrowing->update([
                'status' => BorrowingStatus::Returned,
                'returned_at' => now(),
                'checked_in_by' => $operator->id,
                'checkin_notes' => $generalNotes,
            ]);

            return $borrowing;
        });
    }

    /**
     * Unit kembali Available — TAPI jika ada borrowing lain yang menunggu (reservasi
     * approved berikutnya), status kembali ke Reserved. Edge case multi-reservasi beruntun.
     */
    private function releaseAsset(Asset $asset, Borrowing $current): void
    {
        $hasActiveOther = Borrowing::whereHas('items', fn ($q) => $q->where('asset_id', $asset->id))
            ->where('status', BorrowingStatus::Borrowed->value)
            ->whereKeyNot($current->id)
            ->lockForUpdate()
            ->exists();

        if ($hasActiveOther) {
            return; // masih dipegang transaksi lain — biarkan Borrowed
        }

        $hasPendingOther = Borrowing::whereHas('items', fn ($q) => $q->where('asset_id', $asset->id))
            // Approved = disetujui tapi unit belum diserahkan. Pending ikut disaring
            // untuk baris lama. Selama masih ada, unit tidak boleh Available.
            ->whereIn('status', [BorrowingStatus::Approved->value, BorrowingStatus::Pending->value])
            ->whereKeyNot($current->id)
            ->lockForUpdate()
            ->exists();

        $asset->update(['status' => $hasPendingOther ? AssetStatus::Reserved : AssetStatus::Available]);
    }

    /** Rusak/hilang → asset keluar dari availability + Issue tercatat (PDF 5.3, 5.4) */
    private function markProblem(Asset $asset, Borrowing $borrowing, User $operator, $item, IssueType $type, ?string $notes): void
    {
        $asset->update([
            'status' => $type === IssueType::Loss ? AssetStatus::Lost : AssetStatus::Damaged,
        ]);

        Issue::create([
            'code' => $this->codes->next('ISS', 'issues'),
            'organization_id' => $borrowing->organization_id,
            'asset_id' => $asset->id,
            'borrowing_id' => $borrowing->id,
            'reported_by' => $operator->id,
            'type' => $type,
            'severity' => $type === IssueType::Loss ? 'high' : 'medium',
            'status' => IssueStatus::Open,
            'description' => $notes ?: ("Terdeteksi saat check-in peminjaman {$borrowing->code} (unit {$asset->asset_code})."),
        ]);
    }
}
