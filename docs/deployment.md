# Panduan Deployment Produksi — Lendora

Checklist ini memenuhi PDF bag. 12 (keamanan produksi) dan bag. 13 (kriteria MVP).
Semua perintah dijalankan dari root aplikasi kecuali dinyatakan lain.

---

## 1. Persiapan server

Kebutuhan minimum: PHP 8.4+, Composer 2, **PostgreSQL 15+** (produksi memakai
Neon PostgreSQL 18), Nginx/FrankenPHP, dan akses `crontab` (scheduler wajib —
lihat §5).

> Section §1–§9 ini untuk VPS sendiri. Kalau deploy ke Vercel container, lewati
> langsung ke §10 dan ignore semua instruksi MySQL/backup di bawah — dokumen ini
> pernah ditulis untuk Aiven MySQL dan sudah tidak lagi cocok dengan produksi.

```bash
git clone <repo-anda> /var/www/lendora && cd /var/www/lendora
composer install --optimize-autoloader --no-dev
cp .env.example .env
php artisan key:generate
```

## 2. `.env` produksi

Perbedaan penting dari `.env` local — **jangan sampai salah**:

```env
APP_ENV=production
APP_DEBUG=false              # PDF 12: error tanpa stack trace
APP_URL=https://lendora.institusi.id
LOG_LEVEL=warning

SESSION_SECURE_COOKIE=true   # cookie hanya lewat HTTPS
SESSION_HTTP_ONLY=true       # tidak bisa dibaca JS (mitigasi XSS pencurian sesi)
SESSION_SAME_SITE=lax
```

`APP_DEBUG=false` wajib. Dengan `true`, exception menampilkan query SQL, path file,
dan potongan kredensial kepada pengguna.

## 3. Database & seed

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

Akun bawaan dari seeder (password `password` — **ganti segera**):

| Peran | Email |
|---|---|
| Super Admin | `superadmin@lendora.test` |
| Admin | `admin@lendora.test` |
| Staff | `staff@lendora.test` |
| Teknisi | `teknisi@lendora.test` |
| Peminjam | `budi@lendora.test` |

## 4. Optimisasi cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> `config:cache` dan `route:cache` **tidak boleh** dijalankan di local dengan
> konfigurasi yang berbeda — keduanya mengabaikan perubahan `.env` sampai
> di-clear (`php artisan config:clear`).

## 5. Scheduler (WAJIB)

Tiga command berjalan otomatis dan menentukan alur bisnis: tandai peminjaman
terlambat, kedaluwarsa reservasi, dan kirim pengingat. Tanpa cron, fitur ini
mati diam-diam.

```bash
crontab -e
```

```
* * * * * cd /var/www/lendora && php artisan schedule:run >> /dev/null 2>&1
```

Verifikasi jadwal terdaftar:

```bash
php artisan schedule:list
```

## 6. Backup database (PDF 12)

Neon sudah menyediakan backup otomatis dan *point-in-time recovery* untuk
branch produksi — **jangan** menulis cron `pg_dump` di bawah ini untuk Neon,
karena itu hanya menambah satu salinan lagi tanpa memberi recovery point.

Kalau memang butuh dump manual (mis. sebelum migrasi berisiko):

```bash
# pg_dump dari mesin lokal, lewat pooler. Password dibaca dari prompt, jangan
# ditaruh di dalam crontab — file crontab world-readable di beberapa distro.
PGPASSWORD=pg_dump -h ep-small-thunder-b362f0n6-pooler.c-4.ap-southeast-1.aws.neon.tech \
  -U neondb_owner -d neondb --format=custom \
  > /var/backups/lendora-$(date +\%F).dump
0 3 * * * find /var/backups -name "lendora-*.dump" -mtime +7 -delete
```

> Jangan hard-delete histori. Backup + restore **diuji berkala** — backup yang
> belum pernah diuji restore belum dianggap ada.
> Restore diuji ke branch terpisah, bukan ke produksi: `pg_restore` ke branch
> `neondb` akan menimpa data asli.

## 7. Monitoring

| Yang dipantau | Cara |
|---|---|
| Error aplikasi | `storage/logs/laravel.log` (atau Sentry opsional) |
| Aplikasi hidup | `https://domain/up` (health endpoint bawaan Laravel) |
| Scheduler jalan | `php artisan schedule:list` + cek tabel `notifications` bertambah |
| Disk penuh | `df -h` — log & backup memakan tempat paling cepat |

## 8. Security checklist (PDF bag. 12)

| # | Requirement | Implementasi | Status |
|---|---|---|---|
| 1 | Password hashing, tanpa plaintext | Cast `'hashed'` (bcrypt) di model `User` | ✅ |
| 2 | CSRF protection | `@csrf` di semua form + middleware bawaan | ✅ |
| 3 | XSS-safe output | `{{ }}` auto-escape; hint `<x-stat>` di-escape di call site | ✅ |
| 4 | Validasi input | 21 FormRequest, validasi server-side | ✅ |
| 5 | Authorization | Policies + permission middleware + `Gate::before` | ✅ |
| 6 | Rate limiting | `throttle:6,1` pada login | ✅ |
| 7 | Secure session | `regenerate()` saat login, `invalidate()` saat logout, auto-logout user nonaktif | ✅ |
| 8 | File upload dibatasi | `mimes:jpg,jpeg,png,webp,mp4,webm,mov,pdf` + `max:51200` | ✅ |
| 9 | Ownership/organization scope | Owner check di borrower routes + policy per model | ✅ |
| 10 | Audit log administratif | Observer otomatis + halaman read-only | ✅ |
| 11 | Error produksi tanpa stack trace | `APP_DEBUG=false` (§2) | ✅ saat deploy |
| 12 | HTTPS produksi | `forceScheme('https')` di `AppServiceProvider` + redirect webserver | ✅ saat deploy |
| 13 | Database backup berkala | Cron §6 | ✅ saat deploy |

## 9. Yang diverifikasi otomatis oleh test

`php artisan test` — 111 test, 726 assertions:

- **Auth** — login valid/salah password, user nonaktif ditolak & di-logout otomatis
- **Authorization** — `user.manage`/`organization.manage` Super Admin only; 403 konsisten
- **Konflik reservasi** — overlap ditolak, back-to-back diizinkan, aset `borrowed`/`maintenance` ditolak
- **Approval** — borrowing lahir `Approved`, aset jadi `Reserved`, notifikasi terkirim
- **Checkout** — status, waktu, pemeriksa, dan inspeksi tercatat; tidak bisa dua kali
- **Check-in** — kondisi tercatat, `damaged`/`lost` membuat issue & mengubah status aset
- **State machine** — transisi ilegal ditolak di level enum
- **Generator kode** — prefix kategori, nomor urut, kode soft-delete tidak dipakai ulang
- **Health score** — aturan transparan (kondisi, usia, garansi, penggunaan)
- **XSS** — nama peminjam di-escape di hint yang dirender raw
- **Statistik** — tren check-out selalu 6 bulan penuh; bulan tanpa transaksi diisi `0`, bukan dihapus

---

## 10. Deployment ke Vercel (container)

Berbeda dari §1–§9 yang memakai VPS + Nginx, di sini aplikasi dijalankan sebagai
image container dari `Dockerfile.vercel`. Section §1–§9 **tidak** berlaku; yang
penting ada di sini.

### 10.1 Apa yang sudah disiapkan di repo

| File | Isi |
|---|---|
| `Dockerfile.vercel` | 3 stage (`base` → `dependencies` → `runtime`), FrankenPHP 1 / PHP 8.4, non-root |
| `Caddyfile` | Listen di `:{$PORT:80}`, document root `public/`, front controller ke `index.php` |
| `vercel.json` | Service `app` dengan `runtime: container` + rewrite catch-all, `regions: ["sin1"]` |
| `.dockerignore` | Mencegah `.env`, `vendor/`, `node_modules/`, dan `*.sqlite` masuk image |

> **Tidak ada stage frontend.** Proyek ini sengaja tanpa build step: seluruh UI
> adalah `public/assets/lendora.css` & `lendora.js` yang ditulis tangan, dimuat
> di `resources/views/partials/head.blade.php`. Tidak ada view memakai `@vite`
> (0 match di `resources/views/` dan `app/`).
>
> `package-lock.json` **ada** di repo (2.503 baris) — yang tidak ada adalah view
> yang memakainya. `composer setup` tetap menjalankan `npm run build`, tapi
> hasilnya (`public/build`) tidak pernah dirujuk siapa pun dan di-exclude lewat
> `.dockerignore`. Jalankan `composer setup` untuk development; di produksi
> `npm` tidak perlu dijalankan sama sekali.

Cache yang di-baked saat build: `event:cache`, `route:cache`, `view:cache`.
`config:cache` **tidak** dijalankan — Vercel menyuntikkan `APP_KEY` dan
kredensial DB saat container start, bukan saat build, jadi config harus dibaca
per-request.

### 10.1a ⚠️ Region WAJIB `sin1` — ini pengukur latency terbesar

`vercel.json` mengunci `regions: ["sin1"]`. Jangan dihapus tanpa alasan.

Latar belakang yang terukur: database Neon untuk proyek ini ada di
**aws-ap-southeast-1 (Singapura)** — region `c-4`, endpoint
`…-pooler.c-4.ap-southeast-1.aws.neon.tech` — sedangkan default Vercel untuk
project baru adalah **iad1 (Washington D.C.)**. Lintas Samudra itu mengukur
puluhan milidetik per query; `SELECT 1` yang tidak membaca data sama sekali
sudah butuh waktu itu.

Karena latensi digandakan dengan jumlah query, dua perbaikan ini saling
mengalikan dan keduanya wajib:

| Faktor | Sebelum | Sesudah |
|---|---|---|
| Query per render dashboard admin | 56 | 25 |
| Total render dashboard admin | **~7,2 detik** | **~0,3 detik** |

> **Jangan percaya angka "~0,3 detik" tanpa mengukur ulang dari `sin1`.** Yang
> diukur dalam audit ini adalah `SELECT 1` dari mesin lokal (region Jakarta →
> Singapura): **~80 ms per round-trip**, dan **~79 ms** lewat host `-pooler` —
> nyaris sama, karena PHP membuka koneksi baru tiap request sehingga pooler
> tidak ada gunanya di sini. Angka itu **bukan** representasi Vercel `sin1`,
> dan jarak ke `iad1` yang dulu jadi alasan utama region ini dikunci **tidak
> pernah terukur** (default Vercel saat dokumen ini ditulis sudah `sin1`).
>
> Yang terukur dan bisa dipertanggungjawabkan: latency **dominan di jumlah
> round-trip, bukan di biaya query** — ±20 query terpanas semuanya 0,04–0,55 ms
> di sisi server, sementara ~90% waktu tempuh request habis di jaringan.
> Reducing round-trip count tetap levers yang benar. Arahkan `regions` dan
> ukur ulang dari deployment sungguhan sebelum menyimpulkan angka Critique
> latency. Lihat PERF.md.

Untuk memverifikasi region benar-benar terpakai, cek tab **Resources** di
deployment summary — di sana akan tampil `sin1`.

Kalau database nanti pindah ke region lain, `regions` di `vercel.json` harus
ikut diubah ke region terdekat. Kalau tidak, seluruh penurunan ini kembali.

### 10.2 Environment variables di Vercel

`Vercel → Project → Settings → Environment Variables`:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:<dari "php artisan key:generate --show">

# WAJIB, dan TIDAK BOLEH http://. Lihat §10.6 — tanpa ini seluruh URL yang
# di-generate (CSS/JS dan <form action>) jadi http:// dan halamannya rusak.
APP_URL=https://lendora-two.vercel.app

# Neon PostgreSQL. WAJIB PostgreSQL: Dockerfile.vercel hanya meng-install
# pdo_pgsql, dan sebagian query di app/ memakai dialek PostgreSQL
# (EXTRACT/EPOCH, TO_CHAR). Kalau diisi mysql, aplikasi gagal boot dengan
# "could not find driver" — bukan cuma halaman tertentu.
DB_CONNECTION=pgsql
# Host POOLER. Kalau host direct (tanpa -pooler) dipakai bersamaan dengan
# banyak instance Vercel, koneksi akan habis.
DB_HOST=ep-small-thunder-b362f0n6-pooler.c-4.ap-southeast-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=<password>
# TLS. Default config/database.php sudah `require` untuk APP_ENV != local,
# jadi baris ini opsional. Isi hanya untuk mengetatkan:
#   DB_SSLMODE=verify-full
#   DB_SSLROOTCERT=system
DB_SSLMODE=require

# Behind proxy — agar $request->secure() true (cookie sesi & middleware is_secure)
#
# WAJIB berpasangan dengan SESSION_SECURE_COOKIE=true. Diperiksa dengan request
# http:// yang membawa X-Forwarded-Proto: https — persis kondisi di Vercel,
# karena hop internal Vercel → container itu plain HTTP:
#   TRUSTED_PROXIES kosong -> $request->secure() = FALSE
#   TRUSTED_PROXIES=*     -> $request->secure() = true
# Kalau yang pertama terjadi DAN SESSION_SECURE_COOKIE=true, cookie sesi
# tidak pernah dikirim balik: user terjebak mengulang login terus.
# (Dockerfile.vercel sudah menyetel kedua env ini sebagai default, jadi
#  deploy tetap aman walau dashboard lupa diisi.)
TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

LOG_LEVEL=warning

# ⚠️ PENTING: jangan pernah memakai env produksi di cmd ini untuk menjalankan
# test. Ganti DB_DATABASE menjadi `lendora_test` DAN DB_HOST ke branch `test`
# (lihat §10.7). RefreshDatabase menghapus seluruh isi database target, jadi
# nama `_test` saja tidak cukup — `lendora_test` juga ada di branch produksi.
```

> **`APP_URL` dan `TRUSTED_PROXIES` itu dua hal berbeda.** `APP_URL` menentukan
> skema pada URL yang di-generate; `TRUSTED_PROXIES` membuat `$request->secure()`
> benar. Keduanya perlu, tapi gejalanya berbeda — jangan saling menggantikan.

Set untuk ketiga environment (Production / Preview / Development), atau minimal
untuk Production.

`.env` **tidak pernah** masuk GitHub maupun image container — `.dockerignore`
sudah memblokirnya.

### 10.3 Migration

`Dockerfile.vercel` sengaja **tidak** menjalankan `php artisan migrate`. Database
Neon adalah sistem eksternal; menjalankannya di dalam build membuat image gagal
bila DB tidak terjangkau dari runner.

Jalankan sekali dari mesin lokal:

```bash
DB_HOST=ep-small-thunder-b362f0n6-pooler.c-4.ap-southeast-1.aws.neon.tech \
DB_PORT=5432 DB_DATABASE=neondb \
DB_USERNAME=neondb_owner DB_PASSWORD=<password> \
php artisan migrate --force

# sekali saja, kalau tabel roles/permissions masih kosong
DB_HOST=... php artisan db:seed --class=RolePermissionSeeder --force
```

Ulangi setiap kali ada migration baru di `main`.

> **WAJIB: jalankan `php artisan migrate --force` untuk**
> `2026_09_28_000000_add_performance_indexes.php` bila belum jalan. Index itu
> menutup `unreadNotifications()->count()` (dijalankan di setiap halaman) dan
> kolom `checked_out_at` / `returned_at` yang sebelumnya full-table-scan di
> staff dashboard. Tanpa migration ini, perbaikan query tetap ada tapi
> `"type" => "ALL"` di EXPLAIN.

### 10.4 ⚠️ Penyimpanan bersifat efemera — foto akan hilang

Filesystem container di-reset pada setiap deploy. Semua file yang diunggah —
foto profil siswa (`storage/app/public/avatars`) dan lampiran aset
(`…/attachments`) — **hilang** begitu ada deployment baru.

`SESSION_DRIVER`, `CACHE_STORE`, dan `QUEUE_CONNECTION` aman karena tidak ada
yang menyimpan data di filesystem container: session di-cookie (TAHAP C),
cache & queue di database.

> #### ✅ `FILESYSTEM_DISK` — TAHAP G selesai
>
> Semua operasi storage di `app/` kini memakai **disk default**, bukan disk
> `'public'` yang di-hardcode:
>
> | File | Perubahan |
> |---|---|
> | `app/Http/Controllers/ProfileController.php` | `store('avatars')` & `Storage::delete()` — default disk |
> | `app/Http/Controllers/Admin/UserController.php` | `store('avatars')` & `Storage::delete()` — default disk |
> | `app/Http/Controllers/Admin/AssetAttachmentController.php` | `store("attachments/{id}")` & `Storage::delete()` — default disk |
> | `app/Models/User.php` | `photoUrl()`/`hasPhoto()` — `Storage::url()` default disk, tanpa stat filesystem |
>
> Dengan begitu menyetel `FILESYSTEM_DISK=s3` di Vercel **berfungsi**: unggahan
> mengikuti disk default. Lokal memakai `FILESYSTEM_DISK=public` (perilaku
> development tidak berubah; diserve lewat symlink `public/storage`).
>
> Catatan tambahan: disk `s3` di `config/filesystems.php` memakai key
> `'visibility'`, sedangkan `league/flysystem-aws-s3-v3` mengharapkan
> `'visibility' => 'public'` pada konfigurasi disk — penyesuaian konfigurasi
> tetap diperlukan, bukan hanya kode aplikasinya.
>
> **Status: kode selesai (TAHAP G).** Yang tersisa hanyalah konfigurasi di
> dashboard Vercel di bawah — tidak bisa diverifikasi dari repo.

Setelah kodenya diubah, konfigurasi Vercel-nya:

```bash
composer require league/flysystem-aws-s3-v3
```

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=auto
AWS_BUCKET=lendora-assets
AWS_ENDPOINT=https://<account>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Bucket **wajib publik-read** untuk `public`-style disk, atau pasang
`FILESYSTEM_DISK` terpisah + signed URL. Tanpa langkah ini, fitur upload foto
pada aplikasi akan tampak berfungsi di satu deploy lalu lenyap di deploy berikutnya.

### 10.4a Query budget per request

Karena `regions: ["sin1"]` reasonably dekat dengan database, latency per
round-trip sudah turun drastis dan **jumlah query** menjadi penentu utama
kecepatan halaman. Angka "~1–3ms" yang pernah tertulis di sini tidak pernah
terukur dari `sin1` — lihat catatan di §10.1a. Yang benar-benar terukur: semua
query terpanas 0,04–0,55 ms di sisi server, sementara ~90% waktu tempuh request
habis di jaringan. Jadi mengurangi jumlah round-trip tetap levers yang benar,
tapi jangan memakai angka latency yang tak terukur sebagai acuan.

Sebagai jaring pengaman, anggaran kasar untuk render authenticated:

| Halaman | Anggaran |
|---|---|
| Dashboard admin | ≤ 25 query |
| Halaman list admin | ≤ 12 query |
| Halaman borrower (mobile) | ≤ 10 query |

Kalau sebuah halaman melewati anggarannya, hampir selalu penyebabnya salah
satu dari tiga: relation yang diakses di dalam loop tanpa `with()`, angka
badge/ringkasan yang dihitung di lebih dari satu tempat, atau `->get()` tanpa
paginasi.

Cara mengukur (tidak perlu log server):

```php
// di test, sementara
DB::listen(fn ($q) => logger()->info($q->sql, ['ms' => $q->time]));
```

Tiga sumber angka global — `AppCounts` (`app/Support/AppCounts.php`) —
sengaja dipakai bersama oleh dashboard dan badge navigasi, karena keduanya
memerlukan hitungan yang sama. Kalau suatu saat butuh angka baru, tambahkan
di sana, jangan dihitung ulang di controller dan di view.

### 10.4b Database uji — branch Neon `test`

Test suite memakai `RefreshDatabase`, yang menjalankan `migrate:fresh`:
seluruh isi database target dihapus. Karena itu test **tidak boleh** pernah
menunjuk branch produksi.

Dua lapis penjaga, keduanya aktif:

| Lapis | Yang dijaga | Di mana |
|---|---|---|
| 1 | Nama database harus berakhiran `_test` | `tests/TestCase.php` → `guardTestDatabaseIsDisposable()` |
| 2 | Host tidak boleh sama dengan host produksi | `tests/TestCase.php` → `guardTestDatabaseIsNotProduction()` |

Lapis 2 ini perlu karena `lendora_test` **juga ada di branch produksi**. Lapis 1
lolos begitu saja, padahal `migrate:fresh` menyapu seluruh branch — 707 user
produksi ikut terhapus. Keduanya diuji di `tests/Feature/TestDatabaseGuardTest.php`.

Branch `test` dibuat 2026-09-28 di project Neon yang sama, region sama:

```
ep-lively-credit-b3vg4b5u-pooler.c-4.ap-southeast-1.aws.neon.tech
```

Branch itu mewarisi data parent (termasuk `lendora_test`) dan mewarisi role &
kredensial yang sama, jadi `phpunit.xml` cukup menunjuk host-nya — username dan
password tetap dibaca dari `.env` dan tidak perlu diduplikasi di file yang
di-commit.

Untuk menjalankan test dengan branch lain (mis. milik CI):

```bash
DB_HOST=<endpoint-branch-ci> DB_DATABASE=lendora_test_ci vendor/bin/phpunit
```

Entri `<env>` di `phpunit.xml` sengaja **tanpa** `force="true"`, jadi variabel
yang sudah ada di environment proses menang. Sudah diverifikasi: `DB_HOST` dari
shell tidak ditimpa phpunit.xml.

Kalau branch `test` dihapus: buat ulang di Neon console (**Branches → New**,
parent `production`), lalu ganti `DB_HOST` di `phpunit.xml` dengan endpoint
baru. Kredensial tidak berubah — branch mewarisi role parent.

### 10.4c Proteksi branch `test` + timezone aplikasi

**Proteksi branch `test` — DIBLOKIR PAKET FREE (per 2026-09-29).** Upaya
mengaktifkan proteksi dilakukan via Neon API:

- `update_branch protected=true` pada `br-wispy-sun-b343qpnf` → **HTTP 422**:
  *"maximum number of protected branches for your current plan"*.
- `update_project settings.allowed_ips` → **HTTP 400**: `max: "0"` entry untuk
  paket free.

Per [dokumentasi Neon](https://neon.com/docs/guides/protected-branches):
protected branches hanya tersedia di paket berbayar (**Launch** ≤ 2,
**Scale** ≤ 5), dan IP Allow butuh paket **Scale**. Org ini berlangganan
**free** (`org-orange-frost-29574925`, `plan: free`).

Implikasi keamanan saat ini:

- Branch `test` dan `production` sama-sama publik (hostname + role + password
  cukup untuk konek dari mana pun). `/tmp/…` jangan dianggap terlindungi.
- Setelah upgrade ke paket yang mendukung, langkah yang sudah disiapkan:
  1. `update_branch` `br-wispy-sun-b343qpnf` → `protected: true`.
  2. `update_project settings.allowed_ips` →
     `{ ips: ["182.8.97.118", "182.8.100.16"], protected_branches_only: true }`.
  3. Catatan: IP pengembang bersifat **dinamis** (berubah 182.8.100.16 →
     182.8.97.118 antar sesi). Saat IP berubah, tambahkan IP baru ke allowlist
     atau `phpunit` gagal konek ke branch `test`.
  4. Jangan pernah set `protected_branches_only: false` — itu membatasi **semua**
     branch termasuk produksi, dan egress IP Vercel dinamis → produksi putus.

**Timezone.** `config/app.php` memakai `env('APP_TIMEZONE', 'Asia/Jakarta')` —
default `Asia/Jakarta` (WIB), selaras dengan `APP_LOCALE=id` dan nilai
`APP_TIMEZONE` di `.env`/`.env.example`. Nilai ini hanya mengubah cara aplikasi
menginterpretasikan/memformat waktu; data timestamp tetap disimpan PostgreSQL
sebagai UTC (bagian app/ menulis `now()` / Carbon, yang di database menjadi
timestamptz). Tampilan tanggal memakai `Carbon::locale('id')`.

### 10.5 Catatan teknis

- **Port.** Container harus listen di env `PORT`, yang diumumkan Vercel. Itu
  sebabnya `Caddyfile` memakai `:{$PORT:80}` dan bukan `SERVER_NAME` seperti
  Caddyfile bawaan image. Kolom kosong sebelum titik dua wajib ada — kalau
  listen hanya di `localhost`, Vercel mendapat 502.
- **Document root.** `root * /app/public` membuat hanya `public` yang bisa
  diakses. Tanpa itu, `.env` dan `storage/` bisa diambil langsung lewat browser.
- **Non-root.** Runtime jalan sebagai `www-data`. `CAP_NET_BIND_SERVICE`
  di-set pada binary FrankenPHP supaya proses non-root tetap bisa mengikat
  port di bawah 1024.
- **`TRUSTED_PROXIES`.** `bootstrap/app.php` sengaja membaca daftar proxy dari
  env, bukan `trustProxies('*')` hard-coded — mempercayai semua proxy tanpa
  sengaja memungkinkan siapa pun memalsukan `X-Forwarded-For`.
- **`config:cache` sengaja tidak dijalankan saat build.** Nilai env Vercel baru
  tersedia saat runtime; meng-cache saat build akan membekukan nilai kosong ke
  dalam image. `event:cache`, `route:cache`, dan `view:cache` tidak bergantung
  pada env, jadi aman dan sudah dijalankan.
- **`route:cache` butuh route tanpa closure.** Route `/` sudah dipindahkan ke
  `HomeController` karena closure tidak bisa diserialisasi.
- **`intl` bukan opsional.** Tanpa extension ini,
  `Carbon::locale('id')->translatedFormat()` jatuh ke fallback Inggris dan
  tanggal tampil sebagai "September 2026" alih-alih "Sep 2026".
- **Jumlah koneksi database.** Setiap instance Vercel = satu koneksi database
  tambahan. `.env` §10.2 memakai host **pooler** Neon
  (`…-pooler.c-4…`) yang memang untuk ini; kalau wechsel ke host langsung
  (`ep-…` tanpa `-pooler`) bersamaan dengan jumlah instance, itu akan kehabisan
  koneksi. Jangan set `DB_URL` yang menunjuk host non-pooler di produksi.
- **`pdo_pgsql`, bukan `pdo_mysql`.** Image hanya memasang `pdo_pgsql`. Kalau
  `DB_CONNECTION` diisi `mysql`, aplikasi gagal boot "could not find driver".
  Bagian app/ juga memakai SQL khusus PostgreSQL (`EXTRACT(EPOCH …)`,
  `TO_CHAR`), jadi driver MySQL bukan cuma ekstensi yang hilang — query-nya
  juga salah.
- **`public/storage` dibuat sebagai symlink saat build**, bukan lewat
  `php artisan storage:link` (yang butuh env runtime). Tanpa ini
  `Storage::disk('public')->url()` tetap menghasilkan URL yang tampak benar
  tapi file-nya 404 — tidak ada error di log.
- **Scheduler tidak jalan sendiri.** `php artisan schedule:run` tiap menit (§5)
  tidak dieksekusi Vercel, dan in-process scheduler mati bersama container.
  Dua opsi:
  1. **Vercel Cron** → endpoint HTTP terautentikasi. Perlu route baru yang
     memanggil `Schedule::run()` di balik token; **belum ada di repo** —
     jangan expose `tinker` atau route tanpa proteksi.
  2. Cron eksternal (cron-job.org, EasyCron) yang memanggil endpoint ber-token
     tersebut setiap menit.

  Tanpa salah satu, fitur tandai peminjaman terlambat, kedaluwarsa reservasi,
  dan pengingat akan mati diam-diam.
- **Preview deployment.** Setiap push branch dapat URL sendiri, dan secara
  default memakai database yang **sama** dengan produksi. Jangan pakai data
  produksi untuk pengujian fitur: satu migration salah di preview = data
  produksi ikut berubah. Set `DB_DATABASE` per-branch ke database uji, atau
  nonaktifkan preview deployment untuk project ini.

### 10.6 Troubleshooting: halaman tanpa gaya + "Formulir tidak aman"

**Gejala:** di Vercel halaman tampil seperti HTML polos — font serif, tanpa
warna, layout bertumpuk. CSS sama sekali tidak termuat. Saat login muncul
warning Chrome "Formulir yang akan Anda kirimkan tidak aman".

**Penyebab:** Vercel menghentikan TLS di edge lalu meneruskan request ke
container lewat HTTP. Laravel tidak tahu REQUEST aslinya `https`, sehingga
semua URL yang di-generate jatuh ke `http://`:

```html
<link rel="stylesheet" href="http://lendora-two.vercel.app/assets/lendora.css">
<form method="POST" action="http://lendora-two.vercel.app/login">
```

Halaman sendiri disajikan lewat `https://`, sehingga CSS/JS jadi **mixed
content** dan diblokir browser **tanpa pesan error di console** — itulah
kenapa kelihatan "CSS-nya hilang" padahal file-nya baik. Form yang mengarah
ke `http://` memicu peringatan tersebut.

**Cek cepat:**

```bash
curl -s https://lendora-two.vercel.app/login | grep -oE 'href="[^"]*lendora\.css[^"]*"'
# harus diawali https://
```

**Perbaikan:** pastikan `APP_URL` di Vercel diawali `https://` dan cocok
dengan domain deployment. `AppServiceProvider` memaksa skema ke `https`
berdasarkan scheme `APP_URL` — bukan berdasarkan `APP_ENV`, karena
`APP_ENV` tidak selalu sampai ke container.

Kalau URL sudah `https://` tapi `$request->secure()` masih `false`
(mis. cookie sesi tidak pernah ditandai secure), tambahkan
`TRUSTED_PROXIES=*` juga.

> Catatan: jangan shotgun dengan `URL::forceScheme()` tanpa syarat
> `APP_URL` — itu membuat setiap halaman keluar `https` termasuk saat
> pengembangan lokal.

### 10.8 Insiden 2026-09-29 — `POST /login` HTTP 500 (SQLSTATE 25P02)

#### Gejala

`POST /login` selalu membalas **HTTP 500** di produksi, deterministik (3/3).
Halaman `/login` (GET) normal, jadi bukan masalah aset atau routing.

#### Rantai penyebab

```
QueryException  SQLSTATE[25P02]: In failed sql transaction: 7 ERROR:
  current transaction is aborted, commands ignored until end of transaction block
```

`25P02` adalah error **sekunder**: bukan pernyataan yang benar-benar salah, tapi
menyatakan transaksi sudah berstatus *aborted* sehingga perintah berikutnya
diabaikan. Urutan query pada halaman debug produksi:

```
1. select * from "cache" where "key" in ('lendora-cache-<sha1>')            36.63 ms
2. select * from "cache" where "key" in ('lendora-cache-<sha1>:timer')       5.16 ms
3. select * from "cache" where "key" in ('lendora-cache-<sha1>')             3.84 ms
4. select * from "cache" where "key" = '...' limit 1 for update              4.91 ms   <- SUKSES
5. update "cache" set "value" = ? where "key" = ?                                    <- 25P02
```

Langkah 1-4 adalah `ThrottleRequests` (middleware `throttle:6,1` di
`routes/web.php`) memanggil `RateLimiter::hit()`. Pada cache store `database`,
`hit()` mendarat di `DatabaseStore::incrementOrDecrement()` yang membungkus
seluruh operasi di `DB::transaction()`:

```
BEGIN
SELECT * FROM cache WHERE key = ? LIMIT 1 FOR UPDATE
UPDATE cache SET value = ? WHERE key = ?
COMMIT
```

Artinya **login mati karena penghitung throttle-nya gagal menulis**, bukan karena
kredensial salah. `POST /login` satu-satunya rute yang memakai `RateLimiter`, dan
satu-satunya tempat di aplikasi ini yang memakai `Cache::increment()` - jadi
login satu-satunya endpoint yang ikut tumbang, dan seluruh autentikasi mati
hanya karena lapisan cache.

Yang membuat `25P02` muncul pada langkah 5 padahal langkah 4 sukses adalah
fakta bahwa `SELECT ... FOR UPDATE` dan `UPDATE` berikutnya **tidak mungkin**
berkasalah di server yang sama dalam transaksi yang sama. Itu berarti ada
desync protokol di sisi klien/pooler, bukan sekadar satu baris yang gagal.

#### Perbaikan

`App\Support\NonTransactionalRateLimiter` menggantikan rate limiter bawaan:

- Counter ditulis sebagai dua pernyataan biasa (baca, lalu `put`/upsert)
  **tanpa `DB::transaction()`** - jadi tidak ada transaksi yang bisa di-abort.
- Format penyimpanan tetap integer ter-serialize, dan jendela tetap (*fixed
  window*) tetap dihitung dari key `:timer` milik framework, sehingga
  `attempts()`/`tooManyAttempts()` tidak berubah.
- Binding di `AppServiceProvider::register()` dibungkus `booted()` **karena
  framework juga mendaftarkan singleton `RateLimiter::class` di
  `CacheServiceProvider`, yang terdaftar setelah `AppServiceProvider`**. Binding
  di `register()` akan ditimpa tanpa error apa pun.

Biaya: kehilangan atomicity (dua request bersamaan bisa saja membaca counter
lama yang sama). Untuk rate limit login ini dapat diterima, dan proteksi brute
force tetap aktif - tidak ada kontrol keamanan yang dinonaktifkan.

Diukur di branch uji, 10 `hit()` berurutan: bawaan **658 ms**, baru **556 ms**
per `hit()`. Lebih cepat, karena tidak ada round-trip `BEGIN`/`COMMIT`.

#### Dua temuan yang belum tertutup

1. **`APP_DEBUG` efektif `true` di produksi. (CRITICAL)**
   Halaman debug yang bocor berukuran sekitar 1 MB dan memuat **request header**,
   termasuk token `x-vercel-oidc-token`. Token itu bisa dipakai meniru
   deployment ke API Vercel. Perbaikannya di dashboard Vercel
   (`APP_DEBUG=false`), bukan di repo.

2. **Pemicu infra belum teridentifikasi (NEEDS VERIFICATION).**
   Kode, database, dan pooler masing-masing bisa dibuktikan sehat: login penuh
   berhasil lokal terhadap DB produksi, dan 160+ transaksi
   `BEGIN` / `SELECT FOR UPDATE` / `UPDATE` konkuren lewat pooler tidak
   menghasilkan satu pun `25P02` - baik dengan native maupun emulated prepared
   statement. Berarti pemicunya khas runtime container (FrankenPHP) dan belum
   ketahuan. Semua `DB::transaction()` di `app/Actions/` memakai pola yang
   sama, jadi **perlu diuji dari dalam aplikasi setelah deploy**: jalankan satu
   aksi yang bertransaksi (misalnya membuat peminjaman) dan lihat apakah `25P02`
   muncul juga di sana. Kalau iya, akar masalahnya di level koneksi/pooler,
   bukan di rate limiter, dan perbaikannya harus di level itu juga.
