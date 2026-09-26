{{-- resources/views/admin/assets/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Detail Aset')
@section('content')
<div class="card row">
    <h1 style="margin:0">{{ $asset->asset_code }}</h1>
    <div>
        @can('update', $asset)<a href="{{ route('admin.assets.edit', $asset) }}">Edit</a>@endcan
        @can('delete', $asset)
        <form method="POST" action="{{ route('admin.assets.destroy', $asset) }}" style="display:inline" onsubmit="return confirm('Hapus aset ini?')">
            @csrf @method('DELETE')
            <button style="background:#7f1d1d">Hapus</button>
        </form>
        @endcan
    </div>
</div>

<div class="card">
    <table>
        <tr><th style="width:35%">Tipe</th><td>{{ $asset->assetType->name }} ({{ $asset->assetType->category->name }})</td></tr>
        <tr><th>Status</th><td><span class="badge b-{{ $asset->status->value }}">{{ $asset->status->label() }}</span></td></tr>
        <tr><th>Kondisi</th><td>{{ $asset->condition->value }}</td></tr>
        <tr><th>Serial Number</th><td>{{ $asset->serial_number ?? '—' }}</td></tr>
        <tr><th>Lokasi</th><td>{{ $asset->location?->name ?? '—' }}</td></tr>
        <tr><th>Tanggal Beli</th><td>{{ $asset->purchase_date?->format('d M Y') ?? '—' }}</td></tr>
        <tr><th>Harga</th><td>{{ $asset->purchase_price ? 'Rp '.number_format($asset->purchase_price, 0, ',', '.') : '—' }}</td></tr>
        <tr><th>Garansi s/d</th><td>{{ $asset->warranty_until?->format('d M Y') ?? '—' }}</td></tr>
        <tr><th>Catatan</th><td>{{ $asset->notes ?? '—' }}</td></tr>
    </table>
    {{-- QR Code untuk asset unit akan ditambahkan di Phase 5 --}}
</div>

{{-- Attachments --}}
<div class="card">
    <h3 style="margin-top:0">Foto / Manual / Dokumen</h3>
    @can('update', $asset)
    <form method="POST" action="{{ route('admin.assets.attachments.store', $asset) }}" enctype="multipart/form-data" class="row" style="margin-bottom:12px">
        @csrf
        <div style="flex:2"><input type="file" name="file" required accept=".jpg,.jpeg,.png,.webp,.pdf"></div>
        <div style="flex:1">
            <select name="type">
                <option value="photo">Foto</option>
                <option value="manual">Manual</option>
                <option value="document">Dokumen</option>
            </select>
        </div>
        <div><button>Upload</button></div>
    </form>
    @endcan
    @forelse($asset->attachments as $att)
    <div class="row" style="border-bottom:1px solid #252B38;padding:6px 0">
        <div>
            <a href="{{ Storage::url($att->file_path) }}" target="_blank">{{ $att->title }}</a>
            <span class="muted">[{{ $att->type->value }}] oleh {{ $att->uploader?->name ?? '—' }}</span>
        </div>
        @can('update', $asset)
        <form method="POST" action="{{ route('admin.attachments.destroy', $att) }}" onsubmit="return confirm('Hapus attachment?')">
            @csrf @method('DELETE')
            <button style="background:#7f1d1d;padding:5px 10px;font-size:12px">Hapus</button>
        </form>
        @endcan
    </div>
    @empty
    <p class="muted">Belum ada attachment.</p>
    @endforelse
</div>

{{-- Quick Actions (PDF 4E) + QR unit --}}
<div class="card">
    <h3 style="margin-top:0">⚡ Quick Actions</h3>
    <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center">
        <img src="{{ route('assets.qr', $asset->asset_code) }}" width="110" height="110"
             alt="QR {{ $asset->asset_code }}" style="border:4px solid #fff;border-radius:6px">
        <div>
            @can('reservation.create')
                <a href="{{ route('my.reservations.create', ['asset' => $asset->id]) }}">📅 Reserve unit ini</a><br>
            @endcan
            @can('checkout.perform')
                @if($pendingCheckout)
                    <a href="{{ route('admin.checkout.show', $pendingCheckout) }}">📤 Check-out ({{ $pendingCheckout->code }})</a><br>
                @endif
            @endcan
            @can('checkin.perform')
                @if($activeBorrowing)
                    <a href="{{ route('admin.checkin.show', $activeBorrowing) }}">📥 Check-in ({{ $activeBorrowing->code }})</a><br>
                @endif
            @endcan
            @can('issue.create')
                <a href="{{ route('admin.issues.create', ['asset' => $asset->id]) }}">⚠️ Laporkan Masalah</a><br>
            @endcan
            <span class="muted" style="font-size:13px">🛠️ Maintenance — tersedia di Phase 6</span>
        </div>
        <div>
            <a href="{{ route('admin.assets.qr-label', $asset) }}">🖨️ Cetak Label QR</a>
        </div>
    </div>
</div>

{{-- Jadwal reservasi unit (availability) --}}
<div class="card">
    <h3 style="margin-top:0">📅 Jadwal Reservasi Mendatang</h3>
    @forelse($schedules as $item)
    <div style="border-bottom:1px solid #252B38;padding:6px 0">
        <strong>{{ $item->reservation->code }}</strong>
        <span class="badge b-{{ $item->reservation->status->value }}">{{ $item->reservation->status->value }}</span><br>
        <span class="muted">{{ $item->reservation->start_at->format('d M Y H:i') }} → {{ $item->reservation->end_at->format('d M Y H:i') }}
        oleh {{ $item->reservation->user->name }}</span>
    </div>
    @empty
    <p class="muted">Tidak ada reservasi mendatang untuk unit ini.</p>
    @endforelse
</div>

{{-- Riwayat inspeksi --}}
<div class="card">
    <h3 style="margin-top:0">Riwayat Inspeksi</h3>
    @forelse($inspections as $ins)
    <div style="border-bottom:1px solid #252B38;padding:6px 0">
        <strong>{{ $ins->stage->value }}</strong> — kondisi: {{ $ins->condition->value }}
        oleh {{ $ins->inspector->name }} pada {{ $ins->inspected_at->format('d M Y H:i') }}
        @if($ins->notes)<div class="muted">{{ $ins->notes }}</div>@endif
    </div>
    @empty
    <p class="muted">Belum ada riwayat inspeksi.</p>
    @endforelse
</div>
@endsection
