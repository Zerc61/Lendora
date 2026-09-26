<?php
// database/seeders/RolePermissionSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Asset & inventory (PDF bag. 3 & 4D)
            'asset.view', 'asset.create', 'asset.update', 'asset.delete',
            'category.manage', 'location.manage', 'asset-type.manage',
            // Reservation (PDF 4F)
            'reservation.view', 'reservation.create', 'reservation.approve', 'reservation.cancel',
            // Borrowing (PDF 4G, H, I)
            'borrowing.view', 'borrowing.create', 'borrowing.approve',
            'checkout.perform', 'checkin.perform',
            // Maintenance & issue (PDF 4J, 4K)
            'maintenance.view', 'maintenance.create', 'maintenance.work',
            'issue.view', 'issue.create', 'issue.resolve',
            // User & organisasi (PDF 4C)
            'user.manage', 'role.manage', 'organization.manage',
            // Audit & report (PDF 4L, 4M)
            'audit.view', 'report.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Admin/Asset Manager: semua kecuali manajemen organisasi/user/role
        $adminPermissions = array_diff($permissions, ['organization.manage', 'role.manage', 'user.manage']);

        $roles = [
            'admin' => $adminPermissions,
            'staff' => [
                'asset.view', 'reservation.view',
                'borrowing.view', 'borrowing.approve',
                'checkout.perform', 'checkin.perform',
                'maintenance.view', 'issue.view', 'issue.create',
            ],
            'technician' => [
                'asset.view', 'maintenance.view', 'maintenance.work', 'issue.view',
            ],
            'borrower' => [
                'asset.view',
                'reservation.view', 'reservation.create', 'reservation.cancel',
                'borrowing.view', 'borrowing.create',
                'issue.create',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }

        // super-admin: role harus ada agar hasRole('super-admin') true,
        // permission tidak butuh — dilewati via Gate::before
        Role::findOrCreate('super-admin', 'web');
    }
}