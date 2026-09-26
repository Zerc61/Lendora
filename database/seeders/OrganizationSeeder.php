<?php
// database/seeders/OrganizationSeeder.php
namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::create([
            'name' => 'SMK Nusantara',
            'code' => 'SMK-NUS',
            'status' => 'active',
            'description' => 'Organisasi demo Lendora — sekolah/laboratorium',
        ]);
    }
}