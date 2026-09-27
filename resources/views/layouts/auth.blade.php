{{-- resources/views/layouts/auth.blade.php — shell untuk halaman tamu (login) --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
<div style="min-height:100vh;display:grid;grid-template-columns:1.05fr .95fr">
    {{-- Panel kiri: identitas brand (disembunyikan di layar kecil) --}}
    <aside class="hide-sm" style="position:relative;overflow:hidden;padding:44px;display:flex;flex-direction:column;justify-content:space-between;border-right:1px solid var(--border);background:linear-gradient(150deg,rgba(124,92,252,.16),rgba(18,23,34,.4) 55%),var(--surface-2)">
        <div style="position:absolute;right:-120px;top:-120px;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(124,92,252,.34),transparent 66%)"></div>
        <div style="position:absolute;left:-90px;bottom:-140px;width:340px;height:340px;border-radius:50%;background:radial-gradient(circle,rgba(34,211,238,.18),transparent 66%)"></div>

        <div class="btn-row" style="position:relative">
            <x-logo :size="38" />
            <span class="rail__wordmark"><b style="font-size:1.1rem">Lendora</b><span>Manage. Reserve. Maintain.</span></span>
        </div>

        <div style="position:relative;max-width:44ch">
            <p class="eyebrow">Asset Operations Platform</p>
            <h2 style="font-size:2rem;line-height:1.15;margin-top:8px">
                Kendali penuh atas <span class="grad-text">aset, peminjaman,</span> dan maintenance.
            </h2>
            <p class="muted" style="margin-top:10px;font-size:.88rem">
                Satu sistem untuk reservasi peminjam, serah-terima staf, dan pengerjaan teknisi —
                dengan jejak audit yang transparan.
            </p>
            <div class="btn-row" style="margin-top:22px">
                <span class="badge tone-brand"><x-icon name="qr" /> Scan QR</span>
                <span class="badge tone-accent"><x-icon name="calendar" /> Reservasi</span>
                <span class="badge tone-ok"><x-icon name="wrench" /> Maintenance</span>
            </div>
        </div>

        <p class="tiny dim" style="position:relative">&copy; {{ date('Y') }} Lendora · Sistem Peminjaman Aset</p>
    </aside>

    {{-- Panel kanan: konten form --}}
    <main style="display:grid;place-items:center;padding:32px 22px">
        <div style="width:min(392px,100%)">
            @include('partials.flash')
            @yield('content')
        </div>
    </main>
</div>
@stack('scripts')
</body>
</html>
