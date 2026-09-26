{{-- resources/views/admin/assets/qr-label.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Label QR — {{ $asset->asset_code }}</title>
<style>
    body{font-family:monospace;background:#fff;color:#000;display:flex;justify-content:center;padding:30px}
    .label{width:340px;padding:18px;border:2px dashed #000;text-align:center}
    .label img{width:230px;height:230px}
    h2{margin:0;font-size:22px;letter-spacing:1px}
    .brand{color:#6246D8;font-weight:bold;font-size:14px}
    .meta{font-size:13px;margin:4px 0}
    button{margin-top:14px;padding:8px 16px}
    @media print{button{display:none}body{padding:0}}
</style>
</head>
<body>
<div class="label">
    <div class="brand">LENDORA — {{ $asset->organization->name }}</div>
    <h2>{{ $asset->asset_code }}</h2>
    <img src="{{ route('assets.qr', $asset->asset_code) }}" alt="QR {{ $asset->asset_code }}">
    <div class="meta">{{ $asset->assetType->name }}</div>
    <div class="meta">SN: {{ $asset->serial_number ?? '—' }} | {{ $asset->assetType->category->name }}</div>
    <div class="meta" style="font-size:11px;color:#555">Scan untuk info & aksi unit ini</div>
    <button onclick="window.print()">🖨️ Cetak</button>
</div>
</body>
</html>
