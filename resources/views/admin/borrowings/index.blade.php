{{-- resources/views/admin/borrowings/index.blade.php — Daftar seluruh transaksi peminjaman --}}
@extends('layouts.app')
@section('title', 'Peminjaman')
@section('chrome', 'Peminjaman')

@section('content')
<x-page-head title="Peminjaman"
             subtitle="Seluruh transaksi aset — dari reservasi disetujui hingga aset kembali.">
    @can('reservation.approve')
        <x-btn :href="route('admin.reservations.index')" variant="ghost" icon="calendar">Reservasi</x-btn>
    @endcan
    @can('checkout.perform')
        <x-btn :href="route('admin.checkout.index')" variant="ghost" icon="out">Antrean Check-out</x-btn>
    @endcan
</x-page-head>

<x-filter-bar :reset="route('admin.borrowings.index')" placeholder="Cari kode peminjaman…">
    <x-slot:controls>
        <x-field name="status" label="Status">
            <x-slot:control>
                <select name="status" data-autosubmit>
                    <option value="">Semua status</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>
    </x-slot:controls>
</x-filter-bar>

{{-- Ringkasan distribusi status (halaman aktif) --}}
@php
    $pageCounts = $borrowings->getCollection()->countBy(fn ($b) => $b->status->value);
    $maxCount = max(1, $pageCounts->max() ?? 1);
@endphp
<x-card title="Ringkasan Status"
        subtitle="Distribusi {{ $borrowings->count() }} transaksi pada halaman {{ $borrowings->currentPage() }} dari {{ number_format($borrowings->total(), 0, ',', '.') }} total."
        icon="chart" :delay="0">
    <div class="grid grid--2">
        @foreach ($statuses as $i => $s)
            <x-metric :label="$s->label()"
                      :value="$pageCounts[$s->value] ?? 0"
                      :max="$maxCount"
                      :tone="match ($s->tone()) { 'brand', 'info' => 'accent', default => $s->tone() }"
                      :delay="$i * 50" />
        @endforeach
    </div>
</x-card>

<x-card flush title="Daftar Peminjaman"
          :subtitle="'Menampilkan ' . number_format($borrowings->count(), 0, ',', '.') . ' dari ' . number_format($borrowings->total(), 0, ',', '.') . ' transaksi'">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Kode &amp; Unit</th>
                    <th scope="col">Peminjam</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="hide-sm">Batas Kembali</th>
                    <th scope="col" class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($borrowings as $b)
                @php
                    $codes = $b->items->map(fn ($i) => $i->asset?->asset_code)->filter()->values();
                    $sisa = $codes->count() - 2;
                    $terlambat = $b->status->value === 'overdue' || $b->isOverdue();
                    $days = $b->due_at ? (int) now()->diffInDays($b->due_at, false) : null;
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.borrowings.show', $b) }}" class="table__code">{{ $b->code }}</a>
                        <span class="table__sub">
                            @if ($codes->isEmpty())
                                <span class="dim">Tanpa unit</span>
                            @else
                                {{ $codes->take(2)->implode(', ') }}
                                @if ($sisa > 0)<span class="badge tone-muted">+{{ $sisa }}</span>@endif
                            @endif
                        </span>
                    </td>
                    <td>
                        <b class="truncate">{{ $b->borrower?->name ?? 'Peminjam dihapus' }}</b>
                        <span class="table__sub">{{ \Illuminate\Support\Str::limit($b->purpose ?: 'Tanpa catatan', 34) }}</span>
                    </td>
                    <td>
                        <div class="btn-row">
                            <x-status :status="$b->status" />
                            @if ($terlambat)<span class="badge tone-bad">Terlambat</span>@endif
                        </div>
                    </td>
                    <td class="hide-sm nowrap">
                        @if ($b->due_at)
                            <span>{{ $b->due_at->format('d M Y H:i') }}</span>
                            <span class="table__sub">
                                @if ($b->status->value === 'returned')
                                    selesai {{ $b->returned_at?->format('d/m/Y') ?? '—' }}
                                @elseif ($days > 0)
                                    sisa {{ $days }} hari
                                @elseif ($days === 0)
                                    jatuh tempo hari ini
                                @else
                                    <span style="color:var(--bad);font-weight:800">terlambat {{ abs($days) }} hari</span>
                                @endif
                            </span>
                        @else
                            <span class="dim">—</span>
                        @endif
                    </td>
                    <td class="col-actions">
                        <div class="btn-row btn-row--end">
                            @if ($b->status->value === 'approved')
                                @can('checkout.perform')
                                    <x-btn :href="route('admin.checkout.show', $b)" size="sm" variant="primary" icon="out"
                                            :aria-label="'Proses check-out ' . $b->code" />
                                @endcan
                            @endif
                            @if (in_array($b->status->value, ['borrowed', 'overdue'], true))
                                @can('checkin.perform')
                                    <x-btn :href="route('admin.checkin.show', $b)" size="sm" variant="primary" icon="in"
                                            :aria-label="'Proses check-in ' . $b->code" />
                                @endcan
                            @endif
                            <x-btn :href="route('admin.borrowings.show', $b)" size="sm" variant="ghost" icon="eye"
                                    :aria-label="'Detail ' . $b->code" />
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        @if (request()->filled('status') || request()->filled('search'))
                            <x-empty icon="search" title="Tidak ada transaksi yang cocok"
                                     text="Ubah kata kunci pencarian atau pilih status lain.">
                                <x-btn :href="route('admin.borrowings.index')" variant="ghost" size="sm">Reset filter</x-btn>
                            </x-empty>
                        @else
                            <x-empty icon="bag" title="Belum ada peminjaman"
                                     text="Transaksi dibuat otomatis saat reservasi disetujui." />
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($borrowings->hasPages())
        <div class="card__foot">{{ $borrowings->links() }}</div>
    @endif
</x-card>
@endsection
