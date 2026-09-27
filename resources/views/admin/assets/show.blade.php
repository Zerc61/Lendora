{{-- resources/views/admin/assets/show.blade.php — Detail aset: kesehatan, identitas, media, riwayat, aksi --}}
@extends('layouts.app')
@section('title', $asset->asset_code)
@section('chrome', 'Aset')

@section('content')
@php
    // Lampiran: pisahkan media (foto/video) dari dokumen
    $isVideo = fn ($a) => str_starts_with($a->metadata['mime'] ?? '', 'video/') || $a->type->value === 'video';
    $media = $asset->attachments->filter(fn ($a) => $isVideo($a) || $a->type->value === 'photo'
        || in_array($a->metadata['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true));
    $docs = $asset->attachments->reject(fn ($a) => $media->contains($a));
    $cover = $media->firstWhere('is_cover', true) ?? $media->first();
    $gallery = $cover ? $media->reject(fn ($a) => $a->id === $cover->id)->values() : collect();
    $canRetire = ! in_array($asset->status->value, ['retired', 'borrowed', 'reserved']);

    // Riwayat gabungan (inspeksi + maintenance + issue) untuk satu timeline
    $dot = fn (string $t) => in_array($t, ['bad', 'warn', 'orange']) ? 'bad' : ($t === 'ok' ? 'ok' : 'brand');
    $events = collect([
        $inspections->map(fn ($x) => ['at' => $x->inspected_at, 'st' => $x->condition, 'dot' => $dot($x->condition->tone()),
            'title' => 'Inspeksi · ' . $x->stage->label(), 'text' => 'Kondisi ' . $x->condition->label() . ' oleh '
                . ($x->inspector?->name ?? '—') . ($x->notes ? ' — ' . $x->notes : '')]),
        $asset->maintenanceTickets->map(fn ($x) => ['at' => $x->created_at, 'st' => $x->status, 'dot' => $dot($x->status->tone()),
            'title' => 'Maintenance · ' . $x->type->label(), 'href' => route('admin.tickets.show', $x),
            'text' => 'Tiket ' . $x->code . ' · teknisi ' . ($x->technician?->name ?? 'belum ditentukan') . ' · '
                . ($x->completed_at ? 'selesai ' . $x->completed_at->format('d M Y') : 'belum selesai')]),
        $asset->issues->map(fn ($x) => ['at' => $x->created_at, 'st' => $x->status, 'dot' => $dot($x->status->tone()),
            'title' => 'Issue · ' . $x->type->label(), 'href' => route('admin.issues.show', $x),
            'text' => $x->code . ' · severity ' . $x->severity->label() . ' — ' . \Illuminate\Support\Str::limit($x->description, 80)]),
    ])->flatten(1);
    $totalEvents = $events->count();
    $events = $events->sortByDesc('at')->take(12)->values();
@endphp

<x-page-head :back="route('admin.assets.index')" :title="$asset->asset_code"
             :subtitle="$asset->assetType->name . ' · ' . $asset->assetType->category->name . ($asset->location ? ' · ' . $asset->location->name : '')">
    <x-status :status="$asset->status" />
    @can('update', $asset)
        <x-btn :href="route('admin.assets.edit', $asset)" variant="ghost" icon="edit">Edit</x-btn>
    @endcan
    <x-btn :href="route('showcase', $asset->asset_code)" variant="ghost" icon="external" target="_blank" rel="noopener">Halaman Produk</x-btn>
    <x-btn :href="route('admin.assets.qr-label', $asset)" variant="primary" icon="printer">Cetak Label QR</x-btn>
</x-page-head>

<div class="grid grid--main">
    {{-- ── Kolom kiri: kesehatan, identitas, media, riwayat, jadwal ── --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Kesehatan Aset" subtitle="Gabungan kondisi fisik, status, usia, dan riwayat layanan" icon="shield" tint :delay="0">
            <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">
                <div class="donut">
                    <svg viewBox="0 0 100 100" role="img" aria-label="Skor kesehatan {{ $health['score'] }} dari 100">
                        <circle cx="50" cy="50" r="42" stroke="var(--surface-3)"></circle>
                        <circle cx="50" cy="50" r="42" stroke="{{ $health['color'] }}" stroke-dasharray="264"
                                stroke-dashoffset="{{ round(264 - 264 * $health['score'] / 100) }}"></circle>
                    </svg>
                    <span class="donut__center"><b class="tnum">{{ $health['score'] }}</b><span>{{ $health['label'] }}</span></span>
                </div>
                <div class="stack" style="--gap:7px;flex:1;min-width:190px">
                    @foreach ($health['breakdown'] as $line)
                        <div class="btn-row" style="gap:8px"><x-icon name="info" style="width:14px;height:14px;color:var(--dim);flex:none" /><span class="small muted">{{ $line }}</span></div>
                    @endforeach
                </div>
            </div>
        </x-card>

        <x-card title="Identitas & Spesifikasi" icon="box" :delay="60" subtitle="Data inventori, pembelian, dan garansi unit.">
            <dl class="kv">
                <div class="kv__row"><dt>Tipe</dt><dd>{{ $asset->assetType->name }}</dd></div>
                <div class="kv__row"><dt>Kategori</dt><dd>{{ $asset->assetType->category->name }}</dd></div>
                <div class="kv__row"><dt>Status</dt><dd><x-status :status="$asset->status" /></dd></div>
                <div class="kv__row"><dt>Kondisi</dt><dd><x-status :status="$asset->condition" /></dd></div>
                <div class="kv__row"><dt>Serial Number</dt><dd class="mono">{{ $asset->serial_number ?? '—' }}</dd></div>
                <div class="kv__row"><dt>Lokasi</dt><dd>{{ $asset->location?->name ?? '—' }}</dd></div>
                <div class="kv__row"><dt>Tanggal Beli</dt><dd>{{ $asset->purchase_date?->format('d M Y') ?? '—' }}</dd></div>
                <div class="kv__row"><dt>Harga</dt>
                    <dd class="tnum">{{ $asset->purchase_price ? 'Rp ' . number_format((float) $asset->purchase_price, 0, ',', '.') : '—' }}</dd></div>
                <div class="kv__row"><dt>Garansi s/d</dt>
                    <dd>{{ $asset->warranty_until?->format('d M Y') ?? '—' }}@if ($asset->warranty_until?->isPast()) <span class="badge tone-bad">Habis</span>@endif</dd></div>
                <div class="kv__row"><dt>Catatan</dt><dd>{{ $asset->notes ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="Media & Lampiran" icon="image" :delay="120" subtitle="Foto, video, manual, dan dokumen unit ini">
            @can('update', $asset)
                <form method="POST" action="{{ route('admin.assets.attachments.store', $asset) }}" enctype="multipart/form-data" class="form" style="margin-bottom:14px">
                    @csrf
                    <div class="form-grid">
                        <x-field name="file" label="Unggah Berkas" required hint="Foto, video, atau PDF — maks 50 MB.">
                            <x-slot:control><input id="f-file" type="file" name="file" required accept=".jpg,.jpeg,.png,.webp,.mp4,.webm,.mov,.pdf"></x-slot:control>
                        </x-field>
                        <x-field name="type" label="Jenis Lampiran" required>
                            <x-slot:control>
                                <select id="f-type" name="type" required>
                                    @foreach (\App\Enums\AttachmentType::cases() as $jenis)<option value="{{ $jenis->value }}">{{ $jenis->label() }}</option>@endforeach
                                </select>
                            </x-slot:control>
                        </x-field>
                    </div>
                    <div class="btn-row btn-row--end">
                        <button type="submit" class="btn btn--primary"><x-icon name="download" /> Unggah</button>
                    </div>
                </form>
            @endcan

            @if ($cover)
                <div class="hero-media" style="margin-bottom:12px">
                    @if ($isVideo($cover))
                        <video controls preload="metadata" playsinline aria-label="{{ $cover->title }}"><source src="{{ Storage::url($cover->file_path) }}" type="{{ $cover->metadata['mime'] ?? 'video/mp4' }}"></video>
                    @else
                        <a href="{{ Storage::url($cover->file_path) }}" target="_blank" rel="noopener"><img src="{{ Storage::url($cover->file_path) }}" alt="{{ $cover->title ?: 'Foto ' . $asset->asset_code }}"></a>
                    @endif
                    <span class="gallery__item__flag">Cover</span>
                </div>
            @endif

            @if ($gallery->isNotEmpty())
                <div class="gallery" style="margin-bottom:14px">
                    @foreach ($gallery as $att)
                        <figure class="gallery__item" style="margin:0">
                            @if ($isVideo($att))
                                <video controls preload="metadata" playsinline aria-label="{{ $att->title }}"><source src="{{ Storage::url($att->file_path) }}" type="{{ $att->metadata['mime'] ?? 'video/mp4' }}"></video>
                                <span class="gallery__item__flag">Video</span>
                            @else
                                <a href="{{ Storage::url($att->file_path) }}" target="_blank" rel="noopener"><img src="{{ Storage::url($att->file_path) }}" alt="{{ $att->title ?: 'Foto ' . $asset->asset_code }}" loading="lazy"></a>
                            @endif
                            <figcaption>{{ $att->title ?: 'Media unit' }}</figcaption>
                            @can('update', $asset)
                                <div class="gallery__tools">
                                    @unless ($att->is_cover)
                                        <form method="POST" action="{{ route('admin.attachments.cover', $att) }}">
                                            @csrf <button class="btn btn--soft" type="submit" title="Jadikan cover halaman produk">Cover</button>
                                        </form>
                                    @endunless
                                    <form method="POST" action="{{ route('admin.attachments.destroy', $att) }}" data-confirm="Hapus media ini?">
                                        @csrf @method('DELETE') <button class="btn btn--danger" type="submit" title="Hapus media" aria-label="Hapus media"><x-icon name="trash" /></button>
                                    </form>
                                </div>
                            @endcan
                        </figure>
                    @endforeach
                </div>
            @endif

            @if (! $media->isNotEmpty() && ! $docs->isNotEmpty())
                <x-empty icon="image" title="Belum ada media unit"
                         text="Unggah foto atau video agar peminjam bisa mengenali unit ini dari hasil scan QR." />
            @else
                <div class="divider--label">Dokumen &amp; Manual</div>
                <div class="list">
                    @forelse ($docs as $doc)
                        <div class="list__row" style="--d:{{ $loop->index * 40 }}ms">
                            <span class="list__main">
                                <b><a href="{{ Storage::url($doc->file_path) }}" target="_blank" rel="noopener">{{ $doc->title }}</a></b>
                                <span>{{ $doc->type->label() }} · {{ $doc->uploader?->name ?? '—' }}</span>
                            </span>
                            <span class="list__side">
                                <x-status :status="$doc->type" />
                                @can('update', $asset)
                                    <form method="POST" action="{{ route('admin.attachments.destroy', $doc) }}" data-confirm="Hapus lampiran ini?" style="display:inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn--danger btn--sm" type="submit" title="Hapus lampiran" aria-label="Hapus lampiran"><x-icon name="trash" /></button>
                                    </form>
                                @endcan
                            </span>
                        </div>
                    @empty
                        <p class="small dim" style="padding:9px 0">Belum ada manual atau dokumen untuk unit ini.</p>
                    @endforelse
                </div>
            @endif
        </x-card>

        <x-card title="Riwayat Aset" icon="clock" :delay="180"
                :subtitle="$totalEvents . ' aktivitas tercatat — inspeksi, maintenance, dan issue.'">
            <div class="timeline">
                @forelse ($events as $i => $event)
                    <div class="timeline__item timeline__item--{{ $event['dot'] }}" style="--d:{{ $i * 45 }}ms">
                        <b>@if (! empty($event['href']))<a href="{{ $event['href'] }}">{{ $event['title'] }}</a>@else{{ $event['title'] }}@endif <x-status :status="$event['st']" /></b>
                        <time>{{ $event['at']->format('d M Y · H:i') }}</time>
                        <p>{{ $event['text'] }}</p>
                    </div>
                @empty
                    <x-empty icon="clock" title="Belum ada riwayat"
                             text="Aktivitas inspeksi, maintenance, dan issue akan muncul di sini." />
                @endforelse
            </div>
        </x-card>

        <x-card title="Jadwal Reservasi Mendatang" icon="calendar" :delay="240"
                subtitle="Jadwal yang masih Hold dan belum selesai pada unit ini.">
            @forelse ($schedules as $i => $item)
                <div class="list__row" style="--d:{{ $i * 45 }}ms">
                    <span class="list__main">
                        <b class="table__code">{{ $item->reservation->code }}</b>
                        <span>{{ $item->reservation->start_at->format('d M Y H:i') }} → {{ $item->reservation->end_at->format('d M Y H:i') }}</span>
                        <span>{{ $item->reservation->user?->name ?? 'Peminjam dihapus' }}</span>
                    </span>
                    <span class="list__side"><x-status :status="$item->reservation->status" /></span>
                </div>
            @empty
                <x-empty icon="calendar" title="Unit bebas dipakai" text="Tidak ada reservasi mendatang untuk unit ini." />
            @endforelse
        </x-card>
    </div>

    {{-- ── Kolom kanan: QR + aksi ── --}}
    <div class="stack sticky-panel" style="--gap:18px">
        <x-card title="Label QR Unit" icon="qr" :delay="0" subtitle="Cetak dan tempel di fisik unit.">
            <div class="qrbox">
                <div class="qr">
                    <img src="{{ route('assets.qr', $asset->asset_code) }}" alt="QR code {{ $asset->asset_code }}">
                    <span>{{ $asset->asset_code }}</span>
                </div>
            </div>
            <x-btn :href="route('admin.assets.qr-label', $asset)" size="sm" variant="primary" icon="printer" block style="margin-top:12px">Cetak Label QR</x-btn>
        </x-card>

        <x-card title="Aksi" icon="sparkle" :delay="60">
            <div class="stack" style="--gap:8px">
                @can('reservation.create')
                    <x-btn :href="route('my.reservations.create', ['asset' => $asset->id])" variant="primary" block icon="calendar">Reservasi Unit Ini</x-btn>
                @endcan
                @can('checkout.perform')
                    @if ($pendingCheckout)<x-btn :href="route('admin.checkout.show', $pendingCheckout)" variant="accent" block icon="out">Check-out {{ $pendingCheckout->code }}</x-btn>@endif
                @endcan
                @can('checkin.perform')
                    @if ($activeBorrowing)<x-btn :href="route('admin.checkin.show', $activeBorrowing)" variant="accent" block icon="in">Check-in {{ $activeBorrowing->code }}</x-btn>@endif
                @endcan
                @can('issue.create')
                    <x-btn :href="route('admin.issues.create', ['asset' => $asset->id])" variant="soft" block icon="alert">Laporkan Masalah</x-btn>
                @endcan
                @can('maintenance.create')
                    <x-btn :href="route('admin.tickets.create', ['asset' => $asset->id])" variant="soft" block icon="wrench">Buat Tiket Maintenance</x-btn>
                @endcan
                @can('update', $asset)
                    <x-btn :href="route('admin.assets.edit', $asset)" variant="ghost" block icon="edit">Ubah Data Aset</x-btn>
                @endcan
            </div>

            <div class="card__foot stack" style="--gap:8px">
                @can('update', $asset)
                    @if ($canRetire)
                        <form method="POST" action="{{ route('admin.assets.retire', $asset) }}" data-confirm="Pensiunkan aset ini? Status Dipensiunkan bersifat permanen.">
                            @csrf <button type="submit" class="btn btn--soft btn--block"><x-icon name="minus" /> Pensiunkan Aset</button>
                        </form>
                    @endif
                @endcan
                @can('delete', $asset)
                    <form method="POST" action="{{ route('admin.assets.destroy', $asset) }}" data-confirm="Hapus aset {{ $asset->asset_code }}? Tindakan ini tidak dapat dibatalkan.">
                        @csrf @method('DELETE') <button type="submit" class="btn btn--danger btn--block"><x-icon name="trash" /> Hapus Aset</button>
                    </form>
                @endcan
            </div>
        </x-card>
    </div>
</div>
@endsection
