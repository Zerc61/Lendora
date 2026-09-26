<?php
// app/Actions/TransitionMaintenanceTicket.php

namespace App\Actions;

use App\Enums\AssetStatus;
use App\Enums\IssueType;
use App\Enums\MaintenanceStatus;
use App\Models\Asset;
use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Notifications\MaintenanceTicketAssignedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionMaintenanceTicket
{
    public function execute(MaintenanceTicket $ticket, User $actor, string $action, array $data = []): MaintenanceTicket
    {
        return DB::transaction(function () use ($ticket, $actor, $action, $data) {
            $isAdminLevel = $actor->can('maintenance.create');
            $oldStatus = $ticket->status;

            $target = match ($action) {
                'assign' => MaintenanceStatus::Assigned,
                'start' => MaintenanceStatus::InProgress,
                'wait_parts' => MaintenanceStatus::WaitingParts,
                'complete' => MaintenanceStatus::Completed,
                'verify' => MaintenanceStatus::Verified,
                'cancel' => MaintenanceStatus::Cancelled,
                default => throw ValidationException::withMessages(['action' => 'Aksi tidak dikenal.']),
            };

            if (! $oldStatus->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => "Transisi tidak valid: {$oldStatus->label()} → {$target->label()}.",
                ]);
            }

            // ── Hak akses per transisi ──
            $workActions = ['start', 'wait_parts', 'complete'];

            if (in_array($action, $workActions, true) && ! $isAdminLevel && $ticket->technician_id !== $actor->id) {
                throw ValidationException::withMessages(['action' => 'Hanya teknisi yang ditugaskan yang dapat melakukan aksi ini.']);
            }

            if (in_array($action, ['assign', 'verify', 'cancel'], true) && ! $isAdminLevel) {
                throw ValidationException::withMessages(['action' => 'Aksi ini hanya untuk admin/asset manager.']);
            }

            $updates = ['status' => $target];

            if ($action === 'assign') {
                $tech = User::find($data['technician_id'] ?? null);

                if (! $tech || ! $tech->hasRole('technician')) {
                    throw ValidationException::withMessages(['technician_id' => 'Pilih teknisi yang valid.']);
                }

                $updates['technician_id'] = $tech->id;

                $tech->notify(new MaintenanceTicketAssignedNotification($ticket->fresh()));
            }

            if ($action === 'complete') {
                $updates['completed_at'] = now();

                if (! empty($data['diagnosis'])) {
                    $updates['diagnosis'] = $data['diagnosis'];
                }
            }

            if ($action === 'verify') {
                $final = $data['final_condition'] ?? null;

                if (! $final) {
                    throw ValidationException::withMessages(['final_condition' => 'Kondisi akhir unit wajib dipilih saat verifikasi.']);
                }

                $asset = Asset::whereKey($ticket->asset_id)->lockForUpdate()->first();

                if ($asset && $asset->status === AssetStatus::Maintenance) {
                    // Perbaikan selesai & terverifikasi → unit kembali available (PDF 5.3)
                    $asset->update(['status' => AssetStatus::Available, 'condition' => $final]);
                }

                $updates['cost'] = (float) $ticket->logs()->sum('cost');
                $updates['completed_at'] = $ticket->completed_at ?? now();
            }

            if ($action === 'cancel') {
                $asset = Asset::whereKey($ticket->asset_id)->lockForUpdate()->first();

                if ($asset && $asset->status === AssetStatus::Maintenance) {
                    // Dibatalkan sebelum selesai: damage → tetap Damaged, selain itu → Available
                    $backTo = ($ticket->issue && $ticket->issue->type === IssueType::Damage)
                        ? AssetStatus::Damaged
                        : AssetStatus::Available;

                    $asset->update(['status' => $backTo]);
                }
            }

            $ticket->update($updates);

            // Setiap transisi tercatat sebagai riwayat pekerjaan (PDF 4J)
            $ticket->logs()->create([
                'user_id' => $actor->id,
                'action' => "Status: {$oldStatus->value} → {$target->value}",
                'note' => $data['diagnosis'] ?? null,
                'cost' => 0,
                'performed_at' => now(),
            ]);

            return $ticket;
        });
    }
}
