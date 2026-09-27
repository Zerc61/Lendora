<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Category;
use App\Services\AssetCodeGenerator;
use Tests\TestCase;

/**
 * Generator kode aset: prefix dari nama kategori + nomor urut 4 digit.
 *
 * Aturan penting (PDF 4D): kode **tidak pernah dipakai ulang**, termasuk oleh
 * aset yang sudah di-soft-delete — karena kode adalah identitas historis.
 */
class AssetCodeGeneratorTest extends TestCase
{
    private function makeType($org, string $categoryName): AssetType
    {
        $category = Category::create(['organization_id' => $org->id, 'name' => $categoryName]);

        return AssetType::create([
            'organization_id' => $org->id,
            'category_id' => $category->id,
            'name' => $categoryName.' Tipe Uji',
        ]);
    }

    public function test_generates_prefixed_incrementing_code(): void
    {
        $org = $this->makeOrg();
        $type = $this->makeType($org, 'Kamera');

        $code = app(AssetCodeGenerator::class)->generate($type);

        $this->assertStringStartsWith('LND-KAM-', $code);
        $this->assertStringEndsWith('-0001', $code);
    }

    public function test_nomor_urut_bertambah(): void
    {
        $org = $this->makeOrg();
        $type = $this->makeType($org, 'Proyektor');
        $generator = app(AssetCodeGenerator::class);

        $first = $generator->generate($type);

        // Generator menghitung dari baris yang benar-benar ada, jadi kode
        // pertama harus disimpan dulu sebelum nomor urutnya naik.
        Asset::create([
            'organization_id' => $org->id,
            'asset_type_id' => $type->id,
            'asset_code' => $first,
            'status' => 'available',
            'condition' => 'good',
        ]);

        $second = $generator->generate($type);

        $this->assertNotEquals($first, $second);
        $this->assertStringEndsWith('-0002', $second);
    }

    public function test_does_not_reuse_soft_deleted_code(): void
    {
        $org = $this->makeOrg();
        $type = $this->makeType($org, 'Proyektor');
        $generator = app(AssetCodeGenerator::class);

        $first = $generator->generate($type);

        Asset::create([
            'organization_id' => $org->id,
            'asset_type_id' => $type->id,
            'asset_code' => $first,
            'status' => 'available',
            'condition' => 'good',
        ])->delete(); // soft delete

        $second = $generator->generate($type);

        $this->assertNotEquals($first, $second); // kode tak pernah dipakai ulang
    }

    public function test_prefix_berbeda_per_kategori(): void
    {
        $org = $this->makeOrg();
        $generator = app(AssetCodeGenerator::class);

        $kamera = $generator->generate($this->makeType($org, 'Kamera'));
        $laptop = $generator->generate($this->makeType($org, 'Laptop'));

        $this->assertStringStartsWith('LND-KAM-', $kamera);
        $this->assertStringStartsWith('LND-LAP-', $laptop);
    }
}
