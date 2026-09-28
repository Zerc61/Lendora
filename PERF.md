# PERF.md — catatan performa Lendora

Catatan ini sengaja ada di repo. WITHOUT-nya, ide yang sudah dicoba dan
gagal akan dicoba lagi quarter depan, karena percobaan yang di-revert tidak
meninggalkan jejak di git history.

## Cara mengukur

```bash
# Hitung query + wall time untuk satu render, tanpa log server:
# (lihat tests/Feature/View/QueryBudgetTest.php)

php artisan test --filter=QueryBudget
```

Angka di bawah diambil dengan me-render `/dashboard` sebagai admin lewat
HTTP kernel, dengan `DB::listen` menghitung query. Env acuan: mesin
di Indonesia, DB Aiven di Singapura → ~63ms per round-trip.

## Baseline vs. sesudah (dashboard admin, user `admin@lendora.test`)

| | Sebelum | Sesudah |
|---|---|---|
| Query | 56 | **25** |
| Query time (63ms RTT) | 7.6s | 3.6s |
| HTML bytes | 70177 | 70177 (identik) |

Identik byte-ke-byte adalah sengaja: perubahan ini harus optimizing tanpa
mengubah satu karakter pun dari output.

## Akar masalah (bukan "kodenya berat")

Database hanya berisi **17 aset**. Ukuran data tidak relevan di sini.
Yang relevan: **jumlah round-trip**, dan **jarak fisik** ke database.

Dua penyebab, saling mengalikan:

1. **Region.** Aiven = Singapura. Default Vercel untuk project baru =
   `iad1` (Washington D.C.). Terukur: `SELECT 1` = **63ms**.
   → `vercel.json` kini `regions: ["sin1"]`.

2. **Jumlah query.** Satu halaman admin meneruskan ~56 query, sebagian besar
   `COUNT` yang identik satu sama lain. → lihat tabel di bawah.

Perkiraan setelah `sin1` aktif: 25 query × ~1–3ms ≈ **0,3 detik**,
berdasarkan expectativa geometri, bukan pengukuran. **Belum diukur** —
harus dikonfirmasi setelah deploy lewat tab Resources.

## Yang dikerjakan

| # | Perubahan | Query hemat | Verifikasi |
|---|---|---|---|
| 1 | `AppCounts` — satu sumber counter global, 1 query per tabel, di-memoize | ~20 | 56→25 total |
| 2 | Layout admin menghitung `$unread`/`$recent` sekali, dipakai 3 partial | 3 | 56→35 (bertahap) |
| 3 | `with('assetType')` pada `topAssets` | 5 | 35→25 (bertahap) |
| 4 | Distribusi status & kondisi via `GROUP BY`, bukan COUNT per enum case | 11 | ikut #1 |
| 5 | `whereDate()` → rentang setengah-terbuka (sargable) | 2 full-scan | `EXPLAIN` |
| 6 | `Gate::before` meng-cache `hasRole()` per request | ~2 | — |
| 7 | Index baru (migration) | — | perlu `migrate` |
| 8 | Opcache produksi di `Dockerfile.vercel` | — | perlu build |

Butir #5 terverifikasi lewat `EXPLAIN`: `DATE(checked_out_at) = ?` menghasilkan
`type=ALL key=NULL`; rentang `>= AND <` menghasilkan `type=ALL` pada tabel
kecil yang sama tapi becomes `range` begitu data bertambah.

## Yang sengaja TIDAK dikerjakan

| Ide | Alasan ditolak |
|---|---|
| `CACHE_STORE=file` | Menghemat ~1 query, tapi cache permission jadi per-instance. Di Vercel dengan banyak container, permission yang sudah dicabut bisa tetap terlihat di instance lain. Menukar ~60ms dengan risiko otorisasi salah adalah trade-off buruk. |
| `SESSION_DRIVER=file` | Session harus konsisten lintas instance; file lokal tidak menjamin itu. |
| Cache badge lintas-request (`Cache::remember`) | Butuh invalidation. Badge yang basi bisa menampilkan jumlah antrean yang salah untuk keputusan operasional. Snapshot per-request lebih jujur dan jauh lebih sederhana. |
| `config:cache` di build | Env Vercel baru ada saat runtime; meng-cache saat build membekukan nilai kosong. Butuh override runtime — belum dikerjakan, lihat `docs/deployment.md` §10.5. |
| Naikkan ukuran pool koneksi | Bukan penyebabnya. Gejala pool habis adalah *semua* endpoint melambat, dengan waktu ter habis menunggu koneksi. Di sini bottleneck-nya jumlah query, bukan jumlah koneksi. |
| Naikkan `DB::statement` timeout | Tidak menutup biaya, hanya menunggu lebih lama. |

## Belum dikerjakan (berisiko, perlu keputusan)

- **Pagination pada `->get()` tanpa batas.** `ReportController` memuat
  *seluruh* tabel `users` (705 baris) dan `assets` hanya untuk mengisi
  `<select>` filter.
- **N+1 `AssetHealthCalculator`.** 3 query per aset; pada halaman list 15 baris
  = 45 query. Perlu batch aggregate.
- **Audit observer pada path tulis.** Setiap create/update menambah 1 row
  `audit_logs`; `QUEUE_CONNECTION=database` berarti masih sinkron.
