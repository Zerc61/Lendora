<?php
// database/seeders/UserSeeder.php
namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('code', 'SMK-NUS')->firstOrFail();

        $users = [
            ['name' => 'Super Admin', 'email' => 'superadmin@lendora.test', 'role' => 'super-admin'],
            ['name' => 'Admin Lendora', 'email' => 'admin@lendora.test', 'role' => 'admin'],
            ['name' => 'Staff Perpus', 'email' => 'staff@lendora.test', 'role' => 'staff'],
            ['name' => 'Teknisi Lab', 'email' => 'teknisi@lendora.test', 'role' => 'technician'],
            ['name' => 'Budi Pratama', 'email' => 'budi@lendora.test', 'role' => 'borrower'],
            ['name' => 'Anjar', 'email' => 'anjar@lendora.test', 'role' => 'borrower'],
            ['name' => 'Alfian', 'email' => 'alfian@lendora.test', 'role' => 'borrower'],
        ];

        foreach ($users as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'organization_id' => $org->id,
                    'name' => $u['name'],
                    'password' => 'password',
                    'status' => UserStatus::Active,
                ],
            );
            $user->syncRoles([$u['role']]);
        }
    }
}
