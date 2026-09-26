{{-- resources/views/showcase.blade.php — Halaman Produk (tujuan scan QR) --}}
@extends('layouts.app')
@section('title', $asset->asset_code . ' — Lendora')
@section('content')
<div class="card row">
    <div>
        <div class="muted" style="font-size:12px">{{ $asset->organization->name }} · {{ $asset->assetType->category->name }}</div>
        <h1 style="margin:4px 0">{{ $asset->assetType->name }}</h1>
        <div class="muted" style="font-size:13px">{{ $asset->asset_code }} · SN: {{ $asset->serial_number ?? '—' }}</div>
    </div>
    <div>
        <span class="badge b-{{ $asset->status->value }}">{{ $asset->status->label() }}</span>
        <span class="badge">kondisi: {{ $asset->condition->value }}</span>
    </div>
</div>

{{-- Cover --}}
<div class="card" style="padding:0;overflow:hidden">
    @if($cover)
        @if(str_starts_with(($cover->metadata['mime'] ?? ''), 'video/') || $cover->type->value === 'video')
        <video controls preload="metadata" style="width:100%;max-height:420px;background:#000;display:block">
            <source src="{{ Storage::url($cover->file_path) }}">
        </video>
        @else
        <img src="{{ Storage::url($cover->file_path) }}" alt="{{ $asset->asset_code }}" style="width:100%;max-height:420px;object-fit:cover;display:block">
        @endif
    @else
    <div style="padding:48px 16px;text-align:center">
        <div style="font-size:48px">📦</div>
        <p class="muted">Belum ada foto/video untuk unit ini.<br>Minta operator mengunggah media di halaman detail aset.</p>
        <a href="{{ route('admin.assets.show', $asset) }}">Buka Detail Aset</a>
    </div>
    @endif
</div>

{{-- Video produk --}}
@if($videos->isNotEmpty())
<div class="card">
    <h3 style="margin-top:0">🎬 Video Produk</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px">
        @foreach($videos as $v)
        <div>
            <video controls preload="metadata" style="width:100%;border-radius:8px;background:#000">
                <source src="{{ Storage::url($v->file_path) }}">
            </video>
            <small class="muted">{{ $v->title }}</small>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Galeri foto --}}
@if($photos->isNotEmpty())
<div class="card">
    <h3 style="margin-top:0">📸 Foto Unit</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px">
        @foreach($photos as $p)
        <a href="{{ Storage::url($p->file_path) }}" target="_blank">
            <img src="{{ Storage::url($p->file_path) }}" alt="{{ $p->title }}" loading="lazy"
                 style="width:100%;height:140px;object-fit:cover;border-radius:8px;border:1px solid #252B38">
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- Spesifikasi --}}
<div class="card">
    <h3 style="margin-top:0">📋 Spesifikasi</h3>
    <table>
        <tr><th style="width:35%">Tipe</th><td>{{ $asset->assetType->name }}</td></tr>
        <tr><th>Brand / Model</th><td>{{ $asset->assetType->brand ?? '—' }} {{ $asset->assetType->model ? '/ '.$asset->assetType->model : '' }}</td></tr>
        <tr><th>Lokasi</th><td>{{ $asset->location?->name ?? '—' }}</td></tr>
        <tr><th>Status</th><td><span class="badge b-{{ $asset->status->value }}">{{ $asset->status->label() }}</span></td></tr>
        <tr><th>Kondisi</th><td>{{ $asset->condition->value }}</td></tr>
    </table>
</div>

{{-- Aksi --}}
<div class="card">
    <h3 style="margin-top:0">⚡ Aksi</h3>
    @can('reservation.create')
        <a href="{{ route('my.reservations.create', ['asset' => $asset->id]) }}">📅 Reserve unit ini</a><br>
    @endcan
    @can('issue.create')
        <a href="{{ route('admin.issues.create', ['asset' => $asset->id]) }}">⚠️ Laporkan Masalah</a><br>
    @endcan
    <a href="{{ route('admin.assets.show', $asset) }}">📦 Detail Aset (lengkap)</a>
</div>
@endsection
