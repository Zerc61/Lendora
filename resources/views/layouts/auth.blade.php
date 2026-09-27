{{-- resources/views/layouts/auth.blade.php — shell untuk halaman tamu (login) --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="shell--auth">
<div class="auth">

    {{-- Panel kiri: identitas brand (disembunyikan di layar kecil) --}}
    <aside class="auth__aside">
        <span class="auth__glow auth__glow--a"></span>
        <span class="auth__glow auth__glow--b"></span>

        <div class="auth__brand">
            <x-logo :size="38" />
            <span class="rail__wordmark"><b>Lendora</b><span>Manage. Reserve. Maintain.</span></span>
        </div>

        <div class="auth__pitch">
            <p class="eyebrow">Asset Operations Platform</p>
            <h2 class="auth__headline">
                Kendali penuh atas <span class="grad-text">aset, peminjaman,</span> dan maintenance.
            </h2>
            <p class="auth__lede">
                Satu sistem untuk reservasi peminjam, serah-terima staf, dan pengerjaan teknisi —
                dengan jejak audit yang transparan.
            </p>
            <div class="auth__badges">
                <span class="badge tone-brand"><x-icon name="qr" /> Scan QR</span>
                <span class="badge tone-accent"><x-icon name="calendar" /> Reservasi</span>
                <span class="badge tone-ok"><x-icon name="wrench" /> Maintenance</span>
            </div>
        </div>

        <p class="auth__legal">&copy; {{ date('Y') }} Lendora · Sistem Peminjaman Aset</p>
    </aside>

    {{-- Panel kanan: konten form --}}
    <main class="auth__main">
        <div class="auth__panel">
            @include('partials.flash')
            @yield('content')
        </div>
    </main>
</div>
@stack('scripts')
</body>
</html>
