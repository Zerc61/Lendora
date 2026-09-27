{{-- resources/views/admin/reservations/show.blade.php — Detail reservasi + keputusan persetujuan --}}
@extends('layouts.app')
@section('title', 'Reservasi ' . $reservation->code)
@section('chrome', 'Reservasi')

@section('content')
<x-page-head :crumbs="['Reservasi' => route('admin.reservations.index'), $reservation->code => null]"
             :title="$reservation->code"
             :subtitle="($reservation->user?->name ?? 'Peminjam dihapus') . ' · ' . $reservation->items->count() . ' unit · ' . $reservation->start_at->format('d M Y H:i') . ' s/d ' . $reservation->end_at->format('d M Y H:i')">
    <x-status :status="$reservation->status" />
    @if ($reservation->borrowing)
        <x-btn :href="route('admin.borrowings.show', $reservation->borrowing)" variant="ghost" icon="bag"
                icon-right="arrow-right">Transaksi {{ $reservation->borrowing->code }}</x-btn>
    @endif
</x-page-head>

<div class="grid grid--main">
    {{-- Kolom kiri: detail + unit --}}
    <div class="stack" style="--gap:18px">
        @if ($reservation->rejection_reason)
            <div class="inline-alert tone-bad">
                <x-icon name="x" />
                <div><b>Reservasi ditolak.</b> {{ $reservation->rejection_reason }}</div>
            </div>
        @endif

        <x-card title="Detail Reservasi" icon="clipboard" :delay="0">
            <dl class="kv">
                <div class="kv__row">
                    <dt>Pemohon</dt>
                    <dd>{{ $reservation->user?->name ?? '—' }}<span class="table__sub">{{ $reservation->user?->email }}</span></dd>
                </div>
                <div class="kv__row">
                    <dt>Rentang waktu</dt>
                    <dd class="nowrap">
                        {{ $reservation->start_at->format('d M Y H:i') }} → {{ $reservation->end_at->format('d M Y H:i') }}
                        <span class="table__sub">{{ $reservation->start_at->locale('id')->diffForHumans($reservation->end_at, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}</span>
                    </dd>
                </div>
                <div class="kv__row">
                    <dt>Tujuan</dt>
                    <dd>{{ $reservation->purpose ?: 'Tidak ada catatan' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Diajukan</dt>
                    <dd class="nowrap">{{ $reservation->created_at->format('d M Y H:i') }}
                        <span class="table__sub">{{ $reservation->created_at->locale('id')->diffForHumans() }}</span>
                    </dd>
                </div>
                @if ($reservation->approvedBy)
                    <div class="kv__row">
                        <dt>Diputuskan oleh</dt>
                        <dd>{{ $reservation->approvedBy->name }}
                            <span class="table__sub">{{ $reservation->approved_at?->format('d M Y H:i') }}</span>
                        </dd>
                    </div>
                @endif
                @if ($reservation->rejection_reason)
                    <div class="kv__row">
                        <dt>Alasan penolakan</dt>
                        <dd>{{ $reservation->rejection_reason }}</dd>
                    </div>
                @endif
                @if ($reservation->borrowing)
                    <div class="kv__row">
                        <dt>Transaksi peminjaman</dt>
                        <dd>
                            <a href="{{ route('admin.borrowings.show', $reservation->borrowing) }}">{{ $reservation->borrowing->code }}</a>
                            <x-status :status="$reservation->borrowing->status" />
                        </dd>
                    </div>
                @endif
            </dl>
        </x-card>

        <x-card flush title="Unit Diajukan" subtitle="Unit yang dicadangkan peminjam untuk periode ini." icon="box" :delay="60">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Kode</th>
                            <th scope="col" class="hide-sm">Tipe</th>
                            <th scope="col" class="hide-sm">Lokasi</th>
                            <th scope="col">Kondisi</th>
                            <th scope="col">Status Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reservation->items as $item)
                            <tr>
                                <td>
                                    <span class="table__code">{{ $item->asset?->asset_code ?? '—' }}</span>
                                    <span class="table__sub">diminta {{ $item->quantity }}×</span>
                                </td>
                                <td class="hide-sm">{{ $item->asset?->assetType?->name ?? '—' }}</td>
                                <td class="hide-sm">{{ $item->asset?->location?->name ?? 'Tanpa lokasi' }}</td>
                                <td>@if ($item->asset?->condition)<x-status :status="$item->asset->condition" />@else—@endif</td>
                                <td>@if ($item->asset?->status)<x-status :status="$item->asset->status" />@else—@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- Kolom kanan: timeline persetujuan + keputusan --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Riwayat Persetujuan" icon="clock" :delay="0">
            <div class="timeline">
                <div class="timeline__item timeline__item--brand" style="--d:0ms">
                    <b>Reservasi diajukan</b>
                    <time datetime="{{ $reservation->created_at->toIso8601String() }}">{{ $reservation->created_at->format('d M Y H:i') }}</time>
                    <p>{{ $reservation->user?->name ?? 'Peminjam dihapus' }}</p>
                </div>

                @if ($reservation->status->value === 'rejected')
                    <div class="timeline__item timeline__item--bad" style="--d:60ms">
                        <b>Ditolak</b>
                        <time>{{ $reservation->approved_at?->format('d M Y H:i') ?? '—' }}</time>
                        <p>{{ $reservation->approvedBy?->name ?? 'Tanpa catatan petugas' }}{{ $reservation->rejection_reason ? ' — ' . $reservation->rejection_reason : '' }}</p>
                    </div>
                @elseif ($reservation->approvedBy)
                    <div class="timeline__item timeline__item--ok" style="--d:60ms">
                        <b>Disetujui</b>
                        <time>{{ $reservation->approved_at?->format('d M Y H:i') ?? '—' }}</time>
                        <p>{{ $reservation->approvedBy->name }}</p>
                    </div>
                @else
                    <div class="timeline__item" style="--d:60ms">
                        <b>Menunggu keputusan</b>
                        <p>Antrean persetujuan — {{ $reservation->start_at->locale('id')->diffForHumans() }}</p>
                    </div>
                @endif

                @if ($reservation->borrowing)
                    <div class="timeline__item timeline__item--brand" style="--d:120ms">
                        <b>Transaksi peminjaman dibuat</b>
                        <time>{{ $reservation->borrowing->created_at->format('d M Y H:i') }}</time>
                        <p>
                            <a href="{{ route('admin.borrowings.show', $reservation->borrowing) }}">{{ $reservation->borrowing->code }}</a>
                            · {{ $reservation->borrowing->status->label() }}
                            @if ($reservation->borrowing->due_at)
                                · tenggat {{ $reservation->borrowing->due_at->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                    @if ($reservation->borrowing->returned_at)
                        <div class="timeline__item timeline__item--ok" style="--d:180ms">
                            <b>Aset dikembalikan</b>
                            <time>{{ $reservation->borrowing->returned_at->format('d M Y H:i') }}</time>
                            <p>oleh {{ $reservation->borrowing->checkedInBy?->name ?? 'petugas' }}</p>
                        </div>
                    @endif
                @endif
            </div>
        </x-card>

        @can('reservation.approve')
            @if ($reservation->status->value === 'pending')
                <x-card title="Keputusan" subtitle="Tindakan ini tercatat di audit log." icon="clipboard" tint :delay="80">
                    <form method="POST" action="{{ route('admin.reservations.approve', $reservation) }}"
                          data-confirm="Setujui reservasi {{ $reservation->code }}? Transaksi peminjaman untuk {{ $reservation->items->count() }} unit akan dibuat.">
                        @csrf
                        <button type="submit" class="btn btn--primary btn--lg btn--block">
                            <x-icon name="check" /> Setujui &amp; Buat Peminjaman
                        </button>
                    </form>

                    <div class="divider--label">atau tolak</div>

                    <form method="POST" action="{{ route('admin.reservations.reject', $reservation) }}"
                          data-confirm="Tolak reservasi {{ $reservation->code }}? Alasan wajib diisi.">
                        @csrf
                        <x-field name="reason" label="Alasan Penolakan" required
                                 hint="Ditampilkan ke pemohon, maksimal 500 karakter.">
                            <x-slot:control>
                                <textarea id="f-reason" name="reason" rows="3" required
                                          placeholder="Contoh: unit sedang dipakai AUTOML Lab"></textarea>
                            </x-slot:control>
                        </x-field>
                        <button type="submit" class="btn btn--danger btn--block" style="margin-top:12px">
                            <x-icon name="x" /> Tolak Reservasi
                        </button>
                    </form>
                </x-card>
            @endif
        @endcan
    </div>
</div>
@endsection
