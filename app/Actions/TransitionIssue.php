<?php
// app/Actions/TransitionIssue.php

namespace App\Actions;

use App\Enums\AssetStatus;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Models\Asset;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionIssue
{
    public function execute(Issue $issue, User $actor, string $action, ?string $resolution = null, ?string $lossOutcome = null): Issue
    {
        return DB::transaction(function () use ($issue, $actor, $action, $resolution, $lossOutcome) {
            $target = match ($action) {
                'investigate' => IssueStatus::Investigating,
                'resolve' => IssueStatus::Resolved,
                'reject' => IssueStatus::Rejected,
                'close' => IssueStatus::Closed,
                default => throw ValidationException::withMessages(['action' => 'Aksi tidak dikenal.']),
            };

            if (! $issue->status->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => "Transisi tidak valid: {$issue->status->label()} → {$target->label()}.",
                ]);
            }

            $isFinal = in_array($target, [IssueStatus::Resolved, IssueStatus::Rejected, IssueStatus::Closed], true);

            if ($isFinal && empty($resolution)) {
                throw ValidationException::withMessages(['resolution' => 'Catatan hasil wajib diisi untuk menutup issue.']);
            }

            // PDF 5.4: hasil investigasi alat hilang → aset recovered ATAU retired
            if ($target === IssueStatus::Resolved && $issue->type === IssueType::Loss) {
                if (! in_array($lossOutcome, ['recovered', 'retired'], true)) {
                    throw ValidationException::withMessages([
                        'loss_outcome' => 'Pilih hasil investigasi: pulih (recovered) atau pensiun (retired).',
                    ]);
                }

                if ($issue->asset_id) {
                    $asset = Asset::whereKey($issue->asset_id)->lockForUpdate()->first();

                    if ($asset && $asset->status === AssetStatus::Lost) {
                        $asset->update([
                            'status' => $lossOutcome === 'recovered' ? AssetStatus::Available : AssetStatus::Retired,
                        ]);
                    }
                }
            }

            $issue->update([
                'status' => $target,
                'resolution' => $resolution,
                'resolved_by' => $isFinal ? $actor->id : $issue->resolved_by,
                'resolved_at' => $isFinal ? now() : $issue->resolved_at,
            ]);

            return $issue;
        });
    }
}
