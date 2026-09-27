<?php

namespace Tests;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Category;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

/**
 * Dasar semua test.
 *
 * Isolasi database: `phpunit.xml` menunjuk ke `lendora_test` (MySQL terpisah),
 * bukan database dev `lendora`. `RefreshDatabase` me-migrasi ulang dari nol
 * setiap test, jadi data asli di `lendora` tidak pernah tersentuh — ini
 * pengganti SQLite :memory: yang tidak tersedia di environment ini.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected int $assetSeq = 0;

    protected static int $userSeq = 0;

    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function makeOrg(): Organization
    {
        return Organization::create([
            'name' => 'Organisasi Uji',
            'code' => 'UJI-'.Str::upper(Str::random(4)), // unik per pemanggilan
            'status' => 'active',
        ]);
    }

    protected function makeUser(string $role, ?Organization $org = null, ?string $email = null): User
    {
        if (\Spatie\Permission\Models\Role::count() === 0) {
            $this->seedRoles(); // auto-seed role/permission saat user pertama dibuat
        }

        self::$userSeq++;

        $user = User::create([
            'name' => ucfirst($role).' Uji',
            // Email unik per pemanggilan — beberapa helper membuat >1 user
            // dengan role yang sama dalam satu test.
            'email' => $email ?? (self::$userSeq.'-'.$role.'@test.local'),
            'password' => 'password',
            'organization_id' => $org?->id,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function makeAsset(Organization $org, string $status = 'available'): Asset
    {
        $this->assetSeq++;

        $category = Category::create([
            'organization_id' => $org->id,
            'name' => "Kategori Uji {$this->assetSeq}",
        ]);

        $type = AssetType::create([
            'organization_id' => $org->id,
            'category_id' => $category->id,
            'name' => "Tipe Uji {$this->assetSeq}",
        ]);

        return Asset::create([
            'organization_id' => $org->id,
            'asset_type_id' => $type->id,
            'asset_code' => sprintf('LND-TST-%04d', $this->assetSeq),
            'status' => $status,
            'condition' => 'good',
        ]);
    }
}
