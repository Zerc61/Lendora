<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use App\Services\ReportService;
use App\Support\AppCounts;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kompatibilitas SQL dengan PostgreSQL.
 *
 * Latar: aplikasi berjalan di Neon PostgreSQL, tapi beberapa query mentah
 * masih ditulis dengan dialek MySQL. Semuanya lolos review dan lolos deploy
 * karena tidak ada satu pun filter yang benar-benar mengeksekusinya terhadap
 * PostgreSQL — hasilnya tiga halaman inti (dashboard, laporan, daftar user)
 * balas HTTP 500, dan test suite tidak bisa menangkapnya karena
 * `phpunit.xml` mengunci `DB_CONNECTION=mysql` ke database yang tidak ada.
 *
 * Karakter test di file ini:
 *
 *  1. Yang diuji adalah KONTRAK HALAMAN, bukan hanya "tidak melempar error".
 *     Halaman yang balas 200 berarti seluruh SQL di jalur itu sudah benar-benar
 *     dieksekusi driver sungguhan — tidak ada mock, tidak ada asumsi.
 *  2. Ada test yang memverifikasi ARITMETIKA, bukan cuma ketiadaan error.
 *     `TIMESTAMPDIFF(HOUR, a, b)` → `EXTRACT(EPOCH FROM (b - a)) / 3600` adalah
 *     penggantian yang rawan terbalik diam-diam: query-nya jalan, angkanya
 *     salah, dan grafik utilisasi jadi bohong tanpa memunculkan error apa pun.
 *  3. Ada pagar statis (dua test terakhir) yang menyisir seluruh `app/` mencari
 *     fungsi MySQL-only dan backtick. Ini yang menangkap kemunculan ulang di
 *     kode yang belum tercover test, dan tidak butuh database sama sekali.
 *
 * Lihat PERF.md untuk angka latency dan `docs/deployment.md` untuk driver produksi.
 */
class PostgresCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private int $orgId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $org = $this->makeOrg();
        $this->orgId = $org->id;
        $this->admin = $this->makeUser('super-admin', $org, 'pg-superadmin@test.local');
    }

    // ─────────────────────────────────────────────────────────────
    // 1. Halaman yang dulu HTTP 500
    // ─────────────────────────────────────────────────────────────

    /**
     * Tiga halaman ini butuh izin berbeda (super-admin punya ketiganya), jadi
     * satu user cukup. Yang diperiksa hanya status 200 — error SQL muncul
     * sebagai 500, jadi ini langsung menangkap regresi dialek.
     */
    public function test_halaman_inti_tidak_lagi_500_di_postgresql(): void
    {
        $this->actingAs($this->admin);

        $this->get('/dashboard')->assertOk();          // AppCounts::assetByCondition
        $this->get('/admin/reports')->assertOk();       // ReportService::utilization
        $this->get('/admin/users')->assertOk();         // UserController::roleCounts
    }

    /**
     * Guard terpisah supaya saat gagal, tahu halaman mana yang rusak —
     * bukan satu assertion 500 yang tidak menjelaskan apa pun.
     */
    public function test_setiap_halaman_yang_pernah_mematakan_diuji_individu(): void
    {
        $paths = [
            '/dashboard' => 'AppCounts::assetByCondition() — selectRaw dengan backtick',
            '/admin/reports' => 'ReportService::utilization() — TIMESTAMPDIFF/NOW()',
            '/admin/users' => 'UserController::roleCounts() — pluck(DB::raw()) tanpa alias',
        ];

        foreach ($paths as $path => $cause) {
            $response = $this->actingAs($this->admin)->get($path);

            $response->assertOk(sprintf(
                '%s balas %d. Penyebab yang paling mungkin: %s',
                $path, $response->getStatusCode(), $cause
            ));
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 2. assetByCondition() — nilai, bukan sekadar "tidak error"
    // ─────────────────────────────────────────────────────────────

    public function test_asset_by_condition_menghasilkan_peta_int(): void
    {
        $org = $this->makeOrg();

        $good = $this->makeAsset($org, 'available');
        $fair = $this->makeAsset($org, 'available');
        $fair->update(['condition' => 'fair']);

        $counts = app(AppCounts::class)->assetByCondition();

        // makeAsset() selalu membuat aset dengan condition 'good'.
        $this->assertSame(1, $counts['good'] ?? null, 'Aset pertama harus terhitung sebagai good.');
        $this->assertSame(1, $counts['fair'] ?? null, 'Kondisi setelah diubah harus terhitung sendiri.');

        foreach ($counts as $condition => $total) {
            $this->assertIsInt($total, "Kondisi {$condition} harus integer, bukan string dari driver.");
        }
    }

    public function test_asset_by_condition_sempre_konsisten_dengan_jumlah_aset(): void
    {
        $org = $this->makeOrg();

        // Jumlah sengaja tidak genap dan tiap kondisi muncul lebih dari sekali.
        $conditions = ['good', 'good', 'fair', 'broken', 'broken', 'excellent'];

        foreach ($conditions as $condition) {
            $this->makeAsset($org, 'available')->update(['condition' => $condition]);
        }

        $counts = app(AppCounts::class)->assetByCondition();

        $this->assertCount(count(array_unique($conditions)), $counts);
        $this->assertSame(Asset::count(), array_sum($counts), 'Ada aset yang tidak masuk hitungan kondisi.');

        foreach (array_count_values($conditions) as $condition => $expected) {
            $this->assertSame($expected, $counts[$condition] ?? null, "Hitungan untuk kondisi {$condition} salah.");
        }
    }

    public function test_asset_by_condition_aman_saat_tabel_kosong(): void
    {
        $this->assertSame([], app(AppCounts::class)->assetByCondition());
    }

    // ─────────────────────────────────────────────────────────────
    // 3. utilization() — aritmetika harus benar, bukan cuma jalan
    // ─────────────────────────────────────────────────────────────

    /**
     * Jendela periode di masa lalu yang sudah tertutup. Dipilih disengaja:
     * bagian `COALESCE(b.returned_at, now())` selalu kalah oleh `LEAST(..., to)`
     * sehingga hasilnya deterministik — tidak bergantung jam server.
     */
    private function periodeTertutup(): array
    {
        return [
            Carbon::parse('2026-01-01 00:00:00'),
            Carbon::parse('2026-01-31 23:59:59'),
        ];
    }

    /** Buat satu peminjaman + satu butir peminjaman yang menautkannya ke aset. */
    private function pinjamkan(Asset $asset, ?Carbon $checkedOut, ?Carbon $returned): void
    {
        $borrowingId = DB::table('borrowings')->insertGetId([
            'code' => 'PG-'.strtoupper(bin2hex(random_bytes(4))),
            'organization_id' => $this->orgId,
            'borrower_id' => $this->admin->id,
            'status' => $returned ? 'returned' : 'borrowed',
            'purpose' => 'Uji kompatibilitas PostgreSQL',
            'checked_out_at' => $checkedOut,
            'returned_at' => $returned,
            'created_at' => $checkedOut ?? now(),
            'updated_at' => now(),
        ]);

        DB::table('borrowing_items')->insert([
            'borrowing_id' => $borrowingId,
            'asset_id' => $asset->id,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_utilisasi_menghitung_seluruh_hari_di_luar(): void
    {
        $asset = $this->makeAsset($this->makeOrg(), 'available');

        // 10 – 13 Januari di dalam jendela 1–31 Januari → tepat 3 hari.
        $this->pinjamkan(
            $asset,
            Carbon::parse('2026-01-10 09:00:00'),
            Carbon::parse('2026-01-13 09:00:00')
        );

        [$from, $to] = $this->periodeTertutup();
        $rows = app(ReportService::class)->utilization($from, $to);

        $this->assertCount(1, $rows);
        $this->assertSame($asset->asset_code, $rows->first()->asset_code);
        $this->assertSame(1, (int) $rows->first()->borrow_count);
        $this->assertEqualsWithDelta(
            3.0, (float) $rows->first()->days_out, 0.01,
            'Selisih 10→13 Januari harus 3 hari, bukan 72 jam yang salah bagi.'
        );
    }

    /**
     * Peminjaman yang dimulai SEBELUM jendela dan dikembalikan di dalamnya.
     * `GREATEST(checked_out_at, from)` harus memotong bagian di luar jendela —
     * kalau tidak, utilisasi aset jadi lebih dari 100% dan tidak ada yang protes.
     */
    public function test_utilisasi_memotong_overlap_di_luar_jendela(): void
    {
        $asset = $this->makeAsset($this->makeOrg(), 'available');

        // Mulai 20 Desember (sebelum jendela), kembali 3 Januari PUKUL 00:00
        // (di dalam). Jamnya sengaja sama dengan $from supaya selisihnya
        // tepat 2 hari penuh — angka pecahan akan menutupi salahnya rumus.
        $this->pinjamkan(
            $asset,
            Carbon::parse('2025-12-20 00:00:00'),
            Carbon::parse('2026-01-03 00:00:00')
        );

        [$from, $to] = $this->periodeTertutup();
        $rows = app(ReportService::class)->utilization($from, $to);

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(
            2.0, (float) $rows->first()->days_out, 0.01,
            'Hanya 1→3 Januari yang berada di dalam jendela; bagian Desember harus dipotong.'
        );
    }

    /**
     * `utilization_pct` tidak boleh melewati 100%, dan `LEAST(..., $to)` harus
     * memotong_tail peminjaman yang masih keluar melewati ujung jendela.
     *
     * Kasus ini memakai peminjaman yang MULAI di dalam jendela lalu kembali
     * setelahnya. Kasus pasangannya — keluar SEBELUM jendela lalu kembali
     * SESUDAH jendela — ada di test berikutnya.
     */
    public function test_tingkat_pemanfaatan_tidak_pernah_melebihi_seratus_persen(): void
    {
        $asset = $this->makeAsset($this->makeOrg(), 'available');

        // Mulai tepat di awal jendela, kembali 2 bulan setelahnya. Terpotong
        // `LEAST(..., $to)` → okupansi penuh sepanjang jendela.
        $this->pinjamkan(
            $asset,
            Carbon::parse('2026-01-01 00:00:00'),
            Carbon::parse('2026-03-05 00:00:00')
        );

        [$from, $to] = $this->periodeTertutup();
        $rows = app(ReportService::class)->utilization($from, $to);

        $this->assertCount(1, $rows, 'Peminjaman yang menutupi seluruh jendela harus masuk laporan.');

        $pct = $rows->first()->utilization_pct;

        $this->assertGreaterThan(99, $pct, 'Aset yang keluar sepanjang jendela harus mendekati 100%.');
        $this->assertLessThanOrEqual(100, $pct, 'Pemanfaatan tidak mungkin melebihi 100%.');
    }

    /**
     * Memakai window 30 hari penuh (1–31 Januari) dengan peminjaman yang
     * menutupinya persis. Menguji rumusnya: EXTRACT(EPOCH)/3600 → jam, /24 →
     * hari. Kalau ada yang terbalik satu langkah, angkanya meleset faktor 24
     * dan test ini menangkapnya.
     */
    public function test_hari_dihitung_sebagai_jam_dibagi_empat_puluh_empat(): void
    {
        $asset = $this->makeAsset($this->makeOrg(), 'available');

        $this->pinjamkan(
            $asset,
            Carbon::parse('2026-01-05 00:00:00'),
            Carbon::parse('2026-01-10 00:00:00')
        );

        [$from, $to] = $this->periodeTertutup();
        $rows = app(ReportService::class)->utilization($from, $to);

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(
            5.0, (float) $rows->first()->days_out, 0.01,
            'Lima hari. Kalau hasilnya 120, pembaginya salah (jam tidak dibagi 24).'
        );
    }

    public function test_peminjaman_yang_belum_kembali_terhitung_hingga_sampai_batas_periode(): void
    {
        $asset = $this->makeAsset($this->makeOrg(), 'available');

        // returned_at kosong → `now()` harus menggantikannya, lalu LEAST memotong
        // di `$to`. Karena jendela sudah lewat, hasilnya deterministik.
        $this->pinjamkan($asset, Carbon::parse('2026-01-20 09:00:00'), null);

        [$from, $to] = $this->periodeTertutup();
        $rows = app(ReportService::class)->utilization($from, $to);

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(
            11.6, (float) $rows->first()->days_out, 0.1,
            '20 Jan 09:00 → 31 Jan 23:59 = 11 hari 15 jam ≈ 11,6 hari.'
        );
    }

    public function test_utilisasi_aman_saat_tidak_ada_transaksi(): void
    {
        [$from, $to] = $this->periodeTertutup();

        $this->assertCount(0, app(ReportService::class)->utilization($from, $to));
    }

    /**
     * Kasus yang HILANG dari laporan: dipinjam sebelum jendela, dikembalikan
     * setelah jendela selesai. 100% waktunya berada di dalam periode, jadi
     * aset jelas dipakai — tapi laporan tidak menampilkan apa pun.
     *
     * Penyebabnya WHERE clause lama yang menguji "dimulai di dalam" ATAU
     * "dikembalikan di dalam" ATAU "belum kembali". Peminjaman ini memenuhi
     * tidak satu pun: `checked_out_at` dan `returned_at`-nya sama-sama di luar
     * [from, to], dan `returned_at` bukan NULL.
     *
     * Test ini mengunci perbaikan itu. Kalau filternya dikembalikan ke versi
     * tiga-cabang, assertion `assertCount(1, …)` gagal.
     */
    public function test_peminjaman_yang_melewati_jendela_sepenuhnya_masih_terhitung(): void
    {
        $asset = $this->makeAsset($this->makeOrg(), 'available');

        // 15 Desember → 5 Februari: keluar SEBELUM jendela (1 Jan), kembali
        // SESUDAH jendela (31 Jan). Tidak ada satu pun timestamp di dalam
        // [from, to] kecuali bagian yang terpotong di tengahnya.
        $this->pinjamkan(
            $asset,
            Carbon::parse('2025-12-15 00:00:00'),
            Carbon::parse('2026-02-05 00:00:00')
        );

        [$from, $to] = $this->periodeTertutup();
        $rows = app(ReportService::class)->utilization($from, $to);

        $this->assertCount(
            1, $rows,
            'Peminjaman yang menutupi seluruh jendela harus masuk laporan. '
            .'Kalau 0 baris, WHERE clause kembali ke versi tiga-cabang yang melompati kasus ini.'
        );

        $this->assertSame($asset->asset_code, $rows->first()->asset_code);
        $this->assertEqualsWithDelta(
            31.0, (float) $rows->first()->days_out, 0.01,
            'Seluruh jendela terisi: GREATEST(_, from) dan LEAST(_, to) harus memotong '
            .'tepi-tepinya, jadi hasilnya sama dengan panjang jendela. Jendela di sini '
            .'1 Jan 00:00:00 → 31 Jan 23:59:59 = 30 hari 23:59:59 ≈ 31,0 hari '
            .'(dibulatkan ke 1 desimal). Angka 30,0 akan berarti satu jam hilang di '
            .'salah satu ujung.'
        );
    }

    /**
     * Peminjaman yang sama sekali TIDAK beririsan dengan jendela harus tetap
     * tersaring. Ini penjaga agar perbaikan interval-overlap tidak melebar jadi
     * "ambil semua".
     */
    public function test_peminjaman_sebelum_dan_setelah_jendela_tidak_ikut_terhitung(): void
    {
        $sebelum = $this->makeAsset($this->makeOrg(), 'available');
        $sesudah = $this->makeAsset($this->makeOrg(), 'available');

        // Keduanya sepenuhnya di luar jendela 1–31 Januari 2026.
        $this->pinjamkan(
            $sebelum,
            Carbon::parse('2025-11-01 00:00:00'),
            Carbon::parse('2025-12-01 00:00:00')
        );
        $this->pinjamkan(
            $sesudah,
            Carbon::parse('2026-03-01 00:00:00'),
            Carbon::parse('2026-04-01 00:00:00')
        );

        [$from, $to] = $this->periodeTertutup();

        $this->assertCount(
            0, app(ReportService::class)->utilization($from, $to),
            'Peminjaman yang tidak beririsan sama sekali dengan jendela tidak boleh masuk laporan.'
        );
    }

    // ─────────────────────────────────────────────────────────────
    // 4. Pagar statis — cegah kemunculan ulang di seluruh app/
    // ─────────────────────────────────────────────────────────────

    /**
     * Fungsi SQL yang ada di MySQL dan tidak ada di PostgreSQL. Memanggil
     * salah satunya tidak menghasilkan error yang mudah dibaca — PostgreSQL
     * baru protes saat query dieksekusi, sering di halaman yang tidak ada
     * hubungannya dengan kode yang salah.
     *
     * Daftar ini sengaja pendek dan berisiko rendah false-positive. Fungsi
     * yang kebetulan juga ada di PostgreSQL (mis. `YEAR`, `MONTH`, `DATE`)
     * tidak dimasukkan justru karena bentrok dengan nama kolom dan method.
     */
    private const MYSQL_ONLY = [
        'TIMESTAMPDIFF', 'DATE_FORMAT', 'IFNULL', 'CURDATE', 'CURTIME',
        'GROUP_CONCAT', 'UNIX_TIMESTAMP', 'FROM_UNIXTIME', 'DATE_SUB',
        'DATE_ADD', 'LAST_INSERT_ID', 'SUBSTRING_INDEX', 'UUID_SHORT',
        'BENCHMARK', 'SLEEP', 'FIELD', 'CONV', 'RAND', 'LOCATE',
    ];

    public function test_tidak_ada_fungsi_sql_khusus_mysql_di_app(): void
    {
        $violations = [];

        foreach ($this->phpFilesIn(base_path('app')) as $file) {
            $source = file_get_contents($file);
            $line = 0;

            // Token, bukan preg_match atas teks mentah: komentar dan docblock
            // sengaja boleh menyebut nama fungsi ini untuk menjelaskan kenapa
            // aze tidak dipakai. Yang dicek hanya kode yang benar-benar jalan.
            foreach (token_get_all($source) as $token) {
                if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                $line = is_array($token) ? $token[2] : $line;

                if (! is_array($token) || $token[0] !== T_STRING) {
                    continue;
                }

                if (in_array(strtoupper($token[1]), self::MYSQL_ONLY, true)) {
                    $violations[] = sprintf(
                        '%s:%d  %s()',
                        str_replace(base_path().'/', '', $file),
                        $line,
                        $token[1]
                    );
                }
            }
        }

        $this->assertSame([], $violations, sprintf(
            "Fungsi SQL khusus MySQL ditemukan:\n  %s\n"
            .'PostgreSQL tidak menyediakannya — pakai padanannya, atau bungkus per driver.',
            implode("\n  ", $violations)
        ));
    }

    /**
     * Backtick adalah quoting identifier MySQL. Di PostgreSQL backtick bukan
     * tanda kutip: query langsung gagal `42601: syntax error at or near ","`.
     *
     * Tidak ada satu pun string literal di app/ yang memakai backtick untuk
     * keperluan lain, jadi aturan ini tidak punya false-positive.
     */
    public function test_tidak_ada_backtick_di_dalam_string_literal(): void
    {
        $violations = [];

        foreach ($this->phpFilesIn(base_path('app')) as $file) {
            $source = file_get_contents($file);
            $line = 0;

            foreach (token_get_all($source) as $token) {
                $line = is_array($token) ? $token[2] : $line;

                if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING
                    && str_contains($token[1], '`')) {
                    $violations[] = sprintf(
                        '%s:%d  %s',
                        str_replace(base_path().'/', '', $file),
                        $line,
                        $token[1]
                    );
                }
            }
        }

        $this->assertSame([], $violations, sprintf(
            "Backtick di string literal:\n  %s\n"
            .'Ini quoting identifier MySQL. Tulis kolom lewat ->select() supaya grammar driver yang menanganinya.',
            implode("\n  ", $violations)
        ));
    }

    /** @return list<string> */
    private function phpFilesIn(string $dir): array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
