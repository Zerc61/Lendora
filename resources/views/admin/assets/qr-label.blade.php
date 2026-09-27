{{-- resources/views/admin/assets/qr-label.blade.php — Label QR siap cetak (shell minimal)
     Tanpa chrome: hanya label + tombol cetak. Tombol disembunyikan saat print. --}}
@extends('layouts.blank')
@section('title', 'Label QR — ' . $asset->asset_code)

@section('content')
<main class="app__body" style="display:grid;justify-items:center;padding:32px 24px 8vh">
    <div>
        <div class="btn-row no-print" style="justify-content:center;margin-bottom:16px">
            <button type="button" class="btn btn--primary" id="label-print">
                <x-icon name="printer" /> Cetak Label
            </button>
            <x-btn :href="route('admin.assets.show', $asset)" variant="ghost" icon="arrow-left">Kembali</x-btn>
        </div>

        <div class="qr" style="max-width:340px;gap:10px">
            <div class="btn-row" style="justify-content:center;gap:8px">
                <x-logo :size="24" />
                <b style="font-size:.82rem;letter-spacing:.16em">LENDORA</b>
            </div>
            <div class="small">{{ $asset->organization->name }}</div>

            <img src="{{ route('assets.qr', $asset->asset_code) }}" alt="QR code {{ $asset->asset_code }}">

            <b class="mono" style="font-size:1.15rem;letter-spacing:.06em">{{ $asset->asset_code }}</b>
            <div style="font-weight:700">{{ $asset->assetType->name }}</div>
            <div class="small">SN: {{ $asset->serial_number ?? '—' }}</div>
            <div class="small">{{ $asset->assetType->category->name }}</div>
            <div class="small" style="opacity:.7">Pindai untuk info &amp; aksi unit ini</div>
        </div>

        <p class="tiny dim no-print" style="text-align:center;margin-top:14px;max-width:34ch">
            Tempel label ini pada permukaan unit. Arahkan kamera ponsel ke QR untuk membuka halaman produk.
        </p>
    </div>
</main>
@endsection

@push('scripts')
<script>
    document.getElementById('label-print')?.addEventListener('click', () => window.print());
</script>
@endpush
