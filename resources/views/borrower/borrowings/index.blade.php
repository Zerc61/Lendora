{{-- resources/views/borrower/borrowings/index.blade.php — Peminjaman yang sedang & pernah dipinjam peminjam --}}
@extends('layouts.app')
@section('title', 'Pinjaman Saya')
@section('chrome', 'Pinjaman')

@section('content')
@php
    $page = $borrowings->getCollection();
    $terlambat = $page->filter(fn ($b) => $b->isOverdue() || $b->status->value === 'overdue');
    $dipinjam = $page->filter(fn ($b) => in_array($b->status->value, ['approved', 'borrowed', 'overdue'], true));
    $selesai = $page->filter(fn ($b) => in_array($b->status->value, ['returned', 'rejected', 'cancelled'], true));
@endphp

<x-page-head title="Pinjaman Saya"
             subtitle="Aset yang sedang Anda pegang beserta batas pengembaliannya.">
    <x-btn :href="route('my.reservations.index')" variant="ghost" icon="calendar">Reservasi Saya</x-btn>
</x-page-head>

@if ($terlambat->isNotEmpty())
    <div class="inline-alert tone-bad" style="margin-bottom:16px">
        <x-icon name="alert" />
        <div>
            <b>{{ $terlambat->count() }} peminjaman lewat tenggat.</b>
            Segera serahkan unit ke petugas peminjaman agar tidak kena sanksi.
        </div>
    </div>
@endif

<p class="eyebrow eyebrow--muted" style="margin-bottom:10px">Ringkasan halaman {{ $borrowings->currentPage() }} dari {{ number_format($borrowings->lastPage(), 0, ',', '.') }}</p>

<div class="grid grid--stats" style="margin-bottom:18px">
    <x-stat label="Sedang Dipinjam" :value="$dipinjam->count()" icon="bag" tone="brand" :delay="0"
            hint="Disetujui, dipinjam, atau terlambat" />
    <x-stat label="Lewat Tenggat" :value="$terlambat->count()" icon="clock" :tone="$terlambat->isNotEmpty() ? 'bad' : 'muted'"
            :delay="60" hint="Perlu dikembalikan segera" />
    <x-stat label="Selesai" :value="$selesai->count()" icon="check-circle" tone="ok" :delay="120"
            hint="Sudah dikembalikan atau batal" />
</div>

<x-card title="Daftar Peminjaman" icon="bag"
        :subtitle="'Halaman ' . $borrowings->currentPage() . ' · ' . number_format($borrowings->count(), 0, ',', '.') . ' dari ' . number_format($borrowings->total(), 0, ',', '.') . ' transaksi'" :delay="0">
    @forelse ($page as $i => $b)
        @php
            $unit = $b->items->map(fn ($item) => $item->asset?->asset_code)->filter();
            $telat = $b->isOverdue() || $b->status->value === 'overdue';
            $telatBadge = $telat && $b->status->value !== 'overdue';
            $sisa = $b->due_at ? (int) now()->diffInDays($b->due_at, false) : null;
        @endphp
        <div class="list__row list__row--link" style="--d:{{ $i * 45 }}ms">
            <a class="list__main" href="{{ route('my.borrowings.show', $b) }}">
                <b>
                    <span class="table__code">{{ $b->code }}</span>
                    <x-status :status="$b->status" />
                    @if ($telatBadge)<span class="badge tone-bad">Terlambat</span>@endif
                </b>
                <span>
                    {{ $b->items->count() }} unit
                    @if ($unit->isNotEmpty()) · {{ $unit->take(2)->implode(', ') }}@if ($unit->count() > 2) +{{ $unit->count() - 2 }}@endif @endif
                </span>
                <span @class(['tiny', 'dim' => ! $telat]) @if ($telat) style="color:var(--bad);font-weight:800" @endif>
                    @if ($b->status->value === 'returned')
                        dikembalikan {{ $b->returned_at?->format('d M Y H:i') ?? '—' }}
                    @elseif ($b->due_at === null)
                        tanpa batas pengembalian
                    @elseif ($sisa > 0)
                        tenggat {{ $b->due_at->format('d M Y H:i') }} · sisa {{ $sisa }} hari
                    @elseif ($sisa === 0)
                        tenggat {{ $b->due_at->format('d M Y H:i') }} · jatuh tempo hari ini
                    @else
                        tenggat {{ $b->due_at->format('d M Y H:i') }} · terlambat {{ abs($sisa) }} hari
                    @endif
                </span>
            </a>

            <span class="list__side">
                <x-btn :href="route('my.borrowings.show', $b)" size="sm" variant="ghost" icon="chevron-right"
                       :aria-label="'Detail peminjaman ' . $b->code" />
            </span>
        </div>
    @empty
        <x-empty icon="bag" title="Belum ada peminjaman"
                 text="Transaksi muncul setelah reservasi Anda disetujui dan unit diserahkan petugas.">
            <x-btn :href="route('my.reservations.create')" variant="primary" size="sm" icon="plus">Ajukan Reservasi</x-btn>
        </x-empty>
    @endforelse

    @if ($borrowings->hasPages())
        <div class="card__foot">{{ $borrowings->links() }}</div>
    @endif
</x-card>
@endsection
