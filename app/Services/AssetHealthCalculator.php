<?php
// app/Services/AssetHealthCalculator.php

namespace App\Services;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\IssueStatus;
use App\Models\Asset;

class AssetHealthCalculator
{
    /**
     * Skor 0–100 dengan rincian aturan yang transparan (PDF 4K).
     * @return array{score: int, label: string, color: string, breakdown: string[]}
     */
    public function calculate(Asset $asset): array
    {
        $breakdown = [];
        $score = 100;

        // 1. Kondisi fisik terakhir
        $conditionPenalty = match ($asset->condition) {
            AssetCondition::Broken => 45,
            AssetCondition::Poor => 25,
            AssetCondition::Fair => 12,
            default => 0,
        };
        if ($conditionPenalty > 0) {
            $score -= $conditionPenalty;
            $breakdown[] = "Kondisi fisik ({$asset->condition->value}): -{$conditionPenalty}";
        }

        // 2. Status lifecycle saat ini
        $statusPenalty = match ($asset->status) {
            AssetStatus::Damaged => 30,
            AssetStatus::Maintenance => 15,
            AssetStatus::Lost, AssetStatus::Retired => 100,
            default => 0,
        };
        if ($statusPenalty > 0) {
            $score -= $statusPenalty;
            $breakdown[] = "Status ({$asset->status->label()}): -{$statusPenalty}";
        }

        // 3. Usia aset (PDF 4K): -4/tahun, maks -16
        if ($asset->purchase_date) {
            $years = (int) floor($asset->purchase_date->diffInYears(now()));
            $agePenalty = min($years * 4, 16);
            if ($agePenalty > 0) {
                $score -= $agePenalty;
                $breakdown[] = "Usia aset ({$years} tahun): -{$agePenalty}";
            }
        }

        // 4. Intensitas peminjaman: -0.5 per peminjaman, maks -10
        $borrowCount = $asset->borrowingItems()->count();
        $usagePenalty = min((int) floor($borrowCount * 0.5), 10);
        if ($usagePenalty > 0) {
            $score -= $usagePenalty;
            $breakdown[] = "Jumlah peminjaman ({$borrowCount}x): -{$usagePenalty}";
        }

        // 5. Incident terbuka: -8 per issue open/investigating, maks -16
        $openIssues = $asset->issues()
            ->whereIn('status', [IssueStatus::Open->value, IssueStatus::Investigating->value])
            ->count();
        $issuePenalty = min($openIssues * 8, 16);
        if ($issuePenalty > 0) {
            $score -= $issuePenalty;
            $breakdown[] = "Issue terbuka ({$openIssues}): -{$issuePenalty}";
        }

        // 6. Riwayat maintenance (non-cancelled): -3 per tiket, maks -12
        $ticketCount = $asset->maintenanceTickets()->whereNotIn('status', ['cancelled'])->count();
        $ticketPenalty = min($ticketCount * 3, 12);
        if ($ticketPenalty > 0) {
            $score -= $ticketPenalty;
            $breakdown[] = "Riwayat maintenance ({$ticketCount} tiket): -{$ticketPenalty}";
        }

        // 7. Garansi habis: -4
        if ($asset->warranty_until && $asset->warranty_until->isPast()) {
            $score -= 4;
            $breakdown[] = 'Garansi habis: -4';
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'label' => $score >= 80 ? 'Sehat' : ($score >= 60 ? 'Cukup' : ($score >= 40 ? 'Perlu Perhatian' : 'Kritis')),
            'color' => $score >= 80 ? '#22c55e' : ($score >= 60 ? '#eab308' : ($score >= 40 ? '#f97316' : '#ef4444')),
            'breakdown' => $breakdown ?: ['Tidak ada pengurangan — semua metrik baik.'],
        ];
    }
}
