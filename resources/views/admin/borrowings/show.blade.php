{{-- resources/views/admin/borrowings/show.blade.php — Detail transaksi peminjaman --}}
@extends('layouts.app')
@section('title', 'Peminjaman ' . $borrowing->code)
@section('chrome', 'Peminjaman')

@section('content')
@php
    $terlambat = $borrowing->status->value === 'overdue' || $borrowing->isOverdue();
    $sisaHari = $borrowing->due_at ? (int) now()->diffInDays($borrowing->due_at, false) : null;
    $alur = ['Disetujui', 'Check-out', 'Dikembalikan'];
    $kodeAset = $borrowing->items->filter()->keyBy('asset_id');
    $selesai = match (true) {
        $borrowing->status->value === 'returned' => 3,
        in_array($borrowing->status->value, ['borrowed', 'overdue'], true) => 2,
        in_array($borrowing->status->value, ['rejected', 'cancelled'], true) => 0,
        default => 1,
    };
@endphp

<x-page-head :crumbs="['Peminjaman' => route('admin.borrowings.index'), $borrowing->code => null]"
             :title="$borrowing->code"
             :subtitle="($borrowing->borrower?->name ?? 'Peminjam dihapus') . ' · ' . $borrowing->items->count() . ' unit · ' . \Illuminate\Support\Str::limit($borrowing->purpose ?: 'Tanpa catatan', 60)">
    <x-status :status="$borrowing->status" />
    @if ($terlambat)<span class="badge tone-bad">Terlambat</span>@endif
    @if ($borrowing->reservation)
        <x-btn :href="route('admin.reservations.show', $borrowing->reservation)" variant="ghost" icon="calendar">
            Reservasi {{ $borrowing->reservation->code }}
        </x-btn>
    @endif
</x-page-head>

<div class="grid grid--main">
    {{-- Kolom kiri --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Ringkasan Transaksi" icon="clipboard" :delay="0">
            <dl class="kv">
                <div class="kv__row">
                    <dt>Peminjam</dt>
                    <dd>{{ $borrowing->borrower?->name ?? '—' }}<span class="table__sub">{{ $borrowing->borrower?->email }}</span></dd>
                </div>
                <div class="kv__row">
                    <dt>Reservasi</dt>
                    <dd>
                        @if ($borrowing->reservation)
                            <a href="{{ route('admin.reservations.show', $borrowing->reservation) }}">{{ $borrowing->reservation->code }}</a>
                            <x-status :status="$borrowing->reservation->status" />
                        @else
                            <span class="dim">Pinjaman langsung</span>
                        @endif
                    </dd>
                </div>
                <div class="kv__row">
                    <dt>Tujuan</dt>
                    <dd>{{ $borrowing->purpose ?: 'Tidak ada catatan' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Disetujui oleh</dt>
                    <dd>{{ $borrowing->approvedBy?->name ?? '—' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Check-out</dt>
                    <dd class="nowrap">
                        {{ $borrowing->checked_out_at?->format('d M Y H:i') ?? 'Belum diserahkan' }}
                        <span class="table__sub">oleh {{ $borrowing->checkedOutBy?->name ?? '—' }}</span>
                    </dd>
                </div>
                <div class="kv__row">
                    <dt>Batas kembali</dt>
                    <dd class="nowrap">
                        {{ $borrowing->due_at?->format('d M Y H:i') ?? '—' }}
                        <span class="table__sub">
                            @if ($borrowing->status->value === 'returned')
                                selesai dikembalikan {{ $borrowing->returned_at?->format('d/m/Y') ?? '—' }}
                            @elseif ($sisaHari === null)
                                <span class="dim">tanpa tenggat</span>
                            @elseif ($sisaHari > 0)
                                sisa {{ $sisaHari }} hari
                            @elseif ($sisaHari === 0)
                                jatuh tempo hari ini
                            @else
                                <span style="color:var(--bad);font-weight:800">terlambat {{ abs($sisaHari) }} hari</span>
                            @endif
                        </span>
                    </dd>
                </div>
                <div class="kv__row">
                    <dt>Check-in</dt>
                    <dd class="nowrap">
                        {{ $borrowing->returned_at?->format('d M Y H:i') ?? 'Belum dikembalikan' }}
                        <span class="table__sub">oleh {{ $borrowing->checkedInBy?->name ?? '—' }}</span>
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

        <x-card flush title="Unit" subtitle="Kondisi tercatat pada saat keluar dan kembali." icon="box" :delay="60">
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
                                    @if ($item->condition_out)<x-status :status="$item->condition_out" />@else<span class="dim">—</span>@endif
                                    @if ($item->notes_out)<span class="table__sub clamp-2">{{ $item->notes_out }}</span>@endif
                                </td>
                                <td>
                                    @if ($item->condition_in)<x-status :status="$item->condition_in" />@else<span class="dim">—</span>@endif
                                    @if ($item->notes_in)<span class="table__sub clamp-2">{{ $item->notes_in }}</span>@endif
                                </td>
                                <td class="hide-sm">@if ($item->asset?->status)<x-status :status="$item->asset->status" />@else<span class="dim">—</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty icon="box" title="Tidak ada unit" text="Transaksi ini belum punya aset." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card title="Riwayat Inspeksi" subtitle="Catatan kondisi aset pada tiap tahap serah-terima." icon="clipboard" :delay="120">
            @if ($borrowing->inspections->isEmpty())
                <x-empty icon="clipboard" title="Belum ada inspeksi"
                         text="Inspeksi tercatat otomatis setiap proses check-out dan check-in." />
            @else
                <div class="timeline">
                    @foreach ($borrowing->inspections->sortByDesc('inspected_at')->values() as $i => $ins)
                        <div class="timeline__item {{ $ins->stage->value === 'checkin' ? 'timeline__item--ok' : 'timeline__item--brand' }}"
                             style="--d:{{ $i * 60 }}ms">
                            <b>{{ $kodeAset->get($ins->asset_id)?->asset?->asset_code ?? '—' }} · {{ $ins->stage->label() }}</b>
                            <time datetime="{{ $ins->inspected_at->toIso8601String() }}">{{ $ins->inspected_at->format('d M Y H:i') }}</time>
                            <p>
                                {{ $ins->condition->label() }} oleh {{ $ins->inspector?->name ?? '—' }}
                                @if (($ins->checklist['outcome'] ?? null) && $ins->checklist['outcome'] !== 'ok')
                                    · hasil {{ $ins->checklist['outcome'] }}
                                @endif
                            </p>
                            @if ($ins->notes)<p>{{ $ins->notes }}</p>@endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        @if ($issues->isNotEmpty())
            <x-card title="Issue Terkait" subtitle="Kerusakan atau kehilangan unit dari transaksi ini." icon="alert" tint :delay="160">
                <div class="list">
                    @foreach ($issues as $i => $issue)
                        <div class="list__row" style="--d:{{ $i * 50 }}ms">
                            <span class="list__main">
                                <b>
                                    @can('issue.view')
                                        <a href="{{ route('admin.issues.show', $issue) }}">{{ $issue->code }}</a>
                                    @else
                                        {{ $issue->code }}
                                    @endcan
                                    · {{ $issue->type->label() }}
                                </b>
                                <span>{{ $issue->asset?->asset_code ?? '—' }} · {{ $issue->description }}</span>
                            </span>
                            <span class="list__side">
                                <div class="btn-row btn-row--end">
                                    <x-status :status="$issue->severity" />
                                    <x-status :status="$issue->status" />
                                </div>
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>

    {{-- Kolom kanan: alur + tindakan --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Tahap Transaksi" icon="layers" :delay="0">
            @if (in_array($borrowing->status->value, ['rejected', 'cancelled'], true))
                <div class="inline-alert tone-muted">
                    <x-icon name="info" />
                    <div>Transaksi ini berakhir sebagai <b>{{ $borrowing->status->label() }}</b> — tidak ada alur serah-terima.</div>
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

        <x-card title="Tindakan" icon="out" tint :delay="60">
            @if ($borrowing->status->value === 'pending')
                <p class="small muted">Pinjaman disetujui, unit belum diserahkan. Lanjutkan ke proses check-out untuk mencatat kondisi awal.</p>
                @can('checkout.perform')
                    <x-btn :href="route('admin.checkout.show', $borrowing)" variant="primary" size="lg" block icon="out"
                            style="margin-top:12px">Proses Check-out</x-btn>
                @endcan
            @elseif ($borrowing->status->value === 'approved')
                <div class="inline-alert tone-info">
                    <x-icon name="info" />
                    <div>
                        <b>Menunggu penyerahan unit.</b>
                        Transaksi ini berstatus {{ $borrowing->status->label() }} dan belum masuk antrean check-out,
                        sehingga layar serah-terima belum dapat dibuka.
                    </div>
                </div>
            @elseif (in_array($borrowing->status->value, ['borrowed', 'overdue'], true))
                @if ($terlambat)
                    <div class="inline-alert tone-bad" style="margin-bottom:12px">
                        <x-icon name="alert" />
                        <div><b>Lewat batas pengembalian.</b> Segera proses check-in agar aset kembali tercatat.</div>
                    </div>
                @endif
                <p class="small muted">Aset sedang dipinjam peminjam. Catat kondisi akhir saat pengembalian diterima.</p>
                @can('checkin.perform')
                    <x-btn :href="route('admin.checkin.show', $borrowing)" variant="primary" size="lg" block icon="in"
                            style="margin-top:12px">Proses Check-in</x-btn>
                @endcan
            @elseif ($borrowing->status->value === 'returned')
                <div class="inline-alert tone-ok">
                    <x-icon name="check-circle" />
                    <div>
                        <b>Selesai dikembalikan.</b>
                        {{ $borrowing->returned_at?->format('d M Y H:i') }} oleh {{ $borrowing->checkedInBy?->name ?? 'petugas' }}.
                    </div>
                </div>
            @else
                <p class="small muted">Tidak ada tindakan tersedia untuk status {{ $borrowing->status->label() }}.</p>
            @endif
        </x-card>
    </div>
</div>
@endsection
