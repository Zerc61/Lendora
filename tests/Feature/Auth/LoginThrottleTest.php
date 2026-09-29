<?php

// tests/Feature/Auth/LoginThrottleTest.php

namespace Tests\Feature\Auth;

use App\Support\NonTransactionalRateLimiter;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Mengunci sifat throttle login terhadap cache store `database`.
 *
 * Latar belakang: di produksi, rate limiter bawaan framework menambah counter
 * lewat `Cache::increment()` yang dibungkus `DB::transaction()`. Lewat
 * connection pooler Neon/PgBouncer itu memunculkan SQLSTATE 25P02 dan
 * menjatuhkan `POST /login` menjadi HTTP 500. Test di sini menjaga dua hal:
 * (a) throttle tidak boleh membuka transaksi database, (b) proteksi brute
 * force tetap bekerja -- perbaikan jangan melemahkan kontrol keamanan.
 *
 * Catatan: test memaksa store `database` secara eksplisit, bukan lewat
 * `cache.default`, karena phpunit.xml memakai `CACHE_STORE=array` yang memang
 * tidak pernah membuka transaksi -- memakainya membuat pengujian jadi hampa.
 */
class LoginThrottleTest extends TestCase
{
    public function test_rate_limiter_resolved_from_container_is_non_transactional(): void
    {
        // Penjaga untuk jebakan yang pernah terjadi: framework mendaftarkan
        // singleton RateLimiter sendiri di CacheServiceProvider, yang
        // terdaftar SESUDAH AppServiceProvider. Binding di register() akan
        // ditimpa tanpa error apa pun, dan test throttle di bawah tetap hijau
        // karena memaksa store secara manual. Test ini yang menangkapnya.
        $this->assertInstanceOf(
            NonTransactionalRateLimiter::class,
            app(RateLimiter::class),
            'Binding RateLimiter di container ditimpa provider framework.'
        );
    }

    public function test_login_throttle_never_opens_a_database_transaction(): void
    {
        $this->useDatabaseCacheStore();

        $began = 0;
        DB::connection()->beforeStartingTransaction(function () use (&$began): void {
            $began++;
        });

        $user = $this->makeUser('borrower', $this->makeOrg());

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect();

        $this->assertSame(
            0,
            $began,
            'Throttle login tidak boleh membuka transaksi database (insiden 25P02).'
        );
    }

    public function test_login_throttle_still_blocks_after_six_attempts(): void
    {
        $this->useDatabaseCacheStore();

        $user = $this->makeUser('borrower', $this->makeOrg());

        for ($i = 1; $i <= 6; $i++) {
            $this->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_login_with_database_cache_store_still_authenticates(): void
    {
        $this->useDatabaseCacheStore();

        $user = $this->makeUser('borrower', $this->makeOrg());

        $response = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_counter_does_not_extend_the_fixed_window(): void
    {
        $this->useDatabaseCacheStore();

        $limiter = app(RateLimiter::class);

        // Key unik per test supaya tidak bertabrakan dengan sisa key cache
        // di branch uji, dan bebas karakter wildcard SQL.
        $key = 'test-window-'.uniqid();

        $this->assertSame(1, $limiter->hit($key, 60));
        $windowEndsAt = $this->counterExpiration($limiter, $key);

        $this->assertGreaterThan(
            time(),
            $windowEndsAt,
            'Counter harus punya masa berlaku di masa depan.'
        );

        // Percobaan berikutnya di tengah jendela tidak boleh menggeser akhir
        // jendela. Inilah yang membedakan fixed window dari sliding window:
        // tanpa penjagaan ini, penyerang yang mencoba tiap 30 detik tidak
        // pernah terkunci.
        $this->assertSame(2, $limiter->hit($key, 60));

        $this->assertLessThanOrEqual(
            $windowEndsAt,
            $this->counterExpiration($limiter, $key),
            'Jendela waktu tidak boleh diperpanjang oleh percobaan berikutnya.'
        );
    }

    /**
     * Baca kolom `expiration` baris counter (bukan key `:timer`) dari tabel
     * `cache`. Memakai cleaned key dari rate limiter karena
     * `cleanRateLimiterKey()` framework hanya menjalankan htmlentities --
     * tidak menghash seperti pada versi Laravel sebelumnya.
     */
    private function counterExpiration(RateLimiter $limiter, string $key): int
    {
        $clean = $limiter->cleanRateLimiterKey($key);

        $row = DB::table('cache')
            ->where('key', 'like', '%'.$clean)
            ->get()
            ->first(fn ($candidate) => ! str_ends_with($candidate->key, ':timer'));

        $this->assertNotNull($row, 'Counter throttle harus tersimpan di tabel cache.');

        return (int) $row->expiration;
    }

    /**
     * Pasang rate limiter yang memakai cache store `database` sungguhan.
     *
     * Diblokir per-test lewat `instance()`, jadi tidak perlu menyentuh
     * `cache.default` global dan tidak bergantung pada urutan resolusi
     * container di dalam framework testing.
     */
    private function useDatabaseCacheStore(): void
    {
        $this->app->instance(
            RateLimiter::class,
            new NonTransactionalRateLimiter(Cache::store('database'))
        );
    }
}
