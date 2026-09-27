{{-- resources/views/admin/checkin/index.blade.php — Antrean pengembalian (check-in) --}}
@extends('layouts.app')
@section('title', 'Antrean Check-in')
@section('chrome', 'Check-in')

@section('content')
@php
    $halaman = $borrowings->getCollection();
    $terlambatAntrean = $halaman
        ->filter(fn ($b) => $b->status->value === 'overdue' || $b->isOverdue())
        ->count();
    $unitAntrean = $halaman->sum(fn ($b) => $b->items->count());
    $pertama = $borrowings->first();
    $rekapTipe = $halaman->flatMap(fn ($b) => $b->items)
        ->map(fn ($i) => $i->asset?->assetType?->name ?? 'Tanpa tipe')
        ->countBy()->sortDesc();
@endphp

<x-page-head title="Antrean Check-in"
             subtitle="Pinjaman aktif yang masih menunggu aset dikembalikan beserta pemeriksaan kondisi.">
    @can('checkout.perform')
        <x-btn :href="route('admin.checkout.index')" variant="ghost" icon="out">Antrean Check-out</x-btn>
    @endcan
    <x-btn :href="route('admin.borrowings.index')" variant="ghost" icon="bag">Semua Peminjaman</x-btn>
</x-page-head>

<div class="grid grid--stats">
    <x-stat label="Menunggu Pengembalian" :value="$borrowings->total()" icon="in" tone="brand"
            hint="Termasuk yang <b>terlambat</b>" :delay="0" />
    <x-stat label="Terlambat" :value="$terlambatAntrean" icon="alert" :tone="$terlambatAntrean > 0 ? 'bad' : 'muted'"
            :hint="'Dari ' . $borrowings->count() . ' transaksi di halaman ' . $borrowings->currentPage()" :delay="60" />
    <x-stat label="Tenggat Terdekat" :value="$pertama?->due_at?->format('d M H:i') ?? '—'" icon="clock" tone="warn"
            :hint="$pertama ? 'Peminjam <b>' . e($pertama->borrower?->name ?? '—') . '</b>' : 'Antrean kosong'" :delay="120" />
    <x-stat label="Unit Menunggu" :value="$unitAntrean" icon="box" tone="accent"
            :hint="'Perlu diperiksa pada ' . $borrowings->count() . ' transaksi'" :delay="180" />
</div>

<x-card title="Menunggu Dikembalikan" subtitle="Urut dari batas pengembalian terlama lebih dulu." icon="in" :delay="80">
    @forelse ($borrowings as $i => $b)
        @php
            $terlambat = $b->status->value === 'overdue' || $b->isOverdue();
            $sisa = $b->due_at ? (int) now()->diffInDays($b->due_at, false) : null;
            $kode = $b->items->map(fn ($i) => $i->asset?->asset_code)->filter()->values();
        @endphp
        <div class="list__row list__row--link" style="--d:{{ min($i, 5) * 50 }}ms">
            <x-avatar :user="$b->borrower" plain />
            <span class="list__main">
                <b>
                    <a href="{{ route('admin.checkin.show', $b) }}">{{ $b->borrower?->name ?? 'Peminjam dihapus' }}</a>
                </b>
                <span class="table__code">{{ $b->code }} · {{ $b->items->count() }} unit</span>
                <span class="table__sub">
                    {{ $kode->take(3)->implode(', ') ?: 'Tanpa unit' }}
                    @if ($kode->count() > 3)<span class="badge tone-muted">+{{ $kode->count() - 3 }}</span>@endif
                    @if ($b->checked_out_at)· sejak {{ $b->checked_out_at->locale('id')->diffForHumans() }}@endif
                </span>
                <span class="nowrap">tenggat {{ $b->due_at?->format('d M Y H:i') ?? '—' }}</span>
            </span>
            <span class="list__side">
                @if ($terlambat)
                    <span class="badge tone-bad">
                        <x-icon name="alert" /> Terlambat{{ $sisa !== null && $sisa < 0 ? ' ' . abs($sisa) . ' hari' : '' }}
                    </span>
                @elseif ($sisa !== null && $sisa > 0)
                    <span class="badge tone-ok">Sisa {{ $sisa }} hari</span>
                @else
                    <span class="badge tone-warn">Jatuh tempo hari ini</span>
                @endif
            </span>
            <x-btn :href="route('admin.checkin.show', $b)" variant="primary" size="sm" icon="in">Proses</x-btn>
        </div>
    @empty
        <x-empty icon="check-circle" title="Semua aset sudah kembali"
                 text="Tidak ada pinjaman aktif yang menunggu pengembalian.">
            <x-btn :href="route('admin.borrowings.index')" variant="ghost" size="sm">Lihat peminjaman</x-btn>
        </x-empty>
    @endforelse

    @if ($borrowings->hasPages())
        <div class="card__foot">{{ $borrowings->links() }}</div>
    @endif
</x-card>

@if ($rekapTipe->isNotEmpty())
    <x-card title="Unit yang Menunggu Kembali" icon="box" :delay="140"
            subtitle="Rekap tipe aset di halaman antrean ini — bandingkan dengan unit yang dibawa peminjam.">
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
