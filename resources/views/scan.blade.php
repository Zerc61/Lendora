{{-- resources/views/scan.blade.php --}}
@extends('layouts.app')
@section('title', 'Scan QR')
@section('content')
<h1>📷 Scan QR Aset</h1>
<div class="card">
    <div id="reader" style="width:100%;max-width:480px"></div>
    <p class="muted" style="font-size:13px">Akses kamera butuh HTTPS atau localhost. Jika kamera tidak tersedia, gunakan input manual di bawah.</p>
</div>
<div class="card" style="max-width:480px">
    <form method="GET" action="{{ route('scan') }}" class="row">
        <div style="flex:1">
            <label>Input Manual (asset code)</label>
            <input name="code" placeholder="LND-LAP-0001" autofocus>
        </div>
        <div style="align-self:flex-end"><button>Cari</button></div>
    </form>
</div>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const box = document.getElementById('reader');

    if (typeof Html5Qrcode === 'undefined' || !navigator.mediaDevices) {
        box.innerHTML = '<p class="muted">Kamera tidak tersedia di perangkat ini — gunakan input manual.</p>';
        return;
    }

    const scanner = new Html5Qrcode('reader');

    scanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        (decodedText) => {
            scanner.stop().catch(() => {});
            window.location.href = decodedText; // QR berisi URL resolusi aset
        },
        () => {} // abaikan frame yang gagal dibaca
    ).catch(() => {
        box.innerHTML = '<p class="muted">Gagal membuka kamera (butuh HTTPS/localhost). Gunakan input manual.</p>';
    });
});
</script>
@endsection
