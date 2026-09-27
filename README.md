<p align="center">
  <img src="https://raw.githubusercontent.com/lendora/lendora/main/public/assets/favicon.svg" width="72" alt="Lendora">
  <br><br>
  <b>Lendora</b> — Sistem Peminjaman Aset & Maintenance<br>
  <sub>Manage. Reserve. Maintain.</sub>
</p>

Lendora adalah platform operasional aset untuk organisasi: peminjam mengajukan
reservasi lewat katalog & QR, staf memproses serah-terima (check-out / check-in), teknisi
mengerjakan tiket maintenance, admin memantau kondisi inventori dan jejak audit.

---

## Fitur

| Area | Kemampuan |
|---|---|
| **Aset** | Katalog + tipe aset, kategori, lokasi, kondisi, status state-machine, lampiran foto/video, QR per aset, health score |
| **Reservasi** | Pengajuan peminjam, persetujuan/penolakan bertingkat, expiry otomatis, antrean approve |
| **Peminjaman** | Check-out & check-in dengan inspection kondisi per item, deteksi terlambat, notifikasi pengingat |
| **Issue** | Laporan kerusakan (bisa dari peminjam), alur investigasi → resolve/reject → close, linkage ke tiket |
| **Maintenance** | Tiket (preventive/corrective/inspection), prioritas, penugasan teknisi, work log + biaya, status naik-turun |
| **Laporan** | Ringkasan peminjaman, utilisasi aset, tren 6 bulan, biaya maintenance, ekspor CSV (streaming) |
| **Audit** | Otomatis merekam setiap perubahan penting (actor, aksi, nilai lama/baru) |
| **Notifikasi** | In-app (approval, tenggat, overdue, maintenance, garansi) + scheduler pengingat harian |
| **RBAC** | 5 role: super-admin, admin, staff, technician, borrower — dengan matriks permission |

## Peran & Tampilan

Setiap peran memakai **shell UI sendiri**:

| Peran | Shell | Fokus |
|---|---|---|
| super-admin / admin | Konsol | Inventori, insight, master data, laporan, audit |
| staff | Antrean Kerja | Serah-terima, antrean reservasi, issue |
| technician | Workbench | Tiket yang dikerjakan, konteks aset |
| borrower | App (mobile-first) | Reservasi, status pinjaman, notifikasi |

Lihat [`docs/design-system.md`](docs/design-system.md) untuk detail token, komponen,
animasi, dan aturan penulisan view.

---

## Kebutuhan Sistem

- PHP **8.3+** (ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`)
- Composer 2
- MySQL 8 / MariaDB 10.6+

> **Tidak ada build front-end.** CSS & JS served statis dari `public/assets`
> (lihat `public/assets/lendora.css`). `npm install` / `vite build` tidak diperlukan.

## Instalasi

```sh
composer install
cp .env.example .env
php artisan key:generate

# buat database
mysql -u root -e "CREATE DATABASE lendora CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE USER 'lendora_user'@'localhost' IDENTIFIED BY 'lendora_123';
                  GRANT ALL PRIVILEGES ON lendora.* TO 'lendora_user'@'localhost'; FLUSH PRIVILEGES;"

# sesuaikan DB_* di .env
php artisan migrate --seed
```

### Akun demo (password: `password`)

| Email | Peran |
|---|---|
| `superadmin@lendora.test` | Super Admin |
| `admin@lendora.test` | Administrator |
| `staff@lendora.test` | Staf Operasional |
| `teknisi@lendora.test` | Teknisi |
| `budi@lendora.test` | Peminjam |

Data transaksi contoh (reservasi, peminjaman, issue, tiket):

```sh
php artisan db:seed --class=DemoDataSeeder   # idempoten, aman diulang
```

## Menjalankan

```sh
php artisan serve
```

## Tugas Terjadwal

Notifikasi tenggat, overdue, dan pengingat dijadwalkan di `routes/console.php`:

```sh
php artisan schedule:work      # dev
* * * * * cd /path/lendora && php artisan schedule:run >> /dev/null 2>&1   # production
```

## Testing

```sh
php artisan test
```

## Struktur

```
app/
  Enums/           # status/condition enum + label() & tone() untuk UI
  Http/Controllers/Admin|Borrower|Auth
  Models/
  Policies/        # otorisasi per peran
  Services/ReportService.php
  Support/Navigation.php   # definisi menu per peran + badge counter
  Observers/       # audit trail otomatis
resources/views/
  layouts/         # admin | staff | technician | borrower | auth | blank | app (dispatcher)
  components/      # komponen Blade design system
  partials/        # chrome bersama (head, flash, rail, bottom nav, user menu)
public/assets/     # lendora.css + lendora.js (tanpa build)
docs/              # dokumentasi design system
```

## Lisensi

Proyek internal — hak cipta reserved.
