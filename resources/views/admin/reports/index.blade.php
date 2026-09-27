{{-- resources/views/admin/reports/index.blade.php — laporan & analitik (PDF 4M) --}}
@extends('layouts.app')

@section('title', 'Laporan')
@section('chrome', 'Laporan')

@section('content')
@php
    $periodDays = max(1, (int) abs($from->diffInDays($to)) + 1);

    $monthly = $borrowings->getCollection()
        ->filter(fn ($b) => $b->created_at !== null)
        ->groupBy(fn ($b) => $b->created_at->format('Y-m'))
        ->sortKeys()
        ->map(fn ($rows, $key) => [
            'count' => $rows->count(),
            'label' => \Illuminate\Support\Carbon::createFromFormat('Y-m', $key)->translatedFormat('M y'),
        ]);
    $monthlyMax = max(1, (int) $monthly->max('count'));

    $costByType = $maintenanceCosts
        ->groupBy('type_name')
        ->map(fn ($rows, $typeName) => [
            'type_name' => $typeName,
            'total_cost' => (float) $rows->sum('total_cost'),
            'ticket_count' => (int) $rows->sum('ticket_count'),
        ])
        ->sortByDesc('total_cost')
        ->values();
    $costMax = max(1, (float) $costByType->max('total_cost'));

    $query = request()->query();
@endphp

<x-page-head title="Laporan & Analitik"
             :subtitle="'Periode ' . $from->format('d M Y') . ' → ' . $to->format('d M Y') . ' · ' . $periodDays . ' hari. Data mengikuti filter peminjam dan unit aset di bawah.'" />

{{-- Filter periode + export CSV --}}
<form method="GET" action="{{ route('admin.reports.index') }}" class="card">
    <div class="filters">
        <x-field name="from" label="Dari">
            <x-slot:control>
                <input type="date" name="from" value="{{ request('from', $from->format('Y-m-d')) }}">
            </x-slot:control>
        </x-field>

        <x-field name="to" label="Sampai">
            <x-slot:control>
                <input type="date" name="to" value="{{ request('to', $to->format('Y-m-d')) }}">
            </x-slot:control>
        </x-field>

        <x-field name="user_id" label="Peminjam">
            <x-slot:control>
                <select name="user_id" data-autosubmit>
                    <option value="">Semua peminjam</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((string) request('user_id') === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="asset_id" label="Unit Aset">
            <x-slot:control>
                <select name="asset_id" data-autosubmit>
                    <option value="">Semua unit</option>
                    @foreach ($assets as $a)
                        <option value="{{ $a->id }}" @selected((string) request('asset_id') === (string) $a->id)>
                            {{ $a->asset_code }}
                        </option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <div class="filters__actions">
            <button type="submit" class="btn btn--primary"><x-icon name="filter" /> Terapkan</button>
            <x-btn :href="route('admin.reports.index')" variant="ghost">Reset</x-btn>
        </div>
    </div>

    <div class="card__foot">
        <div class="btn-row btn-row--between">
            <span class="tiny dim">Export CSV memakai periode &amp; filter yang sedang aktif.</span>
            <div class="btn-row">
                <x-btn :href="route('admin.reports.export', ['type' => 'borrowings'] + $query)"
                       size="sm" variant="ghost" icon="download">Peminjaman</x-btn>
                <x-btn :href="route('admin.reports.export', ['type' => 'utilization'] + $query)"
                       size="sm" variant="ghost" icon="download">Utilisasi</x-btn>
                <x-btn :href="route('admin.reports.export', ['type' => 'maintenance-cost'] + $query)"
                       size="sm" variant="ghost" icon="download">Biaya Maintenance</x-btn>
            </div>
        </div>
    </div>
</form>

{{-- KPI periode --}}
<div class="grid grid--stats" style="margin-bottom:18px">
    <x-stat label="Total Pengajuan" :value="$summary['total_pengajuan']" icon="bag" tone="brand"
            :hint="'Dari <b>' . $summary['peminjam_unik'] . '</b> peminjam'" :delay="0" />
    <x-stat label="Disetujui" :value="$summary['disetujui']" icon="check-circle" tone="ok"
            :hint="'Termasuk <b>' . $summary['selesai_kembali'] . '</b> selesai kembali'" :delay="60" />
    <x-stat label="Masih Berjalan" :value="$summary['masih_berjalan']" icon="clock" tone="accent"
            hint="Dipinjam dan belum kembali" :delay="120" />
    <x-stat label="Terlambat" :value="$summary['terlambat_sekarang']" icon="alert"
            :tone="$summary['terlambat_sekarang'] > 0 ? 'bad' : 'muted'"
            :hint="'Dari <b>' . $summary['masih_berjalan'] . '</b> berjalan'" :delay="180" />
    <x-stat label="Ditolak" :value="$summary['ditolak']" icon="x" tone="muted"
            hint="Pengajuan yang tidak disetujui" :delay="240" />
</div>

<div class="grid grid--main">
    {{-- Kolom kiri: tren, utilisasi, detail --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Tren Peminjaman Bulanan" icon="chart" :delay="0"
                subtitle="Jumlah pengajuan pada halaman ini, dikelompokkan per bulan dibuat.">
            <x-slot:actions>
                <span class="badge tone-muted">{{ $borrowings->total() }} transaksi</span>
            </x-slot:actions>

            @if ($monthly->isEmpty())
                <x-empty icon="chart" title="Belum ada transaksi" text="Tren muncul setelah ada pengajuan pada periode ini." />
            @else
                <div class="spark">
                    @foreach ($monthly as $month)
                        <div class="spark__col">
                            <div class="spark__bar"
                                 style="height:{{ max(4, round($month['count'] / $monthlyMax * 100)) }}%;--d:{{ $loop->index * 70 }}ms"
                                 data-value="{{ $month['count'] }}"></div>
                            <span class="spark__label">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card title="Utilisasi Aset" icon="chart" :delay="60"
                subtitle="Waktu unit keluar dibagi panjang periode ({{ $periodDays }} hari).">
            <x-slot:actions>
                <span class="badge tone-muted">{{ $utilization->count() }} unit</span>
            </x-slot:actions>

            @forelse ($utilization->take(10) as $row)
                <div class="bar-row" style="--d:{{ $loop->index * 50 }}ms">
                    <span class="bar-row__label">
                        <span class="table__code">{{ $row->asset_code }}</span>
                        <span class="dim"> · {{ $row->type_name }}</span>
                    </span>
                    <span class="meter meter--{{ $row->utilization_pct >= 70 ? 'ok' : ($row->utilization_pct >= 30 ? 'warn' : 'accent') }}">
                        <span class="meter__fill" style="--w:{{ $row->utilization_pct }}%;--d:{{ 150 + $loop->index * 50 }}ms"></span>
                    </span>
                    <span class="bar-row__value">{{ $row->utilization_pct }}%</span>
                </div>
            @empty
                <x-empty icon="box" title="Belum ada data utilisasi" text="Tidak ada unit yang keluar pada periode ini." />
            @endforelse

            @if ($utilization->count() > 10)
                <p class="field__hint" style="margin-top:10px">
                    10 unit teratas dari {{ $utilization->count() }} unit. Rincian lengkap tersedia pada ekspor CSV Utilisasi.
                </p>
            @endif
        </x-card>

        <x-card flush icon="bag" title="Detail Peminjaman"
                subtitle="Transaksi terbaru pada periode dan filter aktif." :delay="120">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Kode</th>
                            <th scope="col" class="hide-sm">Peminjam</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="hide-sm">Check-out</th>
                            <th scope="col" class="hide-sm">Dikembalikan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($borrowings as $b)
                            <tr>
                                <td><span class="table__code">{{ $b->code }}</span></td>
                                <td class="hide-sm">
                                    <div class="cell-media">
                                        <span class="avatar avatar--plain">{{ $b->borrower?->initials() ?? '?' }}</span>
                                        <div class="cell-media__body"><b>{{ $b->borrower?->name ?? 'Peminjam dihapus' }}</b></div>
                                    </div>
                                </td>
                                <td><x-status :status="$b->status" /></td>
                                <td class="hide-sm nowrap muted">{{ $b->checked_out_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="hide-sm nowrap muted">{{ $b->returned_at?->format('d M Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty icon="bag" title="Tidak ada peminjaman"
                                             text="Belum ada transaksi pada periode atau filter yang dipilih." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card__foot">{{ $borrowings->links() }}</div>
        </x-card>
    </div>

    {{-- Kolom kanan: rincian + biaya maintenance --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Rincian Peminjaman" subtitle="Porsi dari total pengajuan" icon="clipboard" :delay="0">
            <x-metric label="Disetujui" :value="$summary['disetujui']" :max="$summary['total_pengajuan']" tone="ok" />
            <x-metric label="Selesai dikembalikan" :value="$summary['selesai_kembali']" :max="$summary['total_pengajuan']" tone="ok" :delay="60" />
            <x-metric label="Masih berjalan" :value="$summary['masih_berjalan']" :max="$summary['total_pengajuan']" tone="brand" :delay="120" />
            <x-metric label="Terlambat" :value="$summary['terlambat_sekarang']" :max="$summary['total_pengajuan']" tone="bad" :delay="180" />
            <x-metric label="Ditolak" :value="$summary['ditolak']" :max="$summary['total_pengajuan']" tone="bad" :delay="240" />
            <p class="field__hint" style="margin-top:10px">
                <b class="tnum">{{ $summary['peminjam_unik'] }}</b> peminjam unik pada periode ini.
            </p>
        </x-card>

        <x-card title="Biaya Maintenance" subtitle="Total biaya tiket selesai per tipe aset" icon="wrench" :delay="60">
            @forelse ($costByType as $row)
                <div class="bar-row" style="--d:{{ $loop->index * 50 }}ms">
                    <span class="bar-row__label">
                        {{ $row['type_name'] }}
                        <span class="dim"> · {{ $row['ticket_count'] }} tiket</span>
                    </span>
                    <span class="meter meter--warn">
                        <span class="meter__fill" style="--w:{{ round($row['total_cost'] / $costMax * 100) }}%;--d:{{ 150 + $loop->index * 50 }}ms"></span>
                    </span>
                    <span class="bar-row__value">Rp {{ number_format($row['total_cost'], 0, ',', '.') }}</span>
                </div>
            @empty
                <x-empty icon="wrench" title="Belum ada biaya" text="Tidak ada tiket maintenance selesai pada periode ini." />
            @endforelse
        </x-card>
    </div>
</div>
@endsection
