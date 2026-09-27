<?php

namespace Tests\Feature;

use App\Models\Borrowing;
use App\Models\User;
use App\Services\ReportService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sistem analisis statistika.
 *
 * Fokus utama: deret waktu harus KONTINU. `borrowingTrend()` dulunya hanya
 * mengembalikan bulan yang punya transaksi, sehingga grafik menampilkan
 * tonggol jauh dari kiri dan bulan kosong terlihat seperti "tidak ada data"
 * — kesimpulan yang menyesatkan dari analitik yang sebenarnya benar.
 */
class AnalyticsTrendTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $org = $this->makeOrg();
        $this->admin = $this->makeUser('super-admin', $org);
        $this->orgId = $org->id;
    }

    private int $orgId;

    /** @param array<int, int> $perBulan format [bulanmundur => jumlah] */
    private function seedBorrowings(array $perBulan): void
    {
        $rows = [];
        foreach ($perBulan as $back => $count) {
            $month = now()->startOfMonth()->subMonths($back);
            for ($i = 0; $i < $count; $i++) {
                $rows[] = [
                    'code' => 'TRD-'.$back.'-'.$i,
                    'organization_id' => $this->orgId, // borrowings.organization_id NOT NULL
                    'borrower_id' => $this->admin->id,
                    'status' => 'returned',
                    'purpose' => 'Uji tren',
                    'checked_out_at' => $month->copy()->addDays(1)->setTime(9, 0),
                    'returned_at' => $month->copy()->addDays(3)->setTime(9, 0),
                    'created_at' => $month,
                    'updated_at' => now(),
                ];
            }
        }
        \Illuminate\Support\Facades\DB::table('borrowings')->insert($rows);
    }

    public function test_tren_selalu_contain_enam_bulan_lengkap(): void
    {
        $this->seedBorrowings([5 => 4, 4 => 6, 3 => 2, 2 => 0, 1 => 5, 0 => 3]); // bulan 2 kosong

        $trend = app(ReportService::class)->borrowingTrend();

        $this->assertCount(6, $trend, 'Tren harus selalu 6 baris, ada atau tidak ada datanya.');

        $months = $trend->pluck('month')->all();
        $this->assertSame(now()->startOfMonth()->subMonths(5)->format('Y-m'), $months[0]);
        $this->assertSame(now()->startOfMonth()->format('Y-m'), $months[5]);

        // Urut kronologis.
        $sorted = $months;
        sort($sorted);
        $this->assertSame($sorted, $months, 'Bulan harus terurut lama → baru.');
    }

    public function test_bulan_kosong_diisi_nol_bukan_dihilangkan(): void
    {
        $this->seedBorrowings([5 => 4, 4 => 0, 3 => 0, 2 => 0, 1 => 0, 0 => 3]); // 4 bulan kosong

        $trend = app(ReportService::class)->borrowingTrend();

        $this->assertCount(6, $trend);

        $empty = $trend->filter(fn ($r) => $r['total'] === 0);
        $this->assertCount(4, $empty, 'Empat bulan tanpa transaksi harus tetap ada dengan nilai 0.');

        // Yang ada datanya tidak boleh hilang.
        $this->assertSame(4, $trend->firstWhere('month', now()->startOfMonth()->subMonths(5)->format('Y-m'))['total']);
        $this->assertSame(3, $trend->last()['total']);
    }

    public function test_total_tren_mencakup_seluruh_transaksi(): void
    {
        $this->seedBorrowings([3 => 2, 2 => 5, 1 => 1, 0 => 4]);
        // Di luar jendela 6 bulan — tidak boleh ikut terhitung.
        \Illuminate\Support\Facades\DB::table('borrowings')->insert([
            'code' => 'TRD-LAMA', 'organization_id' => $this->orgId,
            'borrower_id' => $this->admin->id, 'status' => 'returned',
            'purpose' => 'Di luar jendela', 'checked_out_at' => now()->subYear(),
            'created_at' => now()->subYear(), 'updated_at' => now(),
        ]);

        $trend = app(ReportService::class)->borrowingTrend();

        $this->assertSame(12, $trend->sum('total'));
    }

    public function test_label_bulan_dalam_bahasa_indonesia(): void
    {
        $trend = app(ReportService::class)->borrowingTrend();

        $last = $trend->last()['label'];

        $this->assertMatchesRegularExpression(
            '/^[A-Z][a-z]{2} \d{4}$/',
            $last,
            "Label harus 'Sep 2026', bukan '2026-09' atau 'September 2026'."
        );
    }

    public function test_dashboard_admin_menampilkan_enam_batang(): void
    {
        $this->seedBorrowings([5 => 4, 4 => 0, 3 => 2, 2 => 0, 1 => 5, 0 => 3]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();

        $this->assertSame(
            6,
            substr_count($response->getContent(), 'class="spark__col'),
            'Grafik tren harus merender 6 kolom, termasuk yang 0 transaksi.'
        );

        // Kolom kosong ditandai agar berbeda dari "tidak ada data".
        $this->assertStringContainsString('spark__col is-empty', $response->getContent());
    }
}
