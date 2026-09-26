{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<h1>Dashboard</h1>
<p class="muted">Halo, {{ $user->name }} — role: {{ $user->getRoleNames()->implode(', ') ?: '—' }}</p>

<div class="card">
    <table>
        @foreach($stats as $label => $value)
        <tr><th style="width:60%">{{ $label }}</th><td><strong>{{ $value }}</strong></td></tr>
        @endforeach
    </table>
</div>

@can('report.view')
<div class="card row">
    <h3 style="margin:0">📈 Laporan Operasional</h3>
    <a href="{{ route('admin.reports.index') }}">Buka Halaman Laporan & Export CSV</a>
</div>
@endcan

<div class="card">
    <h3 style="margin-top:0">Distribusi Status Aset</h3>
    @php($maxStatus = max(1, $statusDistribution->max('count')))
    @foreach($statusDistribution as $row)
    <div class="row" style="margin-bottom:6px">
        <div style="width:140px;font-size:13px">{{ $row['status']->label() }}</div>
        <div style="flex:1;background:#0B0E14;border:1px solid #252B38;border-radius:6px;height:14px">
            <div style="height:100%;border-radius:6px;background:#7C5CFC;width:{{ round($row['count'] / $maxStatus * 100) }}%"></div>
        </div>
        <div style="width:34px;text-align:right">{{ $row['count'] }}</div>
    </div>
    @endforeach
</div>

<div class="card">
    <h3 style="margin-top:0">Tren Peminjaman (6 bulan terakhir)</h3>
    @if($trend->isEmpty())
        <p class="muted">Belum ada data check-out.</p>
    @else
        @php($maxTrend = max(1, $trend->max('total')))
        @foreach($trend as $row)
        <div class="row" style="margin-bottom:6px">
            <div style="width:80px;font-size:13px">{{ $row->month }}</div>
            <div style="flex:1;background:#0B0E14;border:1px solid #252B38;border-radius:6px;height:14px">
                <div style="height:100%;border-radius:6px;background:#22D3EE;width:{{ round($row->total / $maxTrend * 100) }}%"></div>
            </div>
            <div style="width:34px;text-align:right">{{ $row->total }}</div>
        </div>
        @endforeach
    @endif
</div>

<div class="card">
    <h3 style="margin-top:0">Aset Terpopuler <span class="muted" style="font-size:13px">(berdasarkan jumlah peminjaman)</span></h3>
    @forelse($topAssets as $a)
    <div class="row" style="border-bottom:1px solid #252B38;padding:5px 0">
        <a href="{{ route('admin.assets.show', $a) }}"><strong>{{ $a->asset_code }}</strong> — {{ $a->assetType->name }}</a>
        <span>{{ $a->borrowing_items_count }}x dipinjam</span>
    </div>
    @empty
    <p class="muted">Belum ada data peminjaman.</p>
    @endforelse
</div>
@endsection
