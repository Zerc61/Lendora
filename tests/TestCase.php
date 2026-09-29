<?php

namespace Tests;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Category;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Dasar semua test.
 *
 * Isolasi database: `phpunit.xml` menunjuk ke `lendora_test` pada driver yang
 * sama dengan produksi (PostgreSQL), bukan ke database dev. `RefreshDatabase`
 * me-migrasi ulang dari nol setiap test — artinya database target **dihapus
 * seluruh isinya** — jadi ada guard di `setUpTraits()` yang menolak jalan
 * kalau targetnya bukan database uji. Ini pengganti SQLite :memory: yang tidak
 * tersedia di environment ini, dan juga pengganti kebiasaan "semoga .env-nya
 * tidak salah" yang berakhir dengan 707 user hilang.
 *
 * Aplikasi berjalan di PostgreSQL, jadi suite juga harus PostgreSQL. Test
 * yang bergantung pada quoting MySQL akan gagal di sini — itu memang hal
 * yang perlu dibongkar, bukan alasan untuk tetap di MySQL.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected int $assetSeq = 0;

    protected static int $userSeq = 0;

    /**
     * Nama database yang boleh dihapus oleh RefreshDatabase.
     *
     * Suffix `_test` dipakai supaya CI bisa memakai `lendora_test_ci` tanpa
     * perlu mengubah kode. Nama produksi Lendora (`neondb`) tidak berakhiran
     * `_test`, jadi tidak akan lolos.
     */
    private const SAFE_TEST_DATABASES = ['lendora_test', ':memory:'];

    /**
     * Penjaga terakhir sebelum migrate:fresh.
     *
     * Dipanggil dari `setUpTraits()` karena titik itu framework memanggil
     * `refreshDatabase()`. Kalau dijaga di `setUp()` setelah `parent::setUp()`,
     * migrasi sudah terlanjur berjalan dan tabel sudah terlanjur hilang.
     */
    protected function setUpTraits()
    {
        $this->guardTestDatabaseIsDisposable();
        $this->guardTestDatabaseIsNotProduction();

        return parent::setUpTraits();
    }

    private function guardTestDatabaseIsDisposable(): void
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        $isDisposable = in_array($database, self::SAFE_TEST_DATABASES, true)
            || str_ends_with($database, '_test');

        if ($isDisposable) {
            return;
        }

        throw new \RuntimeException(sprintf(
            "Test DIBATASI: \"%s\" bukan database uji, dan RefreshDatabase akan menghapus seluruh isinya.\n"
            ."Database produksi Lendora tidak boleh jadi target test.\n"
            ."Buat database uji (di Neon: buat database `lendora_test`, atau pakai branch terpisah),\n"
            ."lalu arahkan lewat environment:\n"
            ."    DB_DATABASE=lendora_test DB_CONNECTION=pgsql vendor/bin/phpunit\n"
            .'atau set DB_DATABASE/DB_CONNECTION di shell sebelum menjalankan test.',
            $database !== '' ? $database : '(kosong)'
        ));
    }

    /**
     * Penjaga kedua, orthogonal terhadap nama database: host-nya jangan sampai
     * branch produksi.
     *
     * Penjaga nama di atas cek `lendora_test`, dan `lendora_test` MEMANG ADA
     * di branch produksi — jadi penjaga itu lolos padahal targetnya satu
     * branch dengan 707 user asli. `RefreshDatabase` tidak peduli nama, dia
     * hanya menjalankan migrate:fresh: seluruh isi branch itu hilang, termasuk
     * `lendora_test` yang ikut tersapu dan harus dibuat ulang tiap run.
     *
     * Sumber kebenaran "host produksi itu yang mana" dibaca dari berkas `.env`
     * di disk, bukan dari `getenv()`: nilainya sudah ditimpa phpunit.xml
     * sebelum guard ini jalan. Berkas tidak ada (mis. CI) → cek dilewati,
     * penjaga nama masih berlaku.
     */
    private function guardTestDatabaseIsNotProduction(): void
    {
        $prod = $this->productionDatabaseCoordinates();

        if ($prod === null) {
            return;
        }

        $connection = config('database.default');
        $host = trim((string) config("database.connections.{$connection}.host"));

        // Host kosong = tidak terkonfigurasi, bukan bukti aman. Tapi juga tidak
        // bisa dicocokkan, jadi dilewati agar tidak memblokir CI yang hanya
        // menyetel DB_DATABASE lewat environment.
        if ($host === '') {
            return;
        }

        // Dua-duanya ditolak: host persis sama dengan yang di-.env produksi,
        // dan host direct dari branch yang sama (`.env` produksi memakai host
        // pooler — `…-pooler.…` vs `…-…`). Keduanya harus dicek karena keduanya
        // menunjuk branch yang sama.
        if ($host !== $prod['host'] && $host !== $prod['pooled_host']) {
            return;
        }

        $database = (string) config("database.connections.{$connection}.database");

        throw new \RuntimeException(sprintf(
            "Test DIBATASI: test diarahkan ke host yang sama dengan produksi.\n"
            ."  .env (produksi) : %s / %s\n"
            ."  test berjalan  : %s / %s\n"
            ."RefreshDatabase akan migrate:fresh di branch itu. Selama branch sama,\n"
            ."branch produksi ikut tersapu — bukan hanya lendora_test, tapi SELURUH\n"
            ."isi branch, dan lendora_test ikut hilang lalu harus dibuat ulang.\n"
            .'Arahkan test ke branch Neon terpisah (branch `test`), lihat phpunit.xml.',
            $prod['host'], $prod['database'],
            $host, $database !== '' ? $database : '(kosong)'
        ));
    }

    /**
     * Baca host & database produksi dari berkas `.env` di disk.
     *
     * @return array{host: string, pooled_host: string, database: string}|null
     */
    private function productionDatabaseCoordinates(): ?array
    {
        $envFile = dirname(__DIR__).'/.env';

        if (! is_readable($envFile)) {
            return null;
        }

        $values = [];
        foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $values[trim($parts[0])] = trim($parts[1], "\"' \t");
            }
        }

        $host = $values['DB_HOST'] ?? '';

        if ($host === '' || isset($values['DB_URL'])) {
            // DB_HOST kosong atau produksi dikonfigurasi lewat DB_URL — tidak
            // bisa dicocokkan dengan andal. Penjaga nama database tetap berlaku.
            return null;
        }

        return [
            'host' => $host,
            // Host yang di-.env produksi adalah host POOLER. Test boleh memakai
            // pooler atau direct dari branch yang sama; keduanya dicocokkan.
            'pooled_host' => str_replace('-pooler.', '.', $host),
            'database' => $values['DB_DATABASE'] ?? '',
        ];
    }

    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function makeOrg(): Organization
    {
        return Organization::create([
            'name' => 'Organisasi Uji',
            'code' => 'UJI-'.Str::upper(Str::random(4)), // unik per pemanggilan
            'status' => 'active',
        ]);
    }

    protected function makeUser(string $role, ?Organization $org = null, ?string $email = null): User
    {
        if (Role::count() === 0) {
            $this->seedRoles(); // auto-seed role/permission saat user pertama dibuat
        }

        self::$userSeq++;

        $user = User::create([
            'name' => ucfirst($role).' Uji',
            // Email unik per pemanggilan — beberapa helper membuat >1 user
            // dengan role yang sama dalam satu test.
            'email' => $email ?? (self::$userSeq.'-'.$role.'@test.local'),
            'password' => 'password',
            'organization_id' => $org?->id,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function makeAsset(Organization $org, string $status = 'available'): Asset
    {
        $this->assetSeq++;

        $category = Category::create([
            'organization_id' => $org->id,
            'name' => "Kategori Uji {$this->assetSeq}",
        ]);

        $type = AssetType::create([
            'organization_id' => $org->id,
            'category_id' => $category->id,
            'name' => "Tipe Uji {$this->assetSeq}",
        ]);

        return Asset::create([
            'organization_id' => $org->id,
            'asset_type_id' => $type->id,
            'asset_code' => sprintf('LND-TST-%04d', $this->assetSeq),
            'status' => $status,
            'condition' => 'good',
        ]);
    }
}
