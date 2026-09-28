<?php

namespace Tests\Feature\View;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Query budget per halaman.
 *
 * Kenapa ada: performa di Lendora ditentukan oleh JUMLAH round-trip ke
 * database, bukan oleh ukuran data. DB produksi ini sangat kecil (puluhan
 * baris), jadi optimasi berbasis "table besar" tidak akan pernah terpicu —
 * yang penting adalah berapa kali aplikasi mengetuk database untuk satu
 * halaman.
 *
 * Karena itu anggaran di sini dinyatakan dalam JUMLAH QUERY, bukan
 * milidetik. Nilai milidetik akan flakut: ia bergantung pada region, koneksi,
 * dan apakah cache sudah hangat. Jumlah query tidak.
 *
 * Angka di dalam test sengaja longgar (bukan angka persis yang diukur) —
 * test ini menjaga agar tidak ADA PENURUNAN KEMBALI ke pola N+1, bukan
 * untuk mengunci implementasi. Kalau sebuah halaman butuh satu query lagi
 * karena alasan sah, angka di sini yang dinaikkan dan alasannya ditulis.
 *
 * Lihat PERF.md untuk angka riil dan daftar percobaan yang ditolak.
 */
class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{sql: string, ms: float}> */
    private array $queries = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->queries = [];

        DB::listen(function ($query) {
            $this->queries[] = ['sql' => $query->sql, 'ms' => $query->time];
        });
    }

    /**
     * Render satu halaman dan kembalikan daftar SQL-nya.
     *
     * @return list<string>
     */
    private function queriesFor(string $path, $user = null): array
    {
        $this->queries = [];

        $request = $user
            ? $this->actingAs($user)->get($path)
            : $this->get($path);

        $request->assertOk();

        return array_column($this->queries, 'sql');
    }

    /** Query SELECT dari tabel aplikasi, buang query infrastructure. */
    private function appQueries(array $sqls): array
    {
        return array_values(array_filter(
            $sqls,
            fn ($sql) => ! str_contains($sql, '`migrations`')
                && ! str_contains($sql, '`cache`')
                && ! str_contains($sql, '`sessions`')
        ));
    }

    private function assertWithinBudget(string $label, array $sqls, int $budget): void
    {
        $app = $this->appQueries($sqls);
        $n = count($app);

        $this->assertLessThanOrEqual(
            $budget,
            $n,
            sprintf(
                "%s mengirim %d query (anggaran %d).\nQuery yang jalan:\n  %s",
                $label,
                $n,
                $budget,
                implode("\n  ", array_map('trim', $app))
            )
        );
    }

    public function test_dashboard_admin_dalam_anggaran(): void
    {
        $user = $this->makeUser('super-admin', $this->makeOrg(), 'budget-admin@test.local');

        $this->assertWithinBudget('dashboard admin', $this->queriesFor('/dashboard', $user), 25);
    }

    /**
     * Badge navigasi dihitung di layout, jadi HANYA user yang punya izin
     * tertentu yang membuatnya menyala. Semua user autenticated tetap
     * melewati jalur hitung badge — itulah yang diuji di sini.
     */
    public function test_dashboard_borrower_dalam_anggaran(): void
    {
        $user = $this->makeUser('borrower', $this->makeOrg(), 'budget-borrower@test.local');

        $this->assertWithinBudget('dashboard borrower', $this->queriesFor('/dashboard', $user), 25);
    }

    public function test_halaman_login_tidak_menyentuh_database_berat(): void
    {
        // /login hanya untuk guest, jadi tidak ada user.
        $this->assertWithinBudget('login', $this->queriesFor('/login'), 2);
    }

    /**
     * N+1 adalah regresi yang paling mudah masuk kembali tanpa disadari:
     * menambah satu baris di view cukup untuk menyalakannya.
     *
     * Test ini sengaja memakai data yang CUKUP BANYAK supaya N+1 terlihat
     * pada angkanya, bukan pada Feel.
     */
    public function test_relation_di_dalam_loop_tidak_mengambil_query_per_baris(): void
    {
        $org = $this->makeOrg();

        foreach (['available', 'reserved', 'borrowed', 'maintenance'] as $i => $status) {
            for ($n = 0; $n < 5; $n++) {
                $this->makeAsset($org, $status);
            }
        }

        $user = $this->makeUser('super-admin', $org, 'budget-n1@test.local');

        $sqls = $this->appQueries($this->queriesFor('/dashboard', $user));

        $assetTypes = array_filter(
            $sqls,
            fn ($sql) => str_contains($sql, '`asset_types`') && ! str_contains($sql, 'count(')
        );

        $this->assertLessThanOrEqual(
            1,
            count($assetTypes),
            'Query asset_types berulang — relasi diakses di dalam loop tanpa with().'
                ."\n  ".implode("\n  ", $assetTypes)
        );
    }

    /**
     * Aturan: setiap relation yang diakses di dalam loop harus punya
     * with(). Test ini mengunci grouping-nya supaya "Aset Terpopuler" —
     * yang sempat N+1 — tidak balik lagi.
     */
    public function test_dashboard_tidak_mengambil_asset_type_satu_peri_baris(): void
    {
        $org = $this->makeOrg();

        for ($n = 0; $n < 5; $n++) {
            $this->makeAsset($org, 'available');
        }

        $user = $this->makeUser('super-admin', $org, 'budget-eager@test.local');

        $sqls = $this->queriesFor('/dashboard', $user);

        $perRow = array_filter(
            $sqls,
            fn ($sql) => (bool) preg_match('/`asset_types` where `asset_types`.`id` = \?/', $sql)
        );

        $this->assertSame(
            [],
            array_values($perRow),
            'Pola "where id = ?" per baris terdeteksi — relasi belum di-eager-load.'
                ."\n  ".implode("\n  ", $perRow)
        );
    }
}
