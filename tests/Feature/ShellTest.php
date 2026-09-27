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

    /* ── Guardrail CSS ────────────────────────────────────────────────────
       Audit geometri headless (Puppeteer) dulu menemukan dua bug yang
       merusak semua halaman: ikon tanpa ukuran dasar (SVG inline jatuh ke
       300×150) dan blok tingkat atas yang berdempet tanpa jarak. Keduanya
       tidak terlihat di test DOM, jadi dijaga lewat isi stylesheet.       */

    public function test_stylesheet_memiliki_geometri_dasar(): void
    {
        $css = file_get_contents(public_path('assets/lendora.css'));

        $this->assertMatchesRegularExpression(
            '/svg\.icon\s*\{[^}]*width:/',
            $css,
            'Ikon harus punya ukuran dasar; tanpa itu <x-icon> meledak jadi 300×150.'
        );
        $this->assertMatchesRegularExpression(
            '/\.app__body\s*>\s*\*\s*\+\s*\*\s*\{[^}]*margin-top:/',
            $css,
            'Blok tingkat atas di .app__body butuh ritme vertikal (kartu berdempet).'
        );
        $this->assertMatchesRegularExpression(
            '/\.grid\s*>\s*\*\s*,[\s\S]{0,60}min-width:\s*0/',
            $css,
            'Anak grid butuh min-width: 0 agar kolom bisa menyusut.'
        );
    }

    public function test_teknisi_mempunyai_rail_section_dan_strip_status(): void
    {
        $user = $this->loginAs('teknisi@lendora.test');

        // Rail ringkas = navigasi section selalu ada di desktop, bukan cuma
        // lewat burger atau chip status.
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('rail rail--slim', false)
            ->assertSee(route('admin.assets.index'), false)
            ->assertSee(route('admin.issues.index'), false);

        // Strip status hanya di Beranda & seksi Tiket.
        $this->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertSee('class="workbar', false);

        $this->get(route('admin.assets.index'))
            ->assertOk()
            ->assertDontSee('class="workbar', false);

        $counts = Navigation::ticketStatusCounts($user);
        $this->assertSame(
            array_map(fn ($c) => $c->value, \App\Enums\MaintenanceStatus::cases()),
            array_keys($counts),
            'Strip status harus memuat seluruh status tiket, termasuk yang nol.'
        );
    }

    public function test_strip_status_menampilkan_angka(): void
    {
        $this->loginAs('teknisi@lendora.test');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('workchip', false)
            ->assertSee('status=open', false);
    }

    public function test_peminjam_punya_navbar_section_di_layar_lebar(): void
    {
        $this->loginAs('budi@lendora.test');

        $response = $this->get(route('dashboard'))->assertOk();

        // Section di navbar; aksi & akun dipindah ke tombol & menu avatar agar
        // appbar tidak memuat dua menu yang isinya sama.
        $response->assertSee('class="appbar__nav"', false)
            ->assertSee(route('my.reservations.index'), false)
            ->assertSee(route('my.borrowings.index'), false)
            ->assertDontSee('Menu lengkap', false);

        // Aksi "Ajukan" tetap ada (FAB hanya muncul di ≤767px).
        $this->assertStringContainsString(
            'Ajukan',
            $response->getContent(),
            'Aksi utama peminjam harus tetap terjangkau di desktop.'
        );
    }
}
