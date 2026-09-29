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
| 9 | `SESSION_DRIVER` database → **cookie** (TAHAP C) | 2 per request | ~160 ms/request dari Jakarta |
| 10 | Cache badge lintas-request, TTL **45 dtk** di `AppCounts` (TAHAP C, disetujui) | ~4 per request | store `database` — lihat catatan di bawah |
| 11 | `hasPhoto()` tanpa stat filesystem (TAHAP C) | IO per avatar | — |
| 12 | Layout staff/teknisi: `$unread`/`$recent` dihitung sekali dan diteruskan (TAHAP C) | dedup eksplisit | parity dengan admin |
| 13 | TAHAP G: storage memakai disk default (bukan `'public'` hardcoded) | konsisten S3 | `FILESYSTEM_DISK` |

Butir #5 terverifikasi lewat `EXPLAIN`: `DATE(checked_out_at) = ?` menghasilkan
`type=ALL key=NULL`; rentang `>= AND <` menghasilkan `type=ALL` pada tabel
kecil yang sama tapi becomes `range` begitu data bertambah.

Catatan #10 — trade-off yang disetujui eksplisit: badge boleh basi ≤ 45 dtk
demi mengurangi round-trip identik per halaman. Memo per request (+ flush lewat
`RequestHandled`) tetap ada; cache hanya lapisan di bawahnya. Saat test
(`runningUnitTests()`) lapisan ini dilewati supaya angka selalu segar.

## Yang sengaja TIDAK dikerjakan

| Ide | Alasan ditolak |
|---|---|
| `CACHE_STORE=file` | Menghemat ~1 query, tapi cache permission jadi per-instance. Di Vercel dengan banyak container, permission yang sudah dicabut bisa tetap terlihat di instance lain. Menukar ~60ms dengan risiko otorisasi salah adalah trade-off buruk. |
| `SESSION_DRIVER=file` | Session harus konsisten lintas instance; file lokal tidak menjamin itu. |
| Cache badge pakai `array`/`file` | Store-nya tetap `database` (satu sumber kebenaran lintas instance). `file` = inkonsisten antar container; `array` = tidak lintas request. |
| `config:cache` di build | Env Vercel baru ada saat runtime; meng-cache saat build membekukan nilai kosong. Butuh override runtime — belum dikerjakan, lihat `docs/deployment.md` §10.5. |
| Naikkan ukuran pool koneksi | Bukan penyebabnya. Gejala pool habis adalah *semua* endpoint melambat, dengan waktu ter habis menunggu koneksi. Di sini bottleneck-nya jumlah query, bukan jumlah koneksi. |
| Naikkan `DB::statement` timeout | Tidak menutup biaya, hanya menunggu lebih lama. |
| Dropdown filter users di laporan → paginated | 705 baris × 2 kolom = ~30 KB HTML. Paginate `<select>` merusak UX filter; bukan biaya yang dominan. Tetap diamati. |

## Belum dikerjakan (berisiko, perlu keputusan)

- **Audit observer pada path tulis.** Setiap create/update menambah 1 row
  `audit_logs`; `QUEUE_CONNECTION=database` berarti masih sinkron. (Di luar
  scope TAHAP A–G.)
- **`Gate::before` meng-cache status super-admin per proses.** `static $superAdmin`
  di `AppServiceProvider` diisi dari user PERTAMA yang dicek — di produksi itu
  selalu request yang sama (aman), tapi di test yang memakai satu proses untuk
  banyak request/percobaan bisa salah. Saat ini `RefreshDatabase` + container
  fresh per test membuat dampaknya minim; dicatat lebih dulu sebelum diubah.
- ~~**N+1 `AssetHealthCalculator`.** 3 query per aset pada halaman list~~
  **BATAL — klaim basi.** `calculate()` kini HANYA dipakai di halaman detail
  (1 aset = 3 query, wajar). Halaman list tidak pernah menampilkan skor
  kesehatan per baris. Tidak ada N+1 yang perlu di-agregasi, dan menambah
  batch method yang tidak terpakai justru dead code.
- ~~**Pagination pada `->get()` tanpa batas.**~~ **Dinilai LOW** — satu-satunya
  `->get()` besar adalah dropdown filter laporan (lihat tabel "TIDAK
  dikerjakan"); sisanya master-data kecil.
