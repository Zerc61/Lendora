{{-- resources/views/borrower/reservations/show.blade.php — Detail reservasi milik peminjam --}}
@extends('layouts.app')
@section('title', 'Reservasi ' . $reservation->code)
@section('chrome', 'Reservasi')

@section('content')
@php
    $bisaBatal = in_array($reservation->status->value, ['pending', 'approved'], true);
@endphp

<x-page-head :crumbs="['Reservasi Saya' => route('my.reservations.index'), $reservation->code => null]"
             :title="$reservation->code"
             :subtitle="$reservation->items->count() . ' unit · ' . $reservation->start_at?->format('d M Y H:i') . ' → ' . $reservation->end_at?->format('d M Y H:i')">
    <x-status :status="$reservation->status" />

    @if ($bisaBatal)
        <form method="POST" action="{{ route('my.reservations.cancel', $reservation) }}"
              data-confirm="Batalkan reservasi {{ $reservation->code }}? Unit yang dicadangkan akan dilepas.">
            @csrf
            <button type="submit" class="btn btn--danger"><x-icon name="x" /> Batalkan</button>
        </form>
    @endif
</x-page-head>

@if ($reservation->rejection_reason)
    <div class="inline-alert tone-bad" style="margin-bottom:16px">
        <x-icon name="x" />
        <div>
            <b>Reservasi ditolak oleh admin.</b>
            {{ $reservation->rejection_reason }}
        </div>
    </div>
@endif

<div class="grid grid--main">
    {{-- Kolom kiri --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Detail Pengajuan" icon="clipboard" :delay="0">
            <dl class="kv">
                <div class="kv__row">
                    <dt>Pemohon</dt>
                    <dd>
                        <span class="cell-media">
                            <span class="avatar avatar--plain">{{ $reservation->user?->initials() ?? '?' }}</span>
                            <span class="cell-media__body">
                                <b>{{ $reservation->user?->name ?? 'Peminjam dihapus' }}</b>
                                <span>{{ $reservation->user?->organization?->name ?? 'Tanpa organisasi' }}</span>
                            </span>
                        </span>
                    </dd>
                </div>
                <div class="kv__row">
                    <dt>Periode</dt>
                    <dd class="nowrap">
                        {{ $reservation->start_at?->format('d M Y H:i') }} → {{ $reservation->end_at?->format('d M Y H:i') }}
                        <span class="table__sub">{{ $reservation->start_at?->locale('id')->diffForHumans($reservation->end_at, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}</span>
                    </dd>
                </div>
                <div class="kv__row">
                    <dt>Tujuan</dt>
                    <dd>{{ $reservation->purpose ?: 'Tidak ada catatan' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Diajukan</dt>
                    <dd class="nowrap">
                        {{ $reservation->created_at->format('d M Y H:i') }}
                        <span class="table__sub">{{ $reservation->created_at->locale('id')->diffForHumans() }}</span>
                    </dd>
                </div>
                <div class="kv__row">
                    <dt>Diputuskan oleh</dt>
                    <dd>
                        {{ $reservation->approvedBy?->name ?? '—' }}
                        @if ($reservation->approved_at)
                            <span class="table__sub">{{ $reservation->approved_at->format('d M Y H:i') }}</span>
                        @else
                            <span class="table__sub">Belum ada keputusan</span>
                        @endif
                    </dd>
                </div>
                @if ($reservation->rejection_reason)
                    <div class="kv__row">
                        <dt>Alasan penolakan</dt>
                        <dd>{{ $reservation->rejection_reason }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        <x-card flush title="Unit yang Diajukan" subtitle="Kondisi unit saat pengajuan dicatat." icon="box" :delay="60">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Kode Unit</th>
                            <th scope="col" class="hide-sm">Tipe</th>
                            <th scope="col">Kondisi</th>
                            <th scope="col">Status Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservation->items as $item)
                            <tr>
                                <td>
                                    <span class="table__code">{{ $item->asset?->asset_code ?? '—' }}</span>
                                    <span class="table__sub">diminta {{ $item->quantity }}×</span>
                                </td>
                                <td class="hide-sm">{{ $item->asset?->assetType?->name ?? '—' }}</td>
                                <td>@if ($item->asset?->condition)<x-status :status="$item->asset->condition" />@else<span class="dim">—</span>@endif</td>
                                <td>@if ($item->asset?->status)<x-status :status="$item->asset->status" />@else<span class="dim">—</span>@endif</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <x-empty icon="box" title="Tidak ada unit"
                                             text="Pengajuan ini tidak memuat unit aset." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- Kolom kanan: alur + langkah selanjutnya --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Alur Reservasi" icon="layers" :delay="0">
            <div class="timeline">
                <div class="timeline__item timeline__item--brand" style="--d:0ms">
                    <b>Reservasi diajukan</b>
                    <time datetime="{{ $reservation->created_at->toIso8601String() }}">{{ $reservation->created_at->format('d M Y H:i') }}</time>
                    <p>{{ $reservation->items->count() }} unit untuk periode {{ $reservation->start_at?->format('d M Y') }} — {{ $reservation->end_at?->format('d M Y') }}.</p>
                </div>

                @if ($reservation->status->value === 'pending')
                    <div class="timeline__item" style="--d:60ms">
                        <b>Menunggu keputusan admin</b>
                        <time>{{ $reservation->created_at->locale('id')->diffForHumans() }}</time>
                        <p>Anda masih bisa membatalkan pengajuan ini selama belum disetujui.</p>
                    </div>
                @elseif ($reservation->status->value === 'approved')
                    <div class="timeline__item timeline__item--ok" style="--d:60ms">
                        <b>Disetujui</b>
                        <time>{{ $reservation->approved_at?->format('d M Y H:i') ?? '—' }}</time>
                        <p>{{ $reservation->approvedBy?->name ?? 'Admin' }} — datang ke lokasi peminjaman untuk check-out.</p>
                    </div>
                @elseif (in_array($reservation->status->value, ['rejected', 'cancelled', 'expired'], true))
                    <div class="timeline__item timeline__item--bad" style="--d:60ms">
                        <b>{{ $reservation->status->label() }}</b>
                        <time>{{ $reservation->approved_at?->format('d M Y H:i') ?? $reservation->end_at?->format('d M Y H:i') }}</time>
                        <p>{{ $reservation->approvedBy?->name ?? 'Sistem' }}{{ $reservation->rejection_reason ? ' — ' . $reservation->rejection_reason : '' }}</p>
                    </div>
                @endif

                @if ($reservation->borrowing)
                    <div class="timeline__item timeline__item--brand" style="--d:120ms">
                        <b>Transaksi peminjaman dibuat</b>
                        <time>{{ $reservation->borrowing->created_at->format('d M Y H:i') }}</time>
                        <p>
                            <a href="{{ route('my.borrowings.show', $reservation->borrowing) }}">{{ $reservation->borrowing->code }}</a>
                            · {{ $reservation->borrowing->status->label() }}
                            @if ($reservation->borrowing->due_at)
                                · tenggat {{ $reservation->borrowing->due_at->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </x-card>

        <x-card title="Langkah Selanjutnya" icon="info" tint :delay="60">
            @if ($reservation->status->value === 'pending')
                <p class="small muted">Tunggu keputusan admin. Notifikasi akan masuk begitu reservasi disetujui atau ditolak.</p>
                <x-btn :href="route('notifications.index')" variant="soft" icon="bell" block style="margin-top:12px">
                    Lihat Notifikasi
                </x-btn>
            @elseif ($reservation->status->value === 'approved')
                <p class="small muted">Reservasi disetujui. Datang ke lokasi peminjaman, petugas akan scan QR dan mencatat kondisi unit sebelum aset diserahkan.</p>
                @if ($reservation->borrowing)
                    <x-btn :href="route('my.borrowings.show', $reservation->borrowing)" variant="primary" size="lg" block icon="bag"
                           style="margin-top:12px">Lihat Peminjaman {{ $reservation->borrowing->code }}</x-btn>
                @endif
            @elseif ($reservation->status->value === 'fulfilled')
                <p class="small muted">Reservasi ini sudah menjadi transaksi peminjaman. Lihat riwayat aset pada halaman peminjaman.</p>
                @if ($reservation->borrowing)
                    <x-btn :href="route('my.borrowings.show', $reservation->borrowing)" variant="primary" block icon="bag"
                           style="margin-top:12px">Buka {{ $reservation->borrowing->code }}</x-btn>
                @endif
            @else
                <p class="small muted">Reservasi ini tidak dilanjutkan. Anda bisa mengajukan baru kapan saja selama unit tersedia.</p>
                <x-btn :href="route('my.reservations.create')" variant="primary" block icon="plus" style="margin-top:12px">
                    Ajukan Reservasi Baru
                </x-btn>
            @endif
        </x-card>
    </div>
</div>
@endsection
