{{-- resources/views/scan.blade.php — Halaman scan QR (kamera + input manual) --}}
@extends('layouts.app')
@section('title', 'Scan QR')
@section('chrome', 'Scan QR')

@section('content')
<x-page-head title="Scan QR Aset"
             subtitle="Arahkan kamera ke QR pada unit, atau ketik kode aset secara manual.">
    @can('viewAny', App\Models\Asset::class)
        <x-btn :href="route('admin.assets.index')" variant="ghost" icon="box">Daftar Aset</x-btn>
    @endcan
</x-page-head>

<div class="grid grid--main">
    <div class="stack" style="--gap:18px">
        <x-card title="Kamera Pemindai" icon="scan" :delay="0"
                subtitle="Pindai label QR yang menempel pada unit.">
            <div id="reader" class="hero-media" style="max-width:520px"></div>
            <p class="small dim" style="margin-top:10px">
                Akses kamera memerlukan HTTPS atau localhost. Bila kamera tidak tersedia, gunakan input manual di samping.
            </p>
        </x-card>
    </div>

    <div class="stack" style="--gap:18px">
        <x-card title="Input Manual" icon="edit" :delay="60" subtitle="Ketik kode aset atau ID unit.">
            <form method="GET" action="{{ route('scan') }}" class="form">
                <x-field name="code" label="Kode Aset" hint="Contoh: LND-LAP-0001">
                    <x-slot:control>
                        <input id="f-code" name="code" placeholder="LND-LAP-0001" autofocus autocomplete="off">
                    </x-slot:control>
                </x-field>
                <button type="submit" class="btn btn--primary"><x-icon name="search" /> Buka Unit</button>
            </form>
        </x-card>

        <x-card title="Petunjuk" icon="info" :delay="120">
            <div class="stack" style="--gap:11px">
                <div class="inline-alert tone-brand">
                    <x-icon name="scan" />
                    <div><b>Dekatkan QR ke kamera.</b> Area pindai muncul otomatis di layar.</div>
                </div>
                <div class="inline-alert tone-accent">
                    <x-icon name="external" />
                    <div><b>Hasil scan membuka halaman produk.</b> Di sana Anda bisa reservasi atau melapor masalah.</div>
                </div>
                <div class="inline-alert tone-warn">
                    <x-icon name="info" />
                    <div><b>QR bukan pengganti izin akses.</b> Unit yang tidak boleh Anda lihat tetap tertutup.</div>
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const box = document.getElementById('reader');
    const fallback = (message) => {
        box.innerHTML = '<div class="empty"><b>Kamera tidak tersedia</b><p>' + message + '</p></div>';
    };

    if (typeof Html5Qrcode === 'undefined' || ! navigator.mediaDevices) {
        fallback('Perangkat ini tidak mendukung kamera — gunakan input manual di samping.');
        return;
    }

    const scanner = new Html5Qrcode('reader');

    scanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        (decodedText) => {
            scanner.stop().catch(() => {});
            window.location.href = decodedText; // QR berisi URL halaman produk unit
        },
        () => {} // abaikan frame yang gagal dibaca
    ).catch(() => {
        fallback('Gagal membuka kamera (butuh HTTPS atau localhost). Gunakan input manual.');
    });
});
</script>
@endpush
