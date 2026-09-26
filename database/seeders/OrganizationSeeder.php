<?php
// database/seeders/OrganizationSeeder.php
namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::firstOrCreate(
            ['code' => 'SMK-NUS'],
            [
                'name' => 'SMK Nusantara',
                'status' => 'active',
                'description' => 'Organisasi demo Lendora — sekolah/laboratorium',
            ]
        );
    }
}