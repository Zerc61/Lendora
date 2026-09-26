<?php
// database/seeders/MasterDataSeeder.php
namespace Database\Seeders;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Category;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('code', 'SMK-NUS')->firstOrFail();

        // ===== Kategori (sesuai PDF hal. 1) =====
        $categories = collect([
            'Laptop', 'IoT Device', 'Headphone', 'Mouse', 'Kamera', 'Proyektor', 'Peralatan Laboratorium',
        ])->mapWithKeys(function (string $name) use ($org) {
            $category = Category::create([
                'organization_id' => $org->id,
                'name' => $name,
                'description' => "Kategori {$name}",
            ]);
            return [$name => $category];
        });

        // ===== Lokasi (dengan hierarki parent) =====
        $gudang = Location::create(['organization_id' => $org->id, 'name' => 'Gudang Utama']);
        $labKomputer = Location::create(['organization_id' => $org->id, 'name' => 'Lab Komputer 1', 'parent_id' => $gudang->id]);
        $labIot = Location::create(['organization_id' => $org->id, 'name' => 'Lab IoT', 'parent_id' => $gudang->id]);
        $ruangGuru = Location::create(['organization_id' => $org->id, 'name' => 'Ruang Guru']);

        // ===== Tipe Aset =====
        $types = collect([
            ['category' => 'Laptop', 'name' => 'Laptop Lenovo ThinkPad X13', 'brand' => 'Lenovo', 'model' => 'ThinkPad X13'],
            ['category' => 'Laptop', 'name' => 'Laptop Dell Latitude 5420', 'brand' => 'Dell', 'model' => 'Latitude 5420'],
            ['category' => 'IoT Device', 'name' => 'ESP32 DevKit V1', 'brand' => 'Espressif', 'model' => 'ESP32-WROOM-32'],
            ['category' => 'Headphone', 'name' => 'Sony WH-1000XM4', 'brand' => 'Sony', 'model' => 'WH-1000XM4'],
            ['category' => 'Mouse', 'name' => 'Logitech B100', 'brand' => 'Logitech', 'model' => 'B100'],
            ['category' => 'Kamera', 'name' => 'Sony Alpha A7 III', 'brand' => 'Sony', 'model' => 'ILCE-7M3'],
            ['category' => 'Proyektor', 'name' => 'Epson EB-X51', 'brand' => 'Epson', 'model' => 'EB-X51'],
            ['category' => 'Peralatan Laboratorium', 'name' => 'Oskiloskop Rigol DS1054Z', 'brand' => 'Rigol', 'model' => 'DS1054Z'],
        ])->mapWithKeys(function (array $t) use ($org, $categories) {
            $type = AssetType::create([
                'organization_id' => $org->id,
                'category_id' => $categories[$t['category']]->id,
                'name' => $t['name'],
                'brand' => $t['brand'],
                'model' => $t['model'],
            ]);
            return [$t['name'] => $type];
        });

        // ===== Unit Aset (semua variasi status untuk demo state machine) =====
        $units = [
            ['type' => 'Laptop Lenovo ThinkPad X13', 'code' => 'LND-LAP-0001', 'serial' => 'SN-LAP-0001', 'status' => AssetStatus::Available, 'condition' => AssetCondition::Good, 'location' => $gudang, 'price' => 15000000, 'warranty' => '2027-01-10'],
            ['type' => 'Laptop Lenovo ThinkPad X13', 'code' => 'LND-LAP-0002', 'serial' => 'SN-LAP-0002', 'status' => AssetStatus::Borrowed, 'condition' => AssetCondition::Good, 'location' => $labKomputer, 'price' => 15000000, 'warranty' => '2027-01-10'],
            ['type' => 'Laptop Dell Latitude 5420', 'code' => 'LND-LAP-0003', 'serial' => 'SN-LAP-0003', 'status' => AssetStatus::Available, 'condition' => AssetCondition::Fair, 'location' => $gudang, 'price' => 13500000, 'warranty' => null],
            ['type' => 'ESP32 DevKit V1', 'code' => 'LND-IOT-0001', 'serial' => 'SN-IOT-0001', 'status' => AssetStatus::Available, 'condition' => AssetCondition::Good, 'location' => $labIot, 'price' => 120000, 'warranty' => null],
            ['type' => 'ESP32 DevKit V1', 'code' => 'LND-IOT-0002', 'serial' => 'SN-IOT-0002', 'status' => AssetStatus::Reserved, 'condition' => AssetCondition::Good, 'location' => $labIot, 'price' => 120000, 'warranty' => null],
            ['type' => 'Sony WH-1000XM4', 'code' => 'LND-AUD-0001', 'serial' => 'SN-AUD-0001', 'status' => AssetStatus::Available, 'condition' => AssetCondition::Excellent, 'location' => $ruangGuru, 'price' => 4500000, 'warranty' => '2026-08-01'],
            ['type' => 'Logitech B100', 'code' => 'LND-MOU-0001', 'serial' => 'SN-MOU-0001', 'status' => AssetStatus::Available, 'condition' => AssetCondition::Good, 'location' => $gudang, 'price' => 150000, 'warranty' => null],
            ['type' => 'Sony Alpha A7 III', 'code' => 'LND-CAM-0001', 'serial' => 'SN-CAM-0001', 'status' => AssetStatus::Reserved, 'condition' => AssetCondition::Good, 'location' => $gudang, 'price' => 28000000, 'warranty' => '2026-03-15'],
            ['type' => 'Epson EB-X51', 'code' => 'LND-PRJ-0001', 'serial' => 'SN-PRJ-0001', 'status' => AssetStatus::Available, 'condition' => AssetCondition::Good, 'location' => $gudang, 'price' => 7500000, 'warranty' => '2026-06-20'],
            ['type' => 'Epson EB-X51', 'code' => 'LND-PRJ-0002', 'serial' => 'SN-PRJ-0002', 'status' => AssetStatus::Maintenance, 'condition' => AssetCondition::Poor, 'location' => $gudang, 'price' => 7500000, 'warranty' => '2026-06-20'],
            ['type' => 'Oskiloskop Rigol DS1054Z', 'code' => 'LND-LAB-0001', 'serial' => 'SN-LAB-0001', 'status' => AssetStatus::Damaged, 'condition' => AssetCondition::Broken, 'location' => $labIot, 'price' => 9000000, 'warranty' => null],
        ];

        foreach ($units as $u) {
            Asset::create([
                'organization_id' => $org->id,
                'asset_type_id' => $types[$u['type']]->id,
                'location_id' => $u['location']->id,
                'asset_code' => $u['code'],
                'serial_number' => $u['serial'],
                'status' => $u['status'],
                'condition' => $u['condition'],
                'purchase_date' => '2024-01-10',
                'purchase_price' => $u['price'],
                'warranty_until' => $u['warranty'],
            ]);
        }
    }
}