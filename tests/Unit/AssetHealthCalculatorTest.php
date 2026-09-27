<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Category;
use App\Services\AssetHealthCalculator;
use Tests\TestCase;

/**
 * Health score 0–100 (PDF 4K) dengan aturan transparan:
 *   kondisi fisik   : broken -45, poor -25, fair -12
 *   status lifecycle: damaged -30, maintenance -15, lost/retired -100
 *   usia            : -4/tahun, maks -16
 *   penggunaan      : -0.5/peminjaman, maks -10
 *   issue terbuka   : -8/issue, maks -16
 *   riwayat maintenance: -3/tiket, maks -12
 *   garansi habis   : -4
 */
class AssetHealthCalculatorTest extends TestCase
{
    private function makeHealthAsset($org, array $attributes = []): Asset
    {
        $category = Category::create(['organization_id' => $org->id, 'name' => 'Kat-'.uniqid()]);
        $type = AssetType::create([
            'organization_id' => $org->id,
            'category_id' => $category->id,
            'name' => 'Tipe-'.uniqid(),
        ]);

        return Asset::create([
            'organization_id' => $org->id,
            'asset_type_id' => $type->id,
            'asset_code' => 'LND-HLT-'.substr(md5(uniqid('', true)), 0, 8),
            'status' => 'available',
            'condition' => 'excellent',
            ...$attributes,
        ]);
    }

    public function test_healthy_new_asset_scores_100(): void
    {
        $org = $this->makeOrg();
        $asset = $this->makeHealthAsset($org, [
            'purchase_date' => now()->subMonths(6),
            'warranty_until' => now()->addYear(),
        ]);

        $health = app(AssetHealthCalculator::class)->calculate($asset);

        $this->assertSame(100, $health['score']);
        $this->assertSame('Sehat', $health['label']);
    }

    public function test_broken_condition_reduces_score(): void
    {
        $org = $this->makeOrg();
        $asset = $this->makeHealthAsset($org, ['condition' => 'broken']);

        $health = app(AssetHealthCalculator::class)->calculate($asset);

        $this->assertSame(55, $health['score']); // 100 - 45 (aturan transparan PDF 4K)
    }

    public function test_age_penalty_is_capped(): void
    {
        $org = $this->makeOrg();
        $asset = $this->makeHealthAsset($org, ['purchase_date' => now()->subYears(10)]);

        $health = app(AssetHealthCalculator::class)->calculate($asset);

        $this->assertSame(84, $health['score']); // 100 - 16 (maks usia)
    }

    public function test_retired_asset_scores_zero(): void
    {
        $org = $this->makeOrg();
        $asset = $this->makeHealthAsset($org, ['status' => 'retired']);

        $health = app(AssetHealthCalculator::class)->calculate($asset);

        $this->assertSame(0, $health['score']);
        $this->assertSame('Kritis', $health['label']);
    }

    public function test_garansi_habis_mengurangi_skor(): void
    {
        $org = $this->makeOrg();
        $asset = $this->makeHealthAsset($org, ['warranty_until' => now()->subMonth()]);

        $health = app(AssetHealthCalculator::class)->calculate($asset);

        $this->assertSame(96, $health['score']);
    }

    public function test_breakdown_menjelaskan_pengurangan(): void
    {
        $org = $this->makeOrg();
        $asset = $this->makeHealthAsset($org, [
            'condition' => 'poor',
            'warranty_until' => now()->subMonth(),
        ]);

        $health = app(AssetHealthCalculator::class)->calculate($asset);

        $this->assertSame(71, $health['score']); // 100 - 25 (kondisi) - 4 (garansi)
        $this->assertContains('Kondisi fisik (poor): -25', $health['breakdown']);
        $this->assertContains('Garansi habis: -4', $health['breakdown']);
    }
}
