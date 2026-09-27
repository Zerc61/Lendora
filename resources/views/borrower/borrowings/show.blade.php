{{-- resources/views/borrower/borrowings/show.blade.php — Detail transaksi peminjaman milik peminjam --}}
@extends('layouts.app')
@section('title', 'Peminjaman ' . $borrowing->code)
@section('chrome', 'Pinjaman')

@section('content')
@php
    $telat = $borrowing->isOverdue() || $borrowing->status->value === 'overdue';
    $telatBadge = $telat && $borrowing->status->value !== 'overdue';
    $sisa = $borrowing->due_at ? (int) now()->diffInDays($borrowing->due_at, false) : null;
    $alur = ['Disetujui', 'Check-out', 'Dikembalikan'];
    $selesai = match (true) {
        $borrowing->status->value === 'returned' => 3,
        in_array($borrowing->status->value, ['borrowed', 'overdue'], true) => 2,
        in_array($borrowing->status->value, ['rejected', 'cancelled'], true) => 0,
        default => 1,
    };
@endphp

<x-page-head :crumbs="['Pinjaman Saya' => route('my.borrowings.index'), $borrowing->code => null]"
             :title="$borrowing->code"
             :subtitle="$borrowing->items->count() . ' unit · ' . \Illuminate\Support\Str::limit($borrowing->purpose ?: 'Tanpa catatan', 60)">
    <x-status :status="$borrowing->status" />
    @if ($telatBadge)<span class="badge tone-bad">Terlambat</span>@endif
    @if ($borrowing->reservation)
        <x-btn :href="route('my.reservations.show', $borrowing->reservation)" variant="ghost" icon="calendar">
            Reservasi {{ $borrowing->reservation->code }}
        </x-btn>
    @endif
</x-page-head>

@if ($telat)
    <div class="inline-alert tone-bad" style="margin-bottom:16px">
        <x-icon name="alert" />
        <div>
            <b>Aset melewati batas pengembalian.</b>
            @if ($sisa !== null && $sisa < 0)
                Terlambat {{ abs($sisa) }} hari dari tenggat {{ $borrowing->due_at?->format('d M Y H:i') }}.
            @else
                Tenggat pengembalian {{ $borrowing->due_at?->format('d M Y H:i') }}.
            @endif
            Serahkan unit ke petugas agar kondisi tercatat dan sanksi tidak bertambah.
        </div>
    </div>
@endif

<div class="grid grid--main">
    {{-- Kolom kiri --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Ringkasan Transaksi" icon="clipboard" :delay="0">
            <dl class="kv">
                <div class="kv__row">
                    <dt>Tujuan</dt>
                    <dd>{{ $borrowing->purpose ?: 'Tidak ada catatan' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Disetujui oleh</dt>
                    <dd>
                        {{ $borrowing->approvedBy?->name ?? '—' }}
                        @if ($borrowing->approved_at)
                            <span class="table__sub">{{ $borrowing->approved_at->format('d M Y H:i') }}</span>
                        @endif
                    </dd>
                </div>
                <div class="kv__row">
                    <dt>Check-out</dt>
                    <dd class="nowrap">
                        {{ $borrowing->checked_out_at?->format('d M Y H:i') ?? 'Belum diserahkan' }}
                        <span class="table__sub">
                            oleh {{ $borrowing->checkedOutBy?->name ?? '—' }}
                            @if ($borrowing->checked_out_at) · {{ $borrowing->checked_out_at->locale('id')->diffForHumans() }}@endif
                        </span>
                    </dd>
                </div>
                <div class="kv__row">
                    <dt>Batas kembali</dt>
                    <dd class="nowrap">
                        {{ $borrowing->due_at?->format('d M Y H:i') ?? '—' }}
                        <span class="table__sub">
                            @if ($borrowing->status->value === 'returned')
                                selesai dikembalikan {{ $borrowing->returned_at?->format('d/m/Y') ?? '—' }}
                            @elseif ($sisa === null)
                                <span class="dim">tanpa tenggat</span>
                            @elseif ($sisa > 0)
                                sisa {{ $sisa }} hari
                            @elseif ($sisa === 0)
                                jatuh tempo hari ini
                            @else
                                <span style="color:var(--bad);font-weight:800">terlambat {{ abs($sisa) }} hari</span>
                            @endif
                        </span>
                    </dd>
                </div>
                <div class="kv__row">
                    <dt>Check-in</dt>
                    <dd class="nowrap">
                        {{ $borrowing->returned_at?->format('d M Y H:i') ?? 'Belum dikembalikan' }}
                        @if ($borrowing->returned_at)
                            <span class="table__sub">
                                oleh {{ $borrowing->checkedInBy?->name ?? '—' }}
                                · {{ $borrowing->returned_at->locale('id')->diffForHumans() }}
                            </span>
                        @endif
                    </dd>
                </div>
                @if ($borrowing->checkin_notes)
                    <div class="kv__row">
                        <dt>Catatan check-in</dt>
                        <dd>{{ $borrowing->checkin_notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        <x-card flush title="Unit" subtitle="Kondisi dicatat petugas saat keluar dan kembali." icon="box" :delay="60">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Kode &amp; Tipe</th>
                            <th scope="col">Kondisi Keluar</th>
                            <th scope="col">Kondisi Kembali</th>
                            <th scope="col" class="hide-sm">Status Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($borrowing->items as $item)
                            <tr>
                                <td>
                                    <span class="table__code">{{ $item->asset?->asset_code ?? '—' }}</span>
                                    <span class="table__sub">{{ $item->asset?->assetType?->name ?? '—' }}</span>
                                </td>
                                <td>
                                    @if ($item->condition_out)
                                        <x-status :status="$item->condition_out" />
                                    @else
                                        <span class="dim">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->condition_in)
                                        <x-status :status="$item->condition_in" />
                                    @else
                                        <span class="dim">—</span>
                                    @endif
                                </td>
                                <td class="hide-sm">
                                    @if ($item->asset?->status)
                                        <x-status :status="$item->asset->status" />
                                    @else
                                        <span class="dim">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <x-empty icon="box" title="Tidak ada unit" text="Transaksi ini belum punya aset." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        @if ($issues->isNotEmpty())
            <x-card title="Issue Terkait" subtitle="Laporan kerusakan atau kehilangan unit dari transaksi ini." icon="alert" :delay="120">
                <div class="list">
                    @foreach ($issues as $i => $issue)
                        <div class="list__row" style="--d:{{ $i * 50 }}ms">
                            <span class="list__main">
                                <b>{{ $issue->code }} · {{ $issue->type->label() }}</b>
                                <span>{{ $issue->asset?->asset_code ?? '—' }} · {{ $issue->description }}</span>
                            </span>
                            <span class="list__side">
                                <span class="btn-row btn-row--end">
                                    <x-status :status="$issue->severity" />
                                    <x-status :status="$issue->status" />
                                </span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>

    {{-- Kolom kanan: tahap transaksi + tindakan peminjam --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Tahap Transaksi" icon="layers" :delay="0">
            @if (in_array($borrowing->status->value, ['rejected', 'cancelled'], true))
                <div class="inline-alert tone-muted">
                    <x-icon name="info" />
                    <div>Transaksi berakhir sebagai <b>{{ $borrowing->status->label() }}</b> — tidak ada serah-terima aset.</div>
                </div>
            @else
                <div class="steps">
                    @foreach ($alur as $i => $label)
                        @if ($i > 0)<span class="step__line"></span>@endif
                        <span class="step {{ $i < $selesai ? 'is-done' : ($i === $selesai ? 'is-current' : '') }}">
                            <span class="step__dot">{{ $i + 1 }}</span>
                            <span class="step__label">{{ $label }}</span>
                        </span>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card title="Yang Perlu Anda Lakukan" icon="info" tint :delay="60">
            @if ($borrowing->status->value === 'pending')
                <p class="small muted">Pinjaman menunggu persetujuan admin. Anda akan mendapat notifikasi begitu diputuskan.</p>
                <x-btn :href="route('notifications.index')" variant="soft" icon="bell" block style="margin-top:12px">
                    Lihat Notifikasi
                </x-btn>
            @elseif ($borrowing->status->value === 'approved')
                <p class="small muted">Pinjaman disetujui, unit belum diserahkan. Datang ke lokasi peminjaman dan tunjukkan identitas kepada petugas.</p>
            @elseif (in_array($borrowing->status->value, ['borrowed', 'overdue'], true))
                <p class="small muted">
                    Aset sedang di tangan Anda. Kembalikan sebelum
                    <b>{{ $borrowing->due_at?->format('d M Y H:i') ?? '—' }}</b>
                    dan pastikan kondisi unit tidak berubah.
                </p>
                @if ($telat)
                    <div class="inline-alert tone-bad" style="margin-top:12px">
                        <x-icon name="alert" />
                        <div>Terlambat. Segera serahkan unit ke petugas peminjaman.</div>
                    </div>
                @endif
            @elseif ($borrowing->status->value === 'returned')
                <div class="inline-alert tone-ok">
                    <x-icon name="check-circle" />
                    <div>
                        <b>Selesai dikembalikan.</b>
                        {{ $borrowing->returned_at?->format('d M Y H:i') }} oleh {{ $borrowing->checkedInBy?->name ?? 'petugas' }}.
                    </div>
                </div>
                <x-btn :href="route('my.reservations.create')" variant="primary" block icon="plus" style="margin-top:12px">
                    Ajukan Reservasi Berikutnya
                </x-btn>
            @else
                <p class="small muted">Tidak ada tindakan tersedia untuk status {{ $borrowing->status->label() }}.</p>
            @endif
        </x-card>
    </div>
</div>
@endsection
