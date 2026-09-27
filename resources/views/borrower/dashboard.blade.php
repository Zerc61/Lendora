{{-- resources/views/borrower/dashboard.blade.php — Beranda peminjam --}}
@extends('layouts.borrower')
@section('title', 'Beranda')
@section('chrome', 'Beranda')

@section('content')
{{-- Hero: sapaan + aksi utama --}}
<section class="hero" style="margin-bottom:18px">
    <p class="eyebrow">{{ now()->translatedFormat('l, d F Y') }}</p>
    <h2 style="margin-top:6px">Halo, {{ \Illuminate\Support\Str::before($user->name, ' ') }} 👋</h2>
    <p>
        @if ($stats['overdue'] > 0)
            Ada <b style="color:var(--bad)">{{ $stats['overdue'] }} aset yang lewat tenggat</b>. Segera kembalikan ke petugas agar tidak kena sanksi.
        @elseif ($stats['active'] > 0)
            Anda sedang memegang <b>{{ $stats['active'] }} aset</b>. Ingat tanggal pengembaliannya ya.
        @else
            Butuh aset untuk kegiatan? Ajukan reservasi, admin akan menyetujui dan Anda bisa mengambil di lokasi.
        @endif
    </p>
    <div class="hero__actions">
        @can('reservation.create')
            <x-btn href="{{ route('my.reservations.create') }}" variant="primary" icon="plus">Ajukan Reservasi</x-btn>
        @endcan
        <x-btn href="{{ route('my.borrowings.index') }}" variant="accent" icon="bag">Pinjaman Saya</x-btn>
        @can('viewAny', \App\Models\Asset::class)
            <x-btn href="{{ route('admin.assets.index') }}" variant="ghost" icon="box">Lihat Katalog</x-btn>
        @endcan
    </div>
</section>

{{-- Aksi cepat --}}
<div class="quick-grid" style="margin-bottom:18px">
    <a class="quick tone-brand" href="{{ route('my.reservations.index') }}" style="--d:0ms">
        <span class="quick__icon"><x-icon name="calendar" /></span>
        <b>Reservasi Saya</b>
        <span>{{ $stats['reservasi'] }} menunggu keputusan</span>
    </a>
    <a class="quick tone-accent" href="{{ route('my.borrowings.index') }}" style="--d:60ms">
        <span class="quick__icon"><x-icon name="bag" /></span>
        <b>Sedang Dipinjam</b>
        <span>{{ $stats['active'] }} aset aktif</span>
    </a>
    <a class="quick {{ $stats['overdue'] > 0 ? 'tone-bad' : 'tone-ok' }}"
       href="{{ route('my.borrowings.index', $stats['overdue'] > 0 ? ['status' => 'overdue'] : []) }}"
       style="--d:120ms">
        <span class="quick__icon"><x-icon name="clock" /></span>
        <b>Tenggat & Riwayat</b>
        <span>
            @if ($stats['overdue'] > 0)
                <b style="color:var(--bad)">{{ $stats['overdue'] }} lewat tenggat</b>
            @else
                {{ $stats['total'] }} transaksi · semua aman
            @endif
        </span>
    </a>
    <a class="quick tone-muted" href="{{ route('notifications.index') }}" style="--d:180ms">
        <span class="quick__icon"><x-icon name="bell" /></span>
        <b>Notifikasi</b>
        <span>Info persetujuan & tenggat</span>
    </a>
</div>

<div class="grid grid--main">
    <div class="stack" style="--gap:18px">
        <x-card title="Pinjaman Aktif" subtitle="Aset yang sedang Anda pegang" icon="bag" :delay="0">
            <x-slot:actions>
                <a class="btn btn--ghost btn--sm" href="{{ route('my.borrowings.index') }}">Semua</a>
            </x-slot:actions>

            @forelse ($activeBorrowings as $i => $borrowing)
                <a class="list__row list__row--link" href="{{ route('my.borrowings.show', $borrowing) }}" style="--d:{{ $i * 50 }}ms">
                    <span class="list__main">
                        <b>{{ $borrowing->code }}</b>
                        <span>{{ $borrowing->items->map(fn ($item) => $item->asset?->asset_code)->filter()->join(', ') ?: '—' }}</span>
                        <span class="tiny" @if ($borrowing->isOverdue()) style="color:var(--bad);font-weight:800" @endif>
                            Kembalikan sebelum {{ $borrowing->due_at?->format('d M Y') ?? '—' }}
                        </span>
                    </span>
                    <span class="list__side">
                        <x-status :status="$borrowing->status" />
                    </span>
                </a>
            @empty
                <x-empty icon="bag" title="Tidak ada aset dipinjam"
                         text="Ajukan reservasi dulu, lalu ambil aset Anda di lokasi peminjaman.">
                    @can('reservation.create')
                        <x-btn href="{{ route('my.reservations.create') }}" variant="primary" size="sm" icon="plus">Ajukan Reservasi</x-btn>
                    @endcan
                </x-empty>
            @endforelse
        </x-card>

        <x-card title="Riwayat Terbaru" subtitle="Peminjaman yang sudah selesai" icon="clock" :delay="80">
            @forelse ($history as $i => $borrowing)
                <a class="list__row list__row--link" href="{{ route('my.borrowings.show', $borrowing) }}" style="--d:{{ $i * 45 }}ms">
                    <span class="list__main">
                        <b>{{ $borrowing->code }}</b>
                        <span>{{ $borrowing->items->count() }} aset · dikembalikan {{ $borrowing->returned_at?->format('d M Y') ?? '—' }}</span>
                    </span>
                    <span class="list__side"><x-status :status="$borrowing->status" /></span>
                </a>
            @empty
                <x-empty icon="clock" title="Belum ada riwayat" text="Riwayat akan tampil setelah peminjaman pertama Anda selesai." />
            @endforelse
        </x-card>
    </div>

    <div class="stack" style="--gap:18px">
        <x-card title="Status Reservasi" icon="calendar" tint :delay="0">
            <x-slot:actions>
                @can('reservation.create')
                    <a class="btn btn--primary btn--sm" href="{{ route('my.reservations.create') }}"><x-icon name="plus" /> Baru</a>
                @endcan
            </x-slot:actions>

            @forelse ($reservations as $i => $reservation)
                <a class="list__row list__row--link" href="{{ route('my.reservations.show', $reservation) }}" style="--d:{{ $i * 45 }}ms">
                    <span class="list__main">
                        <b>{{ $reservation->start_at?->format('d M') }} → {{ $reservation->end_at?->format('d M Y') }}</b>
                        <span>{{ $reservation->items->count() }} aset · dibuat {{ $reservation->created_at->diffForHumans() }}</span>
                    </span>
                    <span class="list__side"><x-status :status="$reservation->status" /></span>
                </a>
            @empty
                <x-empty icon="calendar" title="Belum ada reservasi" text="Ajukan reservasi untuk hal yang Anda perlukan.">
                    @can('reservation.create')
                        <x-btn href="{{ route('my.reservations.create') }}" variant="primary" size="sm">Ajukan</x-btn>
                    @endcan
                </x-empty>
            @endforelse
        </x-card>

        <x-card title="Cara Kerja" icon="info" :delay="60">
            <div class="stack" style="--gap:11px">
                <div class="inline-alert tone-brand">
                    <x-icon name="calendar" />
                    <div><b>1. Ajukan reservasi.</b> Tentukan aset dan rentang waktu penggunaan.</div>
                </div>
                <div class="inline-alert tone-accent">
                    <x-icon name="check-circle" />
                    <div><b>2. Menunggu persetujuan.</b> Admin akan menyetujui atau menolak dengan alasan.</div>
                </div>
                <div class="inline-alert tone-ok">
                    <x-icon name="in" />
                    <div><b>3. Ambil & kembalikan.</b> Petugas scan QR, cek kondisi, lalu serahkan aset.</div>
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection
