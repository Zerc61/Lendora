<?php

namespace Tests\Feature\Security;

use App\Actions\ApproveReservation;
use App\Actions\CreateReservation;
use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use App\Models\User;
use Tests\TestCase;

/**
 * Security review (PDF bag. 12) — bagian yang bisa diotomasi.
 *
 * Temuan saat review: `<x-stat>` merender `hint` dengan `{!! !!}` (raw) karena
 * hint sengaja boleh berisi `<b>`. Dua call site menyisipkan nama peminjam —
 * input user — tanpa escape → XSS tersimpan. Test ini menguncinya.
 */
class SecurityTest extends TestCase
{
    public function test_nama_peminjam_dihindari_dari_xss(): void
    {
        $org = $this->makeOrg();
        $payload = '<img src=x onerror="alert(1)">';

        $borrower = $this->makeUser('borrower', $org, 'korban@test.local');
        $borrower->update(['name' => $payload]);

        $admin = $this->makeUser('admin', $org);
        $asset = $this->makeAsset($org);

        $reservation = app(CreateReservation::class)->execute(
            $borrower,
            now()->addDay()->setTime(8, 0),
            now()->addDay()->setTime(10, 0),
            'Peminjaman untuk praktikum',
            [$asset->id],
        );

        // Antrean check-out menampilkan borrowing berstatus Approved — hint-nya
        // menyisipkan nama peminjam.
        app(ApproveReservation::class)->execute($reservation, $admin);

        $response = $this->actingAs($admin)->get(route('admin.checkout.index'))->assertOk();

        $this->assertStringNotContainsString(
            $payload,
            $response->getContent(),
            'Nama peminjam harus di-escape — jangan pernah masuk mentah ke HTML.'
        );
        // Versi escapednya boleh tetap tampil sebagai teks.
        $this->assertStringContainsString(
            htmlspecialchars($payload, ENT_QUOTES, 'UTF-8'),
            $response->getContent()
        );
    }

    public function test_izin_yang_gagal_konsisten_403(): void
    {
        // Spatie UnauthorizedException harus dirender 403, bukan 500.
        // Dijamin lewat withExceptions() di bootstrap/app.php.
        $org = $this->makeOrg();
        $staff = $this->makeUser('staff', $org);

        $this->actingAs($staff)
            ->get(route('admin.users.index'))
            ->assertForbidden()
            ->assertSee('tidak memiliki izin', false);
    }

    public function test_password_tidak_pernah_plaintext(): void
    {
        $org = $this->makeOrg();
        $user = $this->makeUser('borrower', $org);

        $this->assertNotSame('password', $user->getAuthPassword());
        $this->assertTrue(password_verify('password', $user->getAuthPassword()));
    }

    public function test_form_sensitif_memuat_token_csrf(): void
    {
        // Laravel mematikan VerifyCsrfToken saat runningUnitTests(), jadi
        // penolakan 419 tidak bisa diuji lewat HTTP. Yang bisa dikunci: form
        // benar-benar memuat token CSRF (kalau lupa @csrf, token tidak ada).
        $org = $this->makeOrg();
        $user = $this->makeUser('borrower', $org);

        $this->get(route('login'))->assertOk()->assertSee('_token', false);

        $this->actingAs($user)
            ->get(route('my.reservations.create'))
            ->assertOk()
            ->assertSee('_token', false);
    }
}
