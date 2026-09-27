{{-- resources/views/showcase.blade.php — Halaman produk /p/{code} (tujuan scan QR) --}}
@extends('layouts.app')
@section('title', $asset->asset_code . ' — ' . $asset->assetType->name)
@section('chrome', 'Produk')

@section('content')
@php
    $isVideo = fn ($a) => str_starts_with(($a->metadata['mime'] ?? ''), 'video/') || $a->type->value === 'video';
    $coverIsVideo = $cover ? $isVideo($cover) : false;
    $videoList = $coverIsVideo ? $videos->reject(fn ($v) => $v->id === $cover->id)->values() : $videos;
    $photoList = $cover && ! $coverIsVideo
        ? $photos->reject(fn ($p) => $p->id === $cover->id)->values()
        : $photos;

    $availability = match ($asset->status) {
        \App\Enums\AssetStatus::Available => 'Unit ini sedang tersedia dan bisa langsung dipinjam.',
        \App\Enums\AssetStatus::Reserved => 'Unit ini sedang direservasi peminjam lain.',
        \App\Enums\AssetStatus::Borrowed => 'Unit ini sedang dipinjam dan akan kembali setelah Masa Peminjaman.',
        \App\Enums\AssetStatus::Maintenance => 'Unit ini sedang diperbaiki oleh teknisi.',
        \App\Enums\AssetStatus::Damaged => 'Unit ini tercatat rusak dan menunggu perbaikan.',
        \App\Enums\AssetStatus::Lost => 'Unit ini tercatat hilang — hubungi petugas bila Anda menemukan.',
        \App\Enums\AssetStatus::Retired => 'Unit ini sudah dipensiunkan dan tidak dapat dipinjam lagi.',
    };
    $conditionText = match ($asset->condition) {
        \App\Enums\AssetCondition::Excellent => 'Sangat baik — seperti baru.',
        \App\Enums\AssetCondition::Good => 'Baik — berfungsi normal.',
        \App\Enums\AssetCondition::Fair => 'Cukup — ada tanda keausan ringan.',
        \App\Enums\AssetCondition::Poor => 'Buruk — perlu perbaikan.',
        \App\Enums\AssetCondition::Broken => 'Rusak berat — belum bisa dipakai.',
    };
@endphp

{{-- Kepala halaman produk --}}
<section class="hero" style="margin-bottom:18px">
    <p class="eyebrow">{{ $asset->organization->name }} · {{ $asset->assetType->category->name }}</p>
    <h2 style="margin-top:6px">{{ $asset->assetType->name }}</h2>
    <p>
        <b class="mono">{{ $asset->asset_code }}</b>
        @if ($asset->serial_number) · SN {{ $asset->serial_number }} @endif
    </p>
    <div class="btn-row" style="margin-top:14px">
        <x-status :status="$asset->status" />
        <span class="badge badge--plain">Kondisi: {{ $asset->condition->label() }}</span>
        @if ($asset->location)
            <span class="badge badge--plain tone-muted"><x-icon name="pin" /> {{ $asset->location->name }}</span>
        @endif
    </div>
</section>

<div class="grid grid--main">
    <div class="stack" style="--gap:18px">
        {{-- Cover & galeri --}}
        <div class="stack" style="--gap:12px">
            @if ($cover)
                <div class="hero-media">
                    @if ($isVideo($cover))
                        <video controls preload="metadata" playsinline>
                            <source src="{{ Storage::url($cover->file_path) }}" type="{{ $cover->metadata['mime'] ?? 'video/mp4' }}">
                        </video>
                    @else
                        <a href="{{ Storage::url($cover->file_path) }}" target="_blank" rel="noopener">
                            <img src="{{ Storage::url($cover->file_path) }}" alt="Foto unit {{ $asset->asset_code }}">
                        </a>
                    @endif
                </div>
            @else
                <div class="hero-media">
                    <x-empty icon="image" title="Belum ada foto unit"
                             text="Minta operator mengunggah foto atau video unit ini di halaman detail aset.">
                        @can('view', $asset)
                            <x-btn :href="route('admin.assets.show', $asset)" variant="ghost" size="sm" icon="box">Buka Detail Aset</x-btn>
                        @endcan
                    </x-empty>
                </div>
            @endif

            @if ($photoList->isNotEmpty())
                <div>
                    <div class="divider--label" style="margin-bottom:10px">Galeri Unit ({{ $photoList->count() }})</div>
                    <div class="gallery">
                        @foreach ($photoList as $photo)
                            <figure class="gallery__item" style="margin:0">
                                <a href="{{ Storage::url($photo->file_path) }}" target="_blank" rel="noopener">
                                    <img src="{{ Storage::url($photo->file_path) }}" alt="{{ $photo->title ?: 'Foto ' . $asset->asset_code }}" loading="lazy">
                                </a>
                                <figcaption>{{ $photo->title ?: 'Foto unit' }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if ($videoList->isNotEmpty())
            <x-card title="Video Unit" icon="video" :delay="60" subtitle="Cara pemakaian dan kondisi Unit saat direkam.">
                <div class="grid grid--2">
                    @foreach ($videoList as $video)
                        <figure style="margin:0">
                            <video controls preload="metadata" playsinline style="border-radius:var(--r-md);background:#000"
                                   aria-label="{{ $video->title }}">
                                <source src="{{ Storage::url($video->file_path) }}" type="{{ $video->metadata['mime'] ?? 'video/mp4' }}">
                            </video>
                            <figcaption class="small muted" style="margin-top:6px">{{ $video->title ?: 'Video unit' }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            </x-card>
        @endif

        {{-- Spesifikasi --}}
        <x-card title="Spesifikasi" icon="clipboard" :delay="120" :subtitle="'Detail unit ' . $asset->asset_code . '.'">
            <div class="inline-alert tone-{{ $asset->status->tone() }}" style="margin-bottom:14px">
                <x-icon name="info" />
                <div>{{ $availability }}</div>
            </div>

            <div class="bar-row" style="margin-bottom:12px">
                <span class="bar-row__label">Kondisi</span>
                <span class="meter meter--{{ $asset->condition->tone() }}">
                    <span class="meter__fill" style="--w:{{ $asset->condition->score() }}%"></span>
                </span>
                <span class="bar-row__value">{{ $asset->condition->score() }}/100</span>
            </div>
            <p class="small muted" style="margin-bottom:14px">{{ $conditionText }}</p>

            <dl class="kv">
                <div class="kv__row">
                    <dt>Tipe</dt>
                    <dd>{{ $asset->assetType->name }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Brand / Model</dt>
                    <dd>{{ trim(($asset->assetType->brand ?? '—') . ($asset->assetType->model ? ' / ' . $asset->assetType->model : '')) }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Kategori</dt>
                    <dd>{{ $asset->assetType->category->name }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Lokasi</dt>
                    <dd>{{ $asset->location?->name ?? '—' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Status</dt>
                    <dd><x-status :status="$asset->status" /></dd>
                </div>
                <div class="kv__row">
                    <dt>Kondisi</dt>
                    <dd><x-status :status="$asset->condition" /></dd>
                </div>
            </dl>
        </x-card>
    </div>

    {{-- Aksi untuk pengunjung halaman produk --}}
    <div class="stack sticky-panel" style="--gap:18px">
        <x-card title="Apa yang Bisa Dilakukan" icon="sparkle" :delay="0" tint>
            <div class="quick-grid">
                @can('reservation.create')
                    <a class="quick tone-brand" href="{{ route('my.reservations.create', ['asset' => $asset->id]) }}" style="--d:0ms">
                        <span class="quick__icon"><x-icon name="calendar" /></span>
                        <b>Ajukan Reservasi</b>
                        <span>Pilih tanggal pakai, lalu ambil di lokasi</span>
                    </a>
                @endcan
                @can('issue.create')
                    <a class="quick tone-warn" href="{{ route('admin.issues.create', ['asset' => $asset->id]) }}" style="--d:60ms">
                        <span class="quick__icon"><x-icon name="alert" /></span>
                        <b>Laporkan Masalah</b>
                        <span>Kerusakan atau kekurangan saat dipakai</span>
                    </a>
                @endcan
                @can('view', $asset)
                    <a class="quick tone-muted" href="{{ route('admin.assets.show', $asset) }}" style="--d:120ms">
                        <span class="quick__icon"><x-icon name="box" /></span>
                        <b>Detail Aset Lengkap</b>
                        <span>Riwayat peminjaman, maintenance, dan lampiran</span>
                    </a>
                @endcan
            </div>
        </x-card>

        <x-card title="Cara Memakai Halaman Ini" icon="info" :delay="60">
            <div class="stack" style="--gap:11px">
                <div class="inline-alert tone-brand">
                    <x-icon name="scan" />
                    <div><b>Tujuan scan QR.</b> Pastikan kode unit di halaman ini sama dengan label pada fisik unit.</div>
                </div>
                <div class="inline-alert tone-accent">
                    <x-icon name="clipboard" />
                    <div><b>Sebelum mengambil.</b> Catat kondisi unit bersama petugas di halaman serah-terima.</div>
                </div>
                <div class="inline-alert tone-warn">
                    <x-icon name="clock" />
                    <div><b>Kembalikan tepat waktu.</b> Keterlambatan menambah poin sanksi peminjam.</div>
                </div>
            </div>
        </x-card>

        <x-card title="Pengenal Unit" icon="qr" :delay="120" subtitle="Bantu petugas mencocokkan unit ini.">
            <dl class="kv">
                <div class="kv__row"><dt>Kode Aset</dt><dd class="mono">{{ $asset->asset_code }}</dd></div>
                <div class="kv__row"><dt>Serial</dt><dd class="mono">{{ $asset->serial_number ?? '—' }}</dd></div>
                <div class="kv__row">
                    <dt>Terbeli</dt>
                    <dd>{{ $asset->purchase_date?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Garansi s/d</dt>
                    <dd>{{ $asset->warranty_until?->format('d M Y') ?? '—' }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</div>
@endsection
