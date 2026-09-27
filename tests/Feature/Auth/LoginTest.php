<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_user_can_login_with_valid_credentials(): void
    {
        $org = $this->makeOrg();
        $user = $this->makeUser('borrower', $org);

        $response = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_login_rejects_wrong_password(): void
    {
        $org = $this->makeOrg();
        $user = $this->makeUser('borrower', $org);

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $org = $this->makeOrg();
        $user = $this->makeUser('borrower', $org);
        $user->update(['status' => UserStatus::Inactive]);

        $this->from(route('login'))->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_deactivated_user_is_logged_out_automatically(): void
    {
        $org = $this->makeOrg();
        $user = $this->makeUser('borrower', $org);

        $this->actingAs($user);
        $user->update(['status' => UserStatus::Inactive]); // admin menonaktifkan akun

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
