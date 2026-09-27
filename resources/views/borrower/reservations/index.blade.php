{{-- resources/views/borrower/reservations/index.blade.php — Daftar reservasi milik peminjam --}}
@extends('layouts.app')
@section('title', 'Reservasi Saya')
@section('chrome', 'Reservasi')

@section('content')
@php
    $menunggu = $reservations->filter(fn ($r) => $r->status->value === 'pending')->count();
@endphp

<x-page-head title="Reservasi Saya"
             subtitle="Riwayat pengajuan aset beserta keputusan persetujuan admin.">
    <x-btn :href="route('my.reservations.index')" variant="ghost" icon="bag">Pinjaman Saya</x-btn>
    <x-btn :href="route('my.reservations.create')" variant="primary" icon="plus">Ajukan Reservasi</x-btn>
</x-page-head>

@if ($menunggu > 0)
    <div class="callout" style="margin-bottom:16px">
        <x-icon name="clock" />
        <div>
            <b>{{ $menunggu }} reservasi menunggu keputusan</b>
            <p>Admin akan menyetujui atau menolak dengan alasan. Anda bisa membatalkan pengajuan selama statusnya masih menunggu atau disetujui.</p>
        </div>
    </div>
@endif

<x-card title="Riwayat Reservasi" icon="calendar"
        :subtitle="'Menampilkan ' . number_format($reservations->count(), 0, ',', '.') . ' dari ' . number_format($reservations->total(), 0, ',', '.') . ' pengajuan'" :delay="0">
    <x-slot:actions>
        <span class="badge tone-muted">{{ number_format($reservations->total(), 0, ',', '.') }} total</span>
    </x-slot:actions>

    @forelse ($reservations as $i => $r)
        @php
            $unit = $r->items->map(fn ($item) => $item->asset?->asset_code)->filter();
            $bisaBatal = in_array($r->status->value, ['pending', 'approved'], true);
        @endphp
        <div class="list__row list__row--link" style="--d:{{ $i * 45 }}ms">
            <a class="list__main" href="{{ route('my.reservations.show', $r) }}">
                <b>
                    <span class="table__code">{{ $r->code }}</span>
                    <x-status :status="$r->status" />
                </b>
                <span class="nowrap">{{ $r->start_at?->format('d M Y H:i') }} → {{ $r->end_at?->format('d M Y H:i') }}</span>
                <span class="clamp-2">{{ \Illuminate\Support\Str::limit($r->purpose, 90) }}</span>
                <span class="tiny dim">
                    {{ $r->items->count() }} unit
                    @if ($unit->isNotEmpty()) · {{ $unit->take(3)->implode(', ') }}@if ($unit->count() > 3) +{{ $unit->count() - 3 }}@endif @endif
                    · diajukan {{ $r->created_at->locale('id')->diffForHumans() }}
                </span>
            </a>

            <span class="list__side">
                @if ($bisaBatal)
                    <form method="POST" action="{{ route('my.reservations.cancel', $r) }}"
                          data-confirm="Batalkan reservasi {{ $r->code }}? Unit yang dicadangkan akan dilepas.">
                        @csrf
                        <button type="submit" class="btn btn--ghost btn--sm">Batalkan</button>
                    </form>
                @else
                    <x-btn :href="route('my.reservations.show', $r)" size="sm" variant="ghost" icon="chevron-right"
                           :aria-label="'Detail reservasi ' . $r->code" />
                @endif
            </span>
        </div>
    @empty
        <x-empty icon="calendar" title="Belum ada reservasi"
                 text="Ajukan reservasi untuk aset yang Anda perlukan, lalu ambil di lokasi setelah disetujui.">
            <x-btn :href="route('my.reservations.create')" variant="primary" size="sm" icon="plus">Ajukan Reservasi</x-btn>
        </x-empty>
    @endforelse

    @if ($reservations->hasPages())
        <div class="card__foot">{{ $reservations->links() }}</div>
    @endif
</x-card>
@endsection
