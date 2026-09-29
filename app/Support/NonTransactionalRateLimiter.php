<?php

// app/Support/NonTransactionalRateLimiter.php

namespace App\Support;

use Illuminate\Cache\RateLimiter;

/**
 * Rate limiter yang tidak memakai transaksi database.
 *
 * LATAR BELAKANG (insiden produksi 2026-09-29)
 * -------------------------------------------
 * Rate limiter bawaan framework menambah counter lewat `Cache::increment()`.
 * Untuk cache store `database`, method itu mendarat di
 * `DatabaseStore::incrementOrDecrement()` yang membungkus operasi di
 * `DB::transaction()`:
 *
 *     BEGIN
 *     SELECT * FROM cache WHERE key = ? LIMIT 1 FOR UPDATE
 *     UPDATE cache SET value = ? WHERE key = ?
 *     COMMIT
 *
 * Di produksi (Neon PostgreSQL lewat connection pooler / PgBouncer) satu
 * pernyataan yang gagal di tengah transaksi membuat seluruh transaksi
 * berstatus "aborted". Gejalanya muncul sebagai SQLSTATE 25P02
 * ("current transaction is aborted, commands ignored until end of transaction
 * block") pada pernyataan BERIKUTNYA -- bukan pada pernyataan yang benar-benar
 * gagal. Akibatnya satu gangguan pada lapisan cache menjatuhkan
 * `POST /login` menjadi HTTP 500: seluruh autentikasi mati karena penghitung
 * throttle-nya gagal menulis.
 *
 * Di sini counter ditulis sebagai dua pernyataan biasa (baca, lalu
 * tulis/upsert) tanpa `DB::transaction()`. Tidak ada transaksi yang bisa
 * di-abort, jadi tidak ada 25P02 yang mungkin terjadi.
 *
 * YANG DIJAGA
 * -----------
 * 1. Format penyimpanan tetap integer ter-serialize di kolom `cache.value`,
 *    sehingga `attempts()`, `tooManyAttempts()`, dan `resetAttempts()` dari
 *    kelas induk bekerja tanpa perubahan.
 * 2. Jendela waktu tetap (fixed window) tetap dihitung dari key `:timer`
 *    milik framework, sehingga percobaan keenam tidak memperpanjang jendela.
 * 3. `add(':timer')` tetap dipanggil agar window dimulai pada percobaan
 *    pertama, persis seperti implementasi induk.
 *
 * TRADE-OFF YANG SENGAJA
 * ----------------------
 * Keatomic-an hilang: dua permintaan bersamaan bisa saja membaca counter lama
 * yang sama lalu menulis nilai yang sama, sehingga hitungan bisa kurang satu
 * untuk sesaat. Untuk rate limit login ini dapat diterima -- tanpa perubahan
 * ini halaman login tidak bisa dipakai sama sekali -- dan jendela penuh 60
 * detik tetap berlaku.
 */
class NonTransactionalRateLimiter extends RateLimiter
{
    /**
     * Tambah counter percobaan tanpa membuka transaksi database.
     *
     * Menggantikan `RateLimiter::increment()` yang rely pada `Cache::increment()`.
     */
    public function increment($key, $decaySeconds = 60, $amount = 1)
    {
        $key = $this->cleanRateLimiterKey($key);

        // Jendela tetap dimulai pada percobaan pertama. `add()` hanya menulis
        // kalau key belum ada, jadi jendela tidak bergeser tiap percobaan.
        $this->cache->add($key.':timer', $this->availableAt($decaySeconds), $decaySeconds);

        // Sisa detik jendela berjalan. Membaca `:timer` milik framework
        // membuat TTL penulisan kembali tepat ke akhir window asal, bukan
        // "sekarang + decay" (yang akan membuat jendela selalu bergeser).
        $availableAt = (int) $this->cache->get($key.':timer');
        $remaining = $availableAt > 0
            ? max(1, $availableAt - $this->availableAt(0))
            : (int) $decaySeconds;

        $next = (int) $this->cache->get($key, 0) + $amount;

        $this->cache->put($key, $next, $remaining);

        return $next;
    }
}
