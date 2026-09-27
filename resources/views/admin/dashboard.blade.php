{{-- resources/views/admin/dashboard.blade.php — Beranda admin/super-admin --}}
@extends('layouts.admin')
@section('title', 'Dashboard')
@section('chrome', 'Dashboard')

@section('content')
<x-page-head title="Ringkasan Operasional"
             subtitle="Kondisi inventori, antrean kerja, dan kesehatan aset secara real-time.">
    <x-btn href="{{ route('admin.assets.index') }}" variant="ghost" icon="box">Inventori</x-btn>
    <x-btn href="{{ route('admin.reports.index') }}" variant="primary" icon="chart" icon-right="arrow-right">Laporan</x-btn>
</x-page-head>

{{-- KPI utama --}}
<div class="grid grid--stats" style="margin-bottom:18px">
    <x-stat label="Total Aset" :value="$stats['total']" icon="box" tone="brand" :hint="'<b>'.$stats['available'].'</b> tersedia sekarang'"
            :href="route('admin.assets.index')" :delay="0" />
    <x-stat label="Tersedia" :value="$stats['available']" icon="check-circle" tone="ok"
            :hint="'Ketersediaan <b>'.$availabilityRate.'%</b>'" :href="route('admin.assets.index', ['status' => 'available'])" :delay="60" />
    <x-stat label="Sedang Dipinjam" :value="$stats['active']" icon="bag" tone="accent"
            :hint="'<b>'.$stats['reserved'].'</b> sedang direservasi'" :href="route('admin.borrowings.index')" :delay="120" />
    <x-stat label="Terlambat" :value="$stats['overdue']" icon="clock" :tone="$stats['overdue'] > 0 ? 'bad' : 'muted'"
            :hint="'Dari <b>'.$stats['active'].'</b> pinjaman aktif'" :href="route('admin.borrowings.index', ['status' => 'overdue'])" :delay="180" />
    <x-stat label="Issue Terbuka" :value="$stats['issues']" icon="alert" :tone="$stats['issues'] > 0 ? 'warn' : 'muted'"
            :href="route('admin.issues.index')" :delay="240" />
    <x-stat label="Tiket Maintenance" :value="$stats['tickets']" icon="wrench" tone="orange"
            :href="route('admin.tickets.index')" :delay="300" />
</div>

<div class="grid grid--main">
    {{-- Kolom kiri: tren + distribusi --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Tren Peminjaman" subtitle="6 bulan terakhir — total transaksi check-out" icon="chart" :delay="0">
            <x-slot:actions>
                <span class="badge tone-muted">{{ $trend->sum('total') }} transaksi</span>
            </x-slot:actions>

            @if ($trend->isEmpty())
                <x-empty icon="chart" title="Belum ada riwayat peminjaman" text="Data tren akan muncul setelah transaksi check-out pertama." />
            @else
                @php $maxTrend = max(1, $trend->max('total')); @endphp
                <div class="spark">
                    @foreach ($trend as $i => $row)
                        <div class="spark__col {{ $row['total'] === 0 ? 'is-empty' : '' }}">
                            <div class="spark__bar" style="height:{{ max(4, round($row['total'] / $maxTrend * 100)) }}%;--d:{{ $i * 70 }}ms"
                                 data-value="{{ $row['total'] }}"></div>
                            <span class="spark__label">{{ $row['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card title="Distribusi Status Aset" subtitle="Kondisi setiap aset saat ini" icon="layers" :delay="80">
            <x-slot:actions>
                <a class="btn btn--ghost btn--sm" href="{{ route('admin.assets.index') }}">Kelola</a>
            </x-slot:actions>
            @php $maxStatus = max(1, $statusDistribution->max('count')); @endphp
            <div class="list">
                @foreach ($statusDistribution as $i => $row)
                    <div class="bar-row" style="--d:{{ $i * 50 }}ms">
                        <span class="bar-row__label"><x-status :status="$row['status']" /></span>
                        <span class="meter meter--{{ $row['status']->tone() === 'brand' ? 'accent' : $row['status']->tone() }}">
                            <span class="meter__fill" style="--w:{{ round($row['count'] / $maxStatus * 100) }}%;--d:{{ 200 + $i * 50 }}ms"></span>
                        </span>
                        <span class="bar-row__value">{{ number_format($row['count'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Aset Terpopuler" subtitle="Peringkat berdasarkan jumlah peminjaman" icon="sparkle" :delay="120">
            @forelse ($topAssets as $i => $asset)
                <a class="list__row list__row--link" href="{{ route('admin.assets.show', $asset) }}" style="--d:{{ $i * 50 }}ms;padding:9px 8px;margin:0 -8px;border-radius:var(--r-sm)">
                    <span class="list__main">
                        <b>{{ $asset->assetType->name }}</b>
                        <span class="table__code">{{ $asset->asset_code }}</span>
                    </span>
                    <span class="list__side">
                        <span class="badge tone-brand">{{ $asset->borrowing_items_count }}×</span>
                        <span class="tiny dim" style="display:block">dipinjam</span>
                    </span>
                    <x-icon name="chevron-right" style="width:15px;height:15px;color:var(--dim)" />
                </a>
            @empty
                <x-empty icon="box" title="Belum ada data peminjaman" />
            @endforelse
        </x-card>
    </div>

    {{-- Kolom kanan: kesehatan + antrean + aktivitas --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Kesehatan Aset" icon="shield" tint :delay="0">
            <div class="health-row">
                <div class="donut">
                    <svg viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="42" stroke="var(--surface-3)"></circle>
                        <circle cx="50" cy="50" r="42" stroke="url(#healthGrad)" stroke-dasharray="264"
                                stroke-dashoffset="{{ round(264 - 264 * $healthScore / 100) }}"></circle>
                        <defs>
                            <linearGradient id="healthGrad" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#34D399"/><stop offset="1" stop-color="#22D3EE"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <span class="donut__center">
                        <b>{{ $healthScore }}</b>
                        <span>Skor</span>
                    </span>
                </div>
                <div class="stack health-row__stats">
                    <div>
                        <div class="tiny dim">Rata-rata kondisi</div>
                        <b class="tnum">{{ $averageCondition }}<span class="dim" style="font-size:.8rem">/100</span></b>
                    </div>
                    <x-metric label="Rusak / Hilang" :value="$stats['unhealthy']" :max="$stats['total']" tone="bad" />
                </div>
            </div>
            <div class="divider"></div>
            @php $maxCond = max(1, $conditionDistribution->max('count')); @endphp
            @foreach ($conditionDistribution as $i => $row)
                <div class="bar-row" style="--d:{{ $i * 40 }}ms">
                    <span class="bar-row__label"><x-status :status="$row['condition']" /></span>
                    <span class="meter meter--{{ $row['condition']->tone() }}">
                        <span class="meter__fill" style="--w:{{ round($row['count'] / $maxCond * 100) }}%;--d:{{ 150 + $i * 40 }}ms"></span>
                    </span>
                    <span class="bar-row__value">{{ number_format($row['count'], 0, ',', '.') }}</span>
                </div>
            @endforeach
        </x-card>

        <x-card title="Antrean Perlu Tindakan" icon="clock" :delay="80">
            <div class="list">
                <a class="list__row list__row--link" href="{{ route('admin.reservations.index', ['status' => 'pending']) }}">
                    <span class="list__main"><b>Reservasi menunggu</b><span>Perlu disetujui atau ditolak</span></span>
                    <span class="list__side"><b class="tnum">{{ $stats['reservations'] }}</b></span>
                </a>
                <a class="list__row list__row--link" href="{{ route('admin.checkout.index') }}">
                    <span class="list__main"><b>Siap check-out</b><span>Sudah disetujui, belum diserahkan</span></span>
                    <span class="list__side"><b class="tnum">{{ $stats['pending'] }}</b></span>
                </a>
                <a class="list__row list__row--link" href="{{ route('admin.checkin.index') }}">
                    <span class="list__main"><b>Belum dikembalikan</b><span>Termasuk yang terlambat</span></span>
                    <span class="list__side"><b class="tnum">{{ $stats['active'] }}</b></span>
                </a>
            </div>
        </x-card>

        <x-card title="Issue Terbaru" icon="alert" :delay="120">
            <x-slot:actions><a class="btn btn--ghost btn--sm" href="{{ route('admin.issues.index') }}">Semua</a></x-slot:actions>
            @forelse ($recentIssues as $issue)
                <a class="list__row list__row--link" href="{{ route('admin.issues.show', $issue) }}" style="--d:{{ $loop->index * 40 }}ms">
                    <span class="list__main">
                        <b class="truncate">{{ $issue->asset?->asset_code ?? '—' }} · {{ \Illuminate\Support\Str::limit($issue->type->label(), 24) }}</b>
                        <span>{{ $issue->code }} · {{ $issue->created_at->diffForHumans() }}</span>
                    </span>
                    <span class="list__side"><x-status :status="$issue->status" /></span>
                </a>
            @empty
                <x-empty icon="check-circle" title="Tidak ada issue terbuka" text="Semua aset dalam kondisi baik." />
            @endforelse
        </x-card>

        <x-card title="Maintenance Terbaru" icon="wrench" :delay="160">
            <x-slot:actions><a class="btn btn--ghost btn--sm" href="{{ route('admin.tickets.index') }}">Semua</a></x-slot:actions>
            @forelse ($recentTickets as $ticket)
                <a class="list__row list__row--link" href="{{ route('admin.tickets.show', $ticket) }}" style="--d:{{ $loop->index * 40 }}ms">
                    <span class="list__main">
                        <b class="truncate">{{ $ticket->code }} · {{ \Illuminate\Support\Str::limit($ticket->type->label(), 22) }}</b>
                        <span>{{ $ticket->asset?->asset_code ?? '—' }} · {{ $ticket->created_at->diffForHumans() }}</span>
                    </span>
                    <span class="list__side"><x-status :status="$ticket->status" /></span>
                </a>
            @empty
                <x-empty icon="check-circle" title="Tidak ada tiket maintenance" />
            @endforelse
        </x-card>
    </div>
</div>
@endsection
