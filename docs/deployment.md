# Panduan Deployment Produksi — Lendora

Checklist ini memenuhi PDF bag. 12 (keamanan produksi) dan bag. 13 (kriteria MVP).
Semua perintah dijalankan dari root aplikasi kecuali dinyatakan lain.

---

## 1. Persiapan server

Kebutuhan minimum: PHP 8.3+, Composer 2, MySQL 8+/MariaDB 10.6+, Nginx/Apache,
dan akses `crontab` (scheduler wajib — lihat §5).

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

Cron harian pukul 02.00, simpan terkompresi, rotasi 7 hari:

```
0 2 * * * mysqldump -u lendora_user -p'PASSWORD' lendora | gzip > /var/backups/lendora-$(date +\%F).sql.gz
0 3 * * * find /var/backups -name "lendora-*.sql.gz" -mtime +7 -delete
```

> Jangan hard-delete histori. Backup + restore **diuji berkala** — backup yang
> belum pernah diuji restore belum dianggap ada.
> Opsional (P2): `composer require spatie/laravel-backup` untuk backup otomatis
> ke S3/disk lain.

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
> di `resources/views/partials/head.blade.php`. Tidak ada view memakai `@vite`,
> dan `package-lock.json` pun tidak ada — sehingga `npm ci` pasti gagal di build.

Cache yang di-baked saat build: `event:cache`, `route:cache`, `view:cache`.
`config:cache` **tidak** dijalankan — Vercel menyuntikkan `APP_KEY` dan
kredensial DB saat container start, bukan saat build, jadi config harus dibaca
per-request.

### 10.1a ⚠️ Region WAJIB `sin1` — ini pengukur latency terbesar

`vercel.json` mengunci `regions: ["sin1"]`. Jangan dihapus tanpa alasan.

Latar belakang yang terukur: database Aiven untuk proyek ini ada di
**Singapura**, sedangkan default Vercel untuk project baru adalah
**iad1 (Washington D.C.)**. Lintas Samudra itu mengukur **~60ms per query** —
`SELECT 1` yang tidak membaca data sama sekali sudah membutuhkan waktu itu.

Karena latensi digandakan dengan jumlah query, dua perbaikan ini saling
mengalikan dan keduanya wajib:

| Faktor | Sebelum | Sesudah |
|---|---|---|
| Round-trip per query | ~60ms (iad1 → Singapura) | ~1–3ms (sin1 → Singapura) |
| Query per render dashboard admin | 56 | 25 |
| Total render dashboard admin | **~7,2 detik** | **~0,3 detik** |

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

DB_CONNECTION=mysql
DB_HOST=<host Aiven>
DB_PORT=3306
DB_DATABASE=defaultdb
DB_USERNAME=avnadmin
DB_PASSWORD=<password>

# Behind proxy — agar $request->secure() true (cookie sesi & middleware is_secure)
TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

LOG_LEVEL=warning
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
Aiven adalah sistem eksternal; menjalankannya di dalam build membuat image gagal
bila DB tidak terjangkau dari runner.

Jalankan sekali dari mesin lokal:

```bash
DB_HOST=<host Aiven> DB_PORT=3306 DB_DATABASE=defaultdb \
DB_USERNAME=avnadmin DB_PASSWORD=<password> \
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

`SESSION_DRIVER`, `CACHE_STORE`, dan `QUEUE_CONNECTION` aman karena ketiganya
sudah `database`, bukan file.

Solusinya: pindahkan ke object storage (S3 / Cloudflare R2 / Supabase Storage).

```bash
composer require league/flysystem-aws-s3-v3
```

Lalu di Vercel:

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

Dengan `regions: ["sin1"]`, database hanya ~1–3ms per round-trip, dan
sekarang **jumlah query**, bukan latensi, yang menentukan kecepatan halaman.

Sebagai jaring pengaman, anggaran kasar untuk render authenticated:

| Halaman | Anggaran |
|---|---|
| Dashboard admin | ≤ 25 query |
| Halaman list admin | ≤ 12 query |
| Halaman borrower (mobile) | ≤ 10 query |

Kalau sebuah halaman melewati anggarannya, almost always penyebabnya salah
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
- **Jumlah koneksi database.** Setiap instance Vercel = satu koneksi MySQL
  tambahan. Kalau Aiven mulai menolak koneksi, tambahkan connection pooler
  (mis. ProxySQL) di depan Aiven.
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
- **Preview deployment.** Setiap push branch dapat URL sendiri dengan database
  Aiven yang sama. Jangan pakai data produksi untuk pengujian fitur.

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
