<?php
// app/Actions/CreateMaintenanceTicket.php

namespace App\Actions;

use App\Enums\AssetStatus;
use App\Enums\MaintenanceStatus;
use App\Models\Asset;
use App\Models\Issue;
use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Services\RecordCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateMaintenanceTicket
{
    public function __construct(private RecordCodeGenerator $codes) {}

    public function execute(User $creator, array $data): MaintenanceTicket
    {
        return DB::transaction(function () use ($creator, $data) {
            $asset = Asset::whereKey($data['asset_id'])->lockForUpdate()->first();

            if (! $asset) {
                throw ValidationException::withMessages(['asset_id' => 'Aset tidak ditemukan.']);
            }

            // Transparan: tiket hanya untuk unit available/damaged
            if (! in_array($asset->status, [AssetStatus::Available, AssetStatus::Damaged], true)) {
                throw ValidationException::withMessages([
                    'asset_id' => "Tiket hanya dapat dibuat untuk unit Tersedia atau Rusak. Unit ini sedang: {$asset->status->label()}.",
                ]);
            }

            $issue = null;
            if (! empty($data['issue_id'])) {
                $issue = Issue::findOrFail($data['issue_id']);

                if (MaintenanceTicket::where('issue_id', $issue->id)->exists()) {
                    throw ValidationException::withMessages(['issue_id' => "Issue {$issue->code} sudah memiliki tiket maintenance."]);
                }
            }

            $ticket = MaintenanceTicket::create([
                'code' => $this->codes->next('MTC', 'maintenance_tickets'),
                'organization_id' => $creator->organization_id ?? $asset->organization_id,
                'asset_id' => $asset->id,
                'issue_id' => $issue?->id,
                'reported_by' => $creator->id,
                'type' => $data['type'],
                'priority' => $data['priority'],
                'status' => MaintenanceStatus::Open,
                'description' => $data['description'],
                'scheduled_at' => $data['scheduled_at'] ?? null,
            ]);

            // Kunci unit dari peminjaman selama maintenance (PDF 4J)
            if ($asset->status->canTransitionTo(AssetStatus::Maintenance)) {
                $asset->update(['status' => AssetStatus::Maintenance]);
            }

            $ticket->logs()->create([
                'user_id' => $creator->id,
                'action' => 'Tiket dibuat',
                'note' => $issue ? "Terhubung ke issue {$issue->code}" : null,
                'cost' => 0,
                'performed_at' => now(),
            ]);

            return $ticket;
        });
    }
}
