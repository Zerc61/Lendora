{{-- resources/views/staff/dashboard.blade.php — Beranda staf: fokus antrean kerja --}}
@extends('layouts.staff')
@section('title', 'Antrean Kerja')
@section('chrome', 'Antrean Kerja')

@section('content')
<x-page-head title="Halo, {{ \Illuminate\Support\Str::before($user->name, ' ') }} 👋"
             subtitle="Prioritas hari ini: serah-terima aset dan keputusan reservasi.">
    @can('reservation.approve')
        <x-btn href="{{ route('admin.reservations.index') }}" variant="ghost" icon="calendar">Reservasi</x-btn>
    @endcan
    <x-btn href="{{ route('scan') }}" variant="primary" icon="scan">Scan QR</x-btn>
</x-page-head>

{{-- Deck antrean: kartu besar, klik = langsung ke antrean --}}
<div class="deck" style="margin-bottom:18px">
    <x-queue-card label="Siap Check-out" :value="$stats['checkout']" icon="out" tone="brand"
                  hint="Disetujui, belum diserahkan" :href="route('admin.checkout.index')" :delay="0" />
    <x-queue-card label="Belum Dikembalikan" :value="$stats['checkin']" icon="in" tone="warn"
                  hint="Termasuk terlambat" :href="route('admin.checkin.index')" :delay="70" />
    @can('reservation.approve')
        <x-queue-card label="Reservasi Menunggu" :value="$stats['reservations']" icon="calendar" tone="info"
                      hint="Perlu keputusan Anda" :href="route('admin.reservations.index')" :delay="140" />
    @endcan
    <x-queue-card label="Terlambat" :value="$stats['overdue']" icon="alert" :tone="$stats['overdue'] > 0 ? 'bad' : 'muted'"
                  hint="Lewat batas pengembalian" :href="route('admin.checkin.index')" :delay="210" />
</div>

<div class="grid grid--stats" style="margin-bottom:18px">
    <x-stat label="Check-out Hari Ini" :value="$stats['today']" icon="out" tone="brand" :delay="0" />
    <x-stat label="Dikembalikan Hari Ini" :value="$stats['returned']" icon="in" tone="ok" :delay="60" />
    <x-stat label="Total Antrean" :value="$stats['checkout'] + $stats['checkin'] + $stats['reservations']"
            icon="clock" tone="accent" :delay="120" />
</div>

<div class="grid grid--main">
    <div class="stack" style="--gap:18px">
        <x-card title="Siap Diserahkan" subtitle="Pinjaman yang sudah disetujui, urut dari yang paling lama" icon="out" :delay="0">
            <x-slot:actions>
                <a class="btn btn--primary btn--sm" href="{{ route('admin.checkout.index') }}">Buka antrean</a>
            </x-slot:actions>

            @forelse ($toCheckout as $i => $borrowing)
                <a class="list__row list__row--link" href="{{ route('admin.checkout.show', $borrowing) }}"
                   style="--d:{{ $i * 45 }}ms">
                    <span class="avatar avatar--plain">{{ $borrowing->borrower?->initials() ?? '?' }}</span>
                    <span class="list__main">
                        <b>{{ $borrowing->borrower?->name ?? 'Peminjam dihapus' }}</b>
                        <span class="table__code">{{ $borrowing->code }}</span>
                        <span>{{ $borrowing->items->count() }} aset · {{ $borrowing->purpose ? \Illuminate\Support\Str::limit($borrowing->purpose, 30) : 'Tanpa catatan' }}</span>
                    </span>
                    <span class="list__side">
                        <x-status :status="$borrowing->status" />
                        <span class="tiny dim" style="display:block;margin-top:3px">due {{ $borrowing->due_at?->format('d/m H:i') ?? '—' }}</span>
                    </span>
                </a>
            @empty
                <x-empty icon="check-circle" title="Antrean check-out kosong" text="Semua pinjaman disetujui sudah diserahkan.">
                    <x-btn href="{{ route('admin.borrowings.index') }}" variant="ghost" size="sm">Lihat peminjaman</x-btn>
                </x-empty>
            @endforelse
        </x-card>

        <x-card title="Menunggu Pengembalian" subtitle="Urut dari yang paling mendesak" icon="in" :delay="80">
            <x-slot:actions>
                <a class="btn btn--primary btn--sm" href="{{ route('admin.checkin.index') }}">Buka antrean</a>
            </x-slot:actions>

            @forelse ($toCheckin as $i => $borrowing)
                <a class="list__row list__row--link" href="{{ route('admin.checkin.show', $borrowing) }}"
                   style="--d:{{ $i * 45 }}ms">
                    <span class="avatar avatar--plain">{{ $borrowing->borrower?->initials() ?? '?' }}</span>
                    <span class="list__main">
                        <b>{{ $borrowing->borrower?->name ?? 'Peminjam dihapus' }}</b>
                        <span class="table__code">{{ $borrowing->code }}</span>
                        <span>{{ $borrowing->items->count() }} aset · tenggat {{ $borrowing->due_at?->format('d M Y') ?? '—' }}</span>
                    </span>
                    <span class="list__side">
                        <x-status :status="$borrowing->status" />
                        @if ($borrowing->isOverdue())
                            <span class="tiny" style="display:block;margin-top:3px;color:var(--bad);font-weight:800">
                                terlambat {{ (int) now()->diffInDays($borrowing->due_at, false) > 0 ? abs(now()->diffInDays($borrowing->due_at, false)) . ' hari' : 'hari ini' }}
                            </span>
                        @endif
                    </span>
                </a>
            @empty
                <x-empty icon="check-circle" title="Semua aset sudah kembali" text="Tidak ada pinjaman aktif saat ini." />
            @endforelse
        </x-card>
    </div>

    <div class="stack" style="--gap:18px">
        @can('reservation.approve')
        <x-card title="Reservasi Menunggu" icon="calendar" tint :delay="0">
            <x-slot:actions><a class="btn btn--ghost btn--sm" href="{{ route('admin.reservations.index') }}">Semua</a></x-slot:actions>

            @forelse ($reservations as $i => $reservation)
                <a class="list__row list__row--link" href="{{ route('admin.reservations.show', $reservation) }}" style="--d:{{ $i * 45 }}ms">
                    <span class="list__main">
                        <b>{{ $reservation->user?->name ?? 'Peminjam dihapus' }}</b>
                        <span>{{ $reservation->start_at?->format('d M') }} → {{ $reservation->end_at?->format('d M Y') }}</span>
                    </span>
                    <span class="list__side">
                        <span class="badge tone-muted">{{ $reservation->items->count() }} aset</span>
                        <span class="tiny dim" style="display:block;margin-top:3px">{{ $reservation->created_at->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <x-empty icon="calendar" title="Tidak ada reservasi menunggu" text="Semua reservasi sudah diproses." />
            @endforelse
        </x-card>
        @endcan

        <x-card title="Panduan Cepat" icon="info" :delay="80">
            <div class="stack" style="--gap:11px">
                <div class="inline-alert tone-accent">
                    <x-icon name="qr" />
                    <div><b>Scan QR saat serah-terima.</b> Membuka halaman aset langsung, mempercepat verifikasi fisik.</div>
                </div>
                <div class="inline-alert tone-brand">
                    <x-icon name="clipboard" />
                    <div><b>Catat kondisi saat check-in.</b> Kerusakan yang tidak dicatat berujung sengketa.</div>
                </div>
                <div class="inline-alert tone-warn">
                    <x-icon name="alert" />
                    <div><b>Temukan kerusakan?</b> Buat issue agar masuk tiket maintenance otomatis.</div>
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection
