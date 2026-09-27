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
