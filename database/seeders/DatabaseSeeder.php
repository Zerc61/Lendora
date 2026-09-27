<?php
// database/seeders/DatabaseSeeder.php  (GANTI total)
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            RolePermissionSeeder::class,
            SchoolStructureSeeder::class,
            UserSeeder::class,
            MasterDataSeeder::class,
        ]);
    }
}