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

{{-- Asset Health (PDF 4K) --}}
<div class="card">
    <h3 style="margin-top:0">💚 Kesehatan Aset: <strong style="color:{{ $health['color'] }}">{{ $health['score'] }}/100 — {{ $health['label'] }}</strong></h3>
    <div style="background:#0B0E14;border:1px solid #252B38;border-radius:6px;height:10px;max-width:420px">
        <div style="height:10px;border-radius:6px;background:{{ $health['color'] }};width:{{ $health['score'] }}%"></div>
    </div>
    <ul class="muted" style="font-size:13px;margin:8px 0 0;padding-left:18px">
        @foreach($health['breakdown'] as $b)<li>{{ $b }}</li>@endforeach
    </ul>
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
</div>

{{-- Galeri Foto / Video Produk + Dokumen --}}
<div class="card">
    <div class="row">
        <h3 style="margin:0">🖼️ Galeri Produk (Foto / Video)</h3>
        <a href="{{ route('showcase', $asset->asset_code) }}">🖼️ Lihat Halaman Produk</a>
    </div>
    @can('update', $asset)
    <form method="POST" action="{{ route('admin.assets.attachments.store', $asset) }}" enctype="multipart/form-data" class="row" style="margin-bottom:12px">
        @csrf
        <div style="flex:2"><input type="file" name="file" required accept=".jpg,.jpeg,.png,.webp,.mp4,.webm,.mov,.pdf"></div>
        <div style="flex:1">
            <select name="type">
                <option value="photo">Foto</option>
                <option value="video">Video</option>
                <option value="manual">Manual</option>
                <option value="document">Dokumen</option>
            </select>
        </div>
        <div><button>Upload</button></div>
    </form>
    @endcan
    @php
        $media = $asset->attachments->filter(fn ($a) => in_array(($a->metadata['mime'] ?? ''), ['image/jpeg','image/png','image/webp'], true)
            || $a->type->value === 'photo' || $a->type->value === 'video');
        $docs = $asset->attachments->reject(fn ($a) => $media->contains($a));
        $isVideo = fn ($a) => str_starts_with(($a->metadata['mime'] ?? ''), 'video/') || $a->type->value === 'video';
    @endphp
    @if($media->isNotEmpty())
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:12px">
        @foreach($media as $att)
        <div style="border:1px solid #252B38;border-radius:8px;overflow:hidden">
            @if($isVideo($att))
            <video controls preload="metadata" style="width:100%;max-height:220px;background:#000">
                <source src="{{ Storage::url($att->file_path) }}">
            </video>
            @else
            <a href="{{ Storage::url($att->file_path) }}" target="_blank">
                <img src="{{ Storage::url($att->file_path) }}" alt="{{ $att->title }}" style="width:100%;max-height:220px;object-fit:cover;display:block">
            </a>
            @endif
            <div class="row" style="padding:6px 8px">
                <small class="muted">{{ Str::limit($att->title, 24) }} @if($att->is_cover)<span class="badge b-available">cover</span>@endif</small>
                @can('update', $asset)
                <div>
                    @unless($att->is_cover)
                    <form method="POST" action="{{ route('admin.attachments.cover', $att) }}" style="display:inline">
                        @csrf
                        <button style="padding:3px 8px;font-size:11px">Cover</button>
                    </form>
                    @endunless
                    <form method="POST" action="{{ route('admin.attachments.destroy', $att) }}" style="display:inline" onsubmit="return confirm('Hapus media ini?')">
                        @csrf @method('DELETE')
                        <button style="background:#7f1d1d;padding:3px 8px;font-size:11px">Hapus</button>
                    </form>
                </div>
                @endcan
            </div>
        </div>
        @endforeach
    </div>
    @else
    <p class="muted">Belum ada foto/video produk. Upload untuk menampilkannya di sini (terlihat setelah scan QR).</p>
    @endif
    @forelse($docs as $att)
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
    <p class="muted">Belum ada manual/dokumen.</p>
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
@can('maintenance.create')
    <a href="{{ route('admin.tickets.create', ['asset' => $asset->id]) }}">🛠️ Buat Tiket Maintenance</a><br>
@endcan
@can('update', $asset)
@if(!in_array($asset->status->value, ['retired', 'borrowed', 'reserved']))
<form method="POST" action="{{ route('admin.assets.retire', $asset) }}" onsubmit="return confirm('Pensiunkan aset ini? Status retired bersifat permanen.')" style="margin-top:6px">
    @csrf
    <button style="background:#334155;padding:5px 10px;font-size:12px">🗑️ Retire Aset</button>
</form>
@endif
@endcan
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

{{-- Riwayat Maintenance (PDF 4J) --}}
<div class="card">
    <h3 style="margin-top:0">🔧 Riwayat Maintenance</h3>
    @forelse($asset->maintenanceTickets as $t)
    <div style="border-bottom:1px solid #252B38;padding:6px 0">
        <a href="{{ route('admin.tickets.show', $t) }}"><strong>{{ $t->code }}</strong></a>
        — {{ $t->type->value }} | <span class="badge b-{{ $t->status->value }}">{{ $t->status->label() }}</span>
        <span class="muted">| teknisi: {{ $t->technician?->name ?? '—' }} | selesai: {{ $t->completed_at?->format('d M Y') ?? '—' }}</span>
    </div>
    @empty
    <p class="muted">Belum ada riwayat maintenance.</p>
    @endforelse
</div>

{{-- Riwayat Issue --}}
<div class="card">
    <h3 style="margin-top:0">⚠️ Riwayat Issue</h3>
    @forelse($asset->issues as $i)
    <div style="border-bottom:1px solid #252B38;padding:6px 0">
        <a href="{{ route('admin.issues.show', $i) }}"><strong>{{ $i->code }}</strong></a>
        — {{ $i->type->value }} ({{ $i->severity->value }}) |
        <span class="badge b-{{ $i->status->value }}">{{ $i->status->label() }}</span>
        <div class="muted">{{ Str::limit($i->description, 80) }}</div>
    </div>
    @empty
    <p class="muted">Belum ada riwayat issue.</p>
    @endforelse
</div>
@endsection
