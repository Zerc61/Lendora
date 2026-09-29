<?php

namespace Tests\Feature;

use ReflectionClass;
use Tests\TestCase;

/**
 * Penjaga di Tests\TestCase menentukan apakah aman menjalankan `migrate:fresh`.
 * Salah baca di sini berarti menghapus isi database yang salah, dan itu tidak
 * bisa diukur dengan test yang kebetulan tidak salah konfigurasi. Jadi kedua
 * guard diuji langsung.
 *
 * Setiap pemanggilan guard memakai instance terpisah, supaya config() yang
 * diubah-ubah di sini tidak mengganggu test yang sedang berjalan.
 *
 * File ini ada di Feature, bukan Unit, karena `config()` butuh container
 * Laravel yang sudah di-boot. Efek sampingnya: test ini sekaligus membuktikan
 * branch `test` yang ditunjuk phpunit.xml benar-benar bisa dipakai — kalau
 * host-nya salah, `setUpTraits()` sudah menolak sebelum test pertama jalan.
 */
class TestDatabaseGuardTest extends TestCase
{
    /**
     * Instance bersih dari Tests\TestCase — tanpa constructor PHPUnit, tanpa
     * efek samping, hanya untuk memanggil method privatnya.
     */
    private function guard(): object
    {
        return $this->guardReflection()->newInstanceWithoutConstructor();
    }

    private function guardReflection(): ReflectionClass
    {
        // `new class(...)` di sini hanya untuk mendapat NAMA kelas konkret;
        // instansinya sendiri dibuang dan dibuat ulang lewat reflection.
        $name = (new ReflectionClass(new class('probe') extends TestCase {}))->getName();

        return new ReflectionClass($name);
    }

    private function runGuard(string $method): void
    {
        $m = $this->guardReflection()->getMethod($method);
        $m->setAccessible(true);
        $m->invoke($this->guard());
    }

    private function withConnectionConfig(string $host, string $database): void
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => $host,
            'database.connections.pgsql.database' => $database,
        ]);
    }

    public function test_database_berakhiran_test_boleh_dihapus(): void
    {
        $this->withConnectionConfig('host-mana-saja.example', 'lendora_test');

        $this->runGuard('guardTestDatabaseIsDisposable');
        $this->runGuard('guardTestDatabaseIsNotProduction');

        $this->addToAssertionCount(1);
    }

    public function test_nama_database_produksi_ditolak(): void
    {
        $this->withConnectionConfig('host-mana-saja.example', 'neondb');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('bukan database uji');

        $this->runGuard('guardTestDatabaseIsDisposable');
    }

    public function test_branch_produksi_ditolak_walaupun_nama_database_aman(): void
    {
        $prod = $this->productionCoordinates();

        // Inilah kasus yang PENJAGA NAMA tidak bisa tangkap: `lendora_test`
        // juga ada di branch produksi, jadi guard nama lolos begitu saja.
        $this->withConnectionConfig($prod['host'], 'lendora_test');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('host yang sama dengan produksi');

        $this->runGuard('guardTestDatabaseIsNotProduction');
    }

    public function test_host_direct_produksi_ikut_ditolak(): void
    {
        $prod = $this->productionCoordinates();

        $direct = str_replace('-pooler.', '.', $prod['host']);

        if ($direct === $prod['host']) {
            $this->markTestSkipped('Host produksi tidak berakhiran -pooler, tidak ada varian direct.');
        }

        $this->withConnectionConfig($direct, 'lendora_test');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('host yang sama dengan produksi');

        $this->runGuard('guardTestDatabaseIsNotProduction');
    }

    public function test_branch_terpisah_boleh_berjalan(): void
    {
        $this->withConnectionConfig(
            'ep-branch-lain-pooler.c-4.ap-southeast-1.aws.neon.tech',
            'lendora_test'
        );

        $this->runGuard('guardTestDatabaseIsNotProduction');
        $this->addToAssertionCount(1);
    }

    public function test_nama_database_produksi_di_branch_lain_tetap_ditolak(): void
    {
        $this->withConnectionConfig(
            'ep-branch-lain-pooler.c-4.ap-southeast-1.aws.neon.tech',
            'neondb'
        );

        $this->expectException(\RuntimeException::class);

        $this->runGuard('guardTestDatabaseIsDisposable');
    }

    /**
     * Koordinat produksi dibaca lewat method production yang SESUNGGUHNYA
     * dipakai guard — bukan parsing ulang di sini. Kalau interpretasi
     * parsing .env-nya berbeda, test ini akan salah dan menyesatkan.
     *
     * @return array{host: string, pooled_host: string, database: string}
     */
    private function productionCoordinates(): array
    {
        $m = $this->guardReflection()->getMethod('productionDatabaseCoordinates');
        $m->setAccessible(true);
        $result = $m->invoke($this->guard());

        $this->assertIsArray($result, 'Tidak ada .env — test ini tidak bisa membandingkan branch produksi.');
        $this->assertNotSame('', $result['host']);
        $this->assertNotSame(
            $result['host'],
            $result['pooled_host'],
            'Varian direct harus berbeda dari host pooler; kalau sama, pencocokan host jadi tidak berarti.'
        );

        return $result;
    }
}
