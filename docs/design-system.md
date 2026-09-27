# Lendora Design System

Sistem tampilan Lendora: **CSS + JS statis, tanpa build step.** Tidak ada Tailwind,
tidak ada Vite, tidak ada `npm install`. Changes ke `public/assets/lendora.css` langsung
efektif setelah hard refresh.

Brand token diambil dari folder `../desain-dan-logo` (mode gelap, aksen ungu + cyan).

---

## 1. Peta File

| File | Isi |
|---|---|
| `public/assets/lendora.css` | Design system lengkap: token, shell, komponen, 4 role shell, responsif, print, reduced-motion |
| `public/assets/lendora.js` | Progressive enhancement: dropdown, rail mobile, flash, count-up, modal konfirmasi, auto-submit filter, preview file, shortcut `/`, tabs, loading state |
| `resources/views/components/*.blade.php` | Komponen Blade yang boleh dipakai di semua view |
| `resources/views/partials/*.blade.php` | Potongan chrome bersama (head, flash, rail-nav, user-menu, notification-btn, bottomnav, mobile-rail) |
| `resources/views/layouts/*.blade.php` | `admin`, `staff`, `technician`, `borrower`, `auth`, `blank`, `app` (dispatcher) |
| `resources/views/vendor/pagination/lendora.blade.php` | Pagination tanpa Tailwind (dipakai otomatis) |

Cache-busting CSS/JS: otomatis lewat `filemtime()` di `partials/head.blade.php` — URL aset
mengandung timestamp modifikasi file, jadi setiap edit CSS/JS langsung dimuat browser tanpa
perlu menaikkan nomor versi secara manual. (Knob `ASSET_VERSION` lama sudah dihapus karena
praktis tidak pernah diubah sehingga justru menahan browser di aset lama.)

---

## 2. Empat Shell (satu per peran)

| Peran | Shell | Ciri khas |
|---|---|---|
| admin / super-admin | `layouts.admin` | Rail penuh 262px dengan 5 grup menu, topbar dengan pencarian global + menu "Buat", strip konteks di bawah topbar, aksen ungu |
| staff | `layouts.staff` | Rail ringkas 228px, **workbar antrean** (Check-out / Check-in / Reservasi) langsung di atas konten, palet diubah ke cyan |
| technician | `layouts.technician` | **Rail ringkas 92px** (ikon + label kecil) untuk navigasi section, **workbar status tiket ber-angka** yang hanya muncul di Beranda & seksi Tiket, konten lebar, palet diubah ke amber |
| borrower | `layouts.borrower` | App mobile-first: appbar, **bottom nav**, tombol FAB, **navbar section di ≥1024px**, drawer untuk layar kecil, tanpa rail |

Semua view memakai `@extends('layouts.app')`. Layout `app` hanya **dispatcher** yang
memilih shell sesuai `auth()->user()->primaryRole()` — jadi satu file view aman dipakai
peran apa pun tanpa perlu tahu perannya.

Override warna per peran dilakukan lewat CSS var pada `body`:

```css
.shell--staff      { --primary/--primary-2/--grad → cyan }
.shell--technician { --primary/--primary-2/--grad → amber }
```

Semua komponen turunan (tombol, badge, active nav, meter, grafik) ikut berubah —
itulah gunanya override token, bukan override per komponen.

---

## 3. Komponen Blade

| Komponen | Keterangan |
|---|---|
| `<x-page-head title subtitle crumbs back>` | Kepala halaman + slot aksi |
| `<x-card title subtitle icon tint flush hover delay>` | Kartu; `<x-slot:actions>` untuk aksi kanan header |
| `<x-stat label value hint icon tone href delay>` | KPI; angka count-up otomatis via `data-count` |
| `<x-status :status="$enum" />` | Badge dari enum (`label()` + `tone()`) |
| `<x-icon name />` | ~50 ikon garis, `currentColor` |
| `<x-logo :size />` | Mark Lendora (SVG inline) |
| `<x-btn href variant size icon iconRight block confirm>` | `variant`: default/primary/accent/soft/ghost/danger/ok |
| `<x-field name label hint required>` + `<x-slot:control>` | Field form + error otomatis dari prop `name` |
| `<x-empty icon title text>` | State kosong (wajib untuk tabel/list kosong) |
| `<x-filter-bar :keep reset>` + `<x-slot:controls>` | Form filter GET; `keep` mempertahankan parameter lain |
| `<x-metric label value max suffix tone />` | Bar meter ringkas |
| `<x-queue-card label value icon tone href />` | Kartu antrean besar |

Slot named `<x-slot:actions>` / `<x-slot:control>` wajib ditulis lengkap
(`<x-slot:control>…</x-slot:control>`) agar tidak lolos sebagai teks.

---

## 4. Enum sebagai sumber label UI

Semua enum domain punya `label()` (Bahasa Indonesia) dan `tone()` (nama tone CSS),
sehingga `<x-status>` selalu konsisten:

`AssetStatus` · `AssetCondition` (+`score()` 0–100 untuk health score) ·
`BorrowingStatus` · `ReservationStatus` · `IssueStatus` · `IssueType` · `IssueSeverity` ·
`MaintenanceStatus` · `MaintenanceType` · `MaintenancePriority` · `AttachmentType` ·
`UserStatus` · `OrganizationStatus` · `EducationLevel` · `Gender`

> **Tanggal:** locale aplikasi masih `en`, jadi `translatedFormat()` **tanpa**
> `->locale('id')` akan menghasilkan bulan Inggris ("06 May 2010"). Selalu pakai
> `->locale('id')->translatedFormat('d M Y')` agar konsisten Bahasa Indonesia.

Tone yang tersedia: `ok`, `info`, `brand`, `accent`, `warn`, `bad`, `orange`, `muted`.

---

## 5. Navigasi

`App\Support\Navigation` menghasilkan menu per peran (sudah difilter permission,
lengkap dengan badge counter antrean):

- `Navigation::for($user)` → grup menu untuk rail/drawer
- `Navigation::primary($user)` → item ringkas untuk bottom nav (mobile)
- `Navigation::ticketStatusCounts($user)` → jumlah tiket per status untuk workbar teknisi

Layout memakai `@include('partials.rail-nav', ['groups' => $nav])`; item aktif ditandai
otomatis lewat `request()->routeIs($item['match'])`.

---

## 6. Aturan Penulisan View

1. Teks UI **Bahasa Indonesia**; istilah teknis boleh.
2. Ikon lewat `<x-icon>`, **jangan emoji** dan **jangan gambar bitmap untuk ikon**.
3. **Tidak ada `<style>`/`<script>` inline.** Butuh JS → `@push('scripts')`.
4. `style="..."` hanya untuk utilitas kecil: `--d` (delay), `--gap`, `--w`/`height` bar, `color`.
5. Semua enum status lewat `<x-status>`; angka lewat `number_format(..., 0, ',', '.')` + `.tnum`.
6. `@section('title', ...)` untuk `<title>`, `@section('chrome', ...)` untuk judul topbar.
7. Form: nama field, `action`, `method`, `@csrf/@method` **tidak boleh berubah**;
   error validasi selalu ditampilkan (`<x-field>` sudah menanganinya).
8. Aksi merusak/bernilai besar → `data-confirm` (JS menampilkan modal konfirmasi).
9. Tabel: `<th scope="col">`, kosong → `<x-empty>` dengan `colspan`, pagination → `->links()`.
10. Tabel di halaman mobile: sembunyikan kolom sekunder dengan `.hide-sm`.
11. Ikon selalu lewat `<x-icon>` — stylesheet punya aturan dasar `svg.icon`
    (18×18, `flex: none`). Tanpa aturan itu SVG inline jatuh ke ukuran default
    browser (300×150) dan merusak kartu/flexbox-nya.
12. Ritme vertikal antar blok tingkat atas dijamin `.app__body > * + *`
    (margin-top 18px). Jangan mengandalkan margin antar komponen — kartu yang
    berdempet tanpa jarak adalah gejala aturan ini hilang.
13. Anak grid/stack butuh `min-width: 0` (sudah diatur di stylesheet) — tanpa
    itu konten `nowrap` memaksa kolom melebar melewati track-nya.

---

## 7. Animasi

- Semua animasi pakai token `--dur-*` dan easing `--ease` (`cubic-bezier(.22,1,.36,1)`).
- Halaman masuk: `rise` (fade + naik 10px) pada `.page-head`, `.card`, `.stat`, `.list__row`.
- Stagger: `style="--d:{{ $i * 50 }}ms"` (40–70ms per item, maks ~6 item).
- Angka KPI: count-up 780ms + format `Intl.NumberFormat('id-ID')`.
- Bar/meter: `scaleX` dari 0; kolom grafik: `scaleY` dari bawah.
- Menu/dropdown: `menuIn` (translateY -6px + scale .98) 180ms.
- `@media (prefers-reduced-motion: reduce)` mematikan semua animasi & transisi.

---

## 8. Checklist Sebelum Merge

```sh
php artisan view:clear && php artisan view:cache   # deteksi error sintaks Blade
php artisan test
```

---

## 9. Struktur Sekolah

Halaman pengguna & organisasi membawa hierarki sekolah. Semua belongs-to
`organizations`, jadi data antar sekolah tidak pernah bercampur.

```
Organization
 ├── Program        (jurusan/peminatan)  →  education_level + name
 │    └── SchoolClass (kelas/rombelan)  →  SchoolClass.user_id
 └── Classroom      (ruang kelas)
```

| Tabel | Isi |
|---|---|
| `programs` | jurusan per jenjang: SMA→IPA/IPS, SMK→RPL/TKJ/TKR/TBS/TAS/AKL, MA→IPS/IIM |
| `school_classes` | rombongan belajar, punya `school_year` + `capacity` + wali kelas |
| `classrooms` | ruang kelas + kapasitas |

`users` kolom tambahan: `school_class_id`, `photo_path`, `identity_number` (NIS/NISN),
`birth_date`, `gender`, `phone`, `address`, `bio`.

**Filter bertingkat di `/admin/users`:** navbar per peran (Semua · Admin · Staff ·
Teknisi · Borrower) lalu sekolah → jenjang → jurusan → kelas. Opsi tiap dropdown
disaring mengikuti filter di atasnya, jadi memilih SMA tidak menampilkan IPA/IPS/SMK.

**Foto profil:** simpan di `storage/app/public/avatars`, diakses lewat
`User::photoUrl()` / `User::hasPhoto()`. Batas 2 MB, hanya JPG/PNG/WebP. File lama
**wajib dihapus** saat diganti agar storage tidak menumpuk file yatim — sudah
dikunci test `SchoolStructureTest`.

**Seeder:** `SchoolStructureSeeder` (idempotent) membuat 2 organisasi, 5 jenjang,
13 program, 25 kelas × 25 siswa, dan 6 ruang kelas untuk SMK Nusantara.
