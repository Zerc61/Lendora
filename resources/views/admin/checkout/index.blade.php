{{-- resources/views/admin/checkout/index.blade.php — Antrean serah-terima (check-out) --}}
@extends('layouts.app')
@section('title', 'Antrean Check-out')
@section('chrome', 'Check-out')

@section('content')
@php
    $halaman = $borrowings->getCollection();
    $unitAntrean = $halaman->sum(fn ($b) => $b->items->count());
    $pertama = $borrowings->first();
    $lewatBatas = $halaman->filter(fn ($b) => $b->due_at && $b->due_at->isPast())->count();
    $rekapTipe = $halaman->flatMap(fn ($b) => $b->items)
        ->map(fn ($i) => $i->asset?->assetType?->name ?? 'Tanpa tipe')
        ->countBy()->sortDesc();
@endphp

<x-page-head title="Antrean Check-out"
             subtitle="Pinjaman yang sudah disetujui dan menunggu unit diserahkan ke peminjam.">
    @can('checkin.perform')
        <x-btn :href="route('admin.checkin.index')" variant="ghost" icon="in">Antrean Check-in</x-btn>
    @endcan
    <x-btn :href="route('admin.borrowings.index')" variant="ghost" icon="bag">Semua Peminjaman</x-btn>
</x-page-head>

<div class="grid grid--stats">
    <x-stat label="Menunggu Serah-terima" :value="$borrowings->total()" icon="out" tone="brand"
            hint="Disetujui, <b>belum</b> diserahkan" :delay="0" />
    <x-stat label="Unit di Antrean" :value="$unitAntrean" icon="box" tone="accent"
            :hint="'Halaman ' . $borrowings->currentPage() . ' · ' . $borrowings->count() . ' transaksi'" :delay="60" />
    <x-stat label="Tenggat Terdekat" :value="$pertama?->due_at?->format('d M H:i') ?? '—'" icon="clock" tone="warn"
            :hint="$pertama ? 'Peminjam <b>' . ($pertama->borrower?->name ?? '—') . '</b>' : 'Antrean kosong'" :delay="120" />
    <x-stat label="Batas Terlewat" :value="$lewatBatas" icon="alert" :tone="$lewatBatas > 0 ? 'warn' : 'muted'"
            :hint="$lewatBatas > 0 ? 'Tenggat <b>sudah lewat</b> saat diserahkan' : 'Semua tenggat masih di masa depan'"
            :delay="180" />
</div>

<x-card title="Siap Disyerahkan" subtitle="Urut dari batas pengembalian terdekat." icon="out" :delay="80">
    @forelse ($borrowings as $i => $b)
        @php
            $sisa = $b->due_at ? (int) now()->diffInDays($b->due_at, false) : null;
            $kode = $b->items->map(fn ($i) => $i->asset?->asset_code)->filter()->values();
        @endphp
        <div class="list__row list__row--link" style="--d:{{ min($i, 5) * 50 }}ms">
            <span class="avatar avatar--plain">{{ $b->borrower?->initials() ?? '?' }}</span>
            <span class="list__main">
                <b>
                    <a href="{{ route('admin.checkout.show', $b) }}">{{ $b->borrower?->name ?? 'Peminjam dihapus' }}</a>
                </b>
                <span class="table__code">{{ $b->code }} · {{ $b->items->count() }} unit</span>
                <span class="table__sub">
                    {{ $kode->take(3)->implode(', ') ?: 'Tanpa unit' }}
                    @if ($kode->count() > 3)<span class="badge tone-muted">+{{ $kode->count() - 3 }}</span>@endif
                    · dibuat {{ $b->created_at->locale('id')->diffForHumans() }}
                </span>
                <span class="truncate">{{ $b->purpose ?: 'Tanpa catatan tujuan' }}</span>
            </span>
            <span class="list__side">
                <span class="badge tone-warn nowrap">
                    <x-icon name="clock" /> due {{ $b->due_at?->format('d/m H:i') ?? '—' }}
                </span>
                <span class="tiny dim" style="display:block;margin-top:3px">
                    @if ($sisa !== null && $sisa > 0) sisa {{ $sisa }} hari
                    @elseif ($sisa === 0) jatuh tempo hari ini
                    @elseif ($sisa !== null) tenggat terlewat
                    @endif
                </span>
            </span>
            <x-btn :href="route('admin.checkout.show', $b)" variant="primary" size="sm" icon="out">Proses</x-btn>
        </div>
    @empty
        <x-empty icon="check-circle" title="Antrean check-out kosong"
                 text="Semua pinjaman yang disetujui sudah diserahkan ke peminjam.">
            <x-btn :href="route('admin.borrowings.index')" variant="ghost" size="sm">Lihat peminjaman</x-btn>
        </x-empty>
    @endforelse

    @if ($borrowings->hasPages())
        <div class="card__foot">{{ $borrowings->links() }}</div>
    @endif
</x-card>

@if ($rekapTipe->isNotEmpty())
    <x-card title="Unit yang Perlu Disiapkan" icon="box" :delay="140"
            subtitle="Rekap tipe aset di halaman antrean ini — siapkan sebelum memanggil peminjam.">
        <div class="list">
            @foreach ($rekapTipe as $tipe => $jumlah)
                <div class="list__row" style="--d:{{ min($loop->index, 5) * 50 }}ms">
                    <span class="list__main"><b>{{ $tipe }}</b></span>
                    <span class="list__side"><span class="badge tone-muted">{{ $jumlah }} unit</span></span>
                </div>
            @endforeach
        </div>
    </x-card>
@endif
@endsection
