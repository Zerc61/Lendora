<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use App\Support\Navigation;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test untuk redesign UI.
 *
 * Menjamin: tiap peran memakai shell yang berbeda, dan setiap item menu
 * milik peran tersebut benar-benar bisa dibuka (tidak ada link mati atau
 * view yang gagal render).
 */
class ShellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);
    }

    public static function roles(): array
    {
        return [
            'super-admin' => ['superadmin@lendora.test', 'shell--admin'],
            'admin'       => ['admin@lendora.test', 'shell--admin'],
            'staff'       => ['staff@lendora.test', 'shell--staff'],
            'technician'  => ['teknisi@lendora.test', 'shell--technician'],
            'borrower'    => ['budi@lendora.test', 'shell--borrower'],
        ];
    }

    private function loginAs(string $email): User
    {
        $user = User::where('email', $email)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_setiap_peran_dapat_halaman_login(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Lendora', false)
            ->assertSee('assets/lendora.css', false);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('roles')]
    public function test_setiap_peran_memakai_shellnya_sendiri(string $email, string $shellClass): void
    {
        $this->loginAs($email);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="'.$shellClass.'"', false)
            ->assertSee('assets/lendora.js', false);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('roles')]
    public function test_semua_item_menu_peran_dapat_dibuka(string $email, string $shellClass): void
    {
        $user = $this->loginAs($email);

        $items = collect(Navigation::for($user))->flatMap(fn (array $group) => $group['items']);

        $this->assertNotEmpty($items, "Menu peran {$user->primaryRole()} kosong.");

        foreach ($items as $item) {
            $this->get($item['url'])
                ->assertSuccessful();
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('roles')]
    public function test_halaman_umum_per_role(string $email, string $shellClass): void
    {
        $this->loginAs($email);

        foreach (['notifications.index', 'profile.index'] as $route) {
            $this->get(route($route))->assertSuccessful();
        }

        // Area borrower hanya untuk perimeter dengan permission reservation.create.
        if (in_array($email, ['borrower', 'admin', 'super-admin'], true)
            || User::where('email', $email)->firstOrFail()->can('reservation.create')) {
            foreach (['my.reservations.index', 'my.borrowings.index'] as $route) {
                $this->get(route($route))->assertSuccessful();
            }
        }
    }

    public function test_navigasi_hanya_berisi_menu_yang_diperbolehkan(): void
    {
        $admin = $this->loginAs('admin@lendora.test');
        $adminRoutes = collect(Navigation::for($admin))->flatMap(fn ($g) => $g['items'])->pluck('route');

        // admin tidak memiliki user.manage / organization.manage
        $this->assertFalse($adminRoutes->contains('admin.users.index'));
        $this->assertFalse($adminRoutes->contains('admin.organizations.index'));

        $borrower = $this->loginAs('budi@lendora.test');
        $borrowerRoutes = collect(Navigation::for($borrower))->flatMap(fn ($g) => $g['items'])->pluck('route');

        $this->assertFalse($borrowerRoutes->contains('admin.reports.index'));
        $this->assertFalse($borrowerRoutes->contains('admin.tickets.index'));
    }

    public function test_halaman_produk_bisa_dibuka_setelah_login(): void
    {
        // /p/{assetCode} ada di dalam group auth+active: halaman produk bukan publik,
        // QR hanya mempersingkat akses (policy view tetap berlaku).
        $asset = Asset::whereHas('attachments', fn ($q) => $q->where('is_cover', true))->first()
            ?? Asset::firstOrFail();

        $this->loginAs('admin@lendora.test');

        $this->get('/p/'.$asset->asset_code)
            ->assertOk()
            ->assertSee($asset->asset_code, false);
    }
}
