<?php

namespace Tests\Feature\Authorization;

use Tests\TestCase;

/**
 * Kunci authorization sesuai PDF bag. 3.
 *
 * Yang paling mudah salah: `user.manage`, `role.manage`, dan
 * `organization.manage` adalah **Super Admin only** — admin biasa TIDAK
 * boleh membuka manajemen pengguna. Test ini mengunci perilaku tersebut.
 */
class RoleAccessTest extends TestCase
{
    public function test_borrower_cannot_access_user_management(): void
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);

        $this->actingAs($borrower)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_user_management(): void
    {
        // PDF bag. 3: user/role/organization = Super Admin only
        $org = $this->makeOrg();
        $admin = $this->makeUser('admin', $org);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_access_user_management(): void
    {
        $this->makeOrg();
        $super = $this->makeUser('super-admin');

        $this->actingAs($super)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_staff_cannot_access_category_management(): void
    {
        $org = $this->makeOrg();
        $staff = $this->makeUser('staff', $org);

        $this->actingAs($staff)
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }

    public function test_super_admin_bypasses_all_permissions(): void
    {
        $this->makeOrg();
        $super = $this->makeUser('super-admin');

        $this->actingAs($super)
            ->get(route('admin.organizations.index'))
            ->assertOk();
    }

    public function test_borrower_can_browse_assets_but_not_create(): void
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);

        $this->actingAs($borrower)
            ->get(route('admin.assets.index'))
            ->assertOk();

        $this->actingAs($borrower)
            ->post(route('admin.assets.store'), [])
            ->assertForbidden();
    }

    public function test_staff_cannot_view_audit_logs(): void
    {
        $org = $this->makeOrg();
        $staff = $this->makeUser('staff', $org);

        $this->actingAs($staff)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_izin_yang_gagal_konsisten_403(): void
    {
        // Regression: Spatie UnauthorizedException harus dirender sebagai 403,
        // bukan 500. Dijamin lewat withExceptions() di bootstrap/app.php.
        $org = $this->makeOrg();
        $staff = $this->makeUser('staff', $org);

        $this->actingAs($staff)
            ->get(route('admin.users.index'))
            ->assertForbidden()
            ->assertSee('tidak memiliki izin', false);
    }
}
