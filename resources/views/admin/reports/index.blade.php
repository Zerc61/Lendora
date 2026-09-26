{{-- resources/views/admin/reports/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Laporan')
@section('content')
<h1>Laporan & Analitik</h1>

{{-- Filter periode/user/asset (PDF 4M) --}}
<div class="card">
    <form method="GET">
        <div class="row">
            <div style="flex:1"><label>Dari</label><input type="date" name="from" value="{{ request('from', $from->format('Y-m-d')) }}"></div>
            <div style="flex:1"><label>Sampai</label><input type="date" name="to" value="{{ request('to', $to->format('Y-m-d')) }}"></div>
            <div style="flex:1">
                <label>Peminjam</label>
                <select name="user_id">
                    <option value="">Semua</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1">
                <label>Unit Aset</label>
                <select name="asset_id">
                    <option value="">Semua</option>
                    @foreach($assets as $a)
                    <option value="{{ $a->id }}" {{ request('asset_id') == $a->id ? 'selected' : '' }}>{{ $a->asset_code }}</option>
                    @endforeach
                </select>
            </div>
            <div style="align-self:flex-end">
                <button>Terapkan</button>
                <a href="{{ route('admin.reports.index') }}">Reset</a>
            </div>
        </div>
    </form>
</div>

{{-- Export CSV (PDF 4M) --}}
<div class="card row">
    <h3 style="margin:0">⬇️ Export CSV (periode aktif)</h3>
    <div style="display:flex;gap:8px">
        <a href="{{ route('admin.reports.export', ['type' => 'borrowings'] + request()->query()) }}">Peminjaman</a>
        <a href="{{ route('admin.reports.export', ['type' => 'utilization'] + request()->query()) }}">Utilisasi</a>
        <a href="{{ route('admin.reports.export', ['type' => 'maintenance-cost'] + request()->query()) }}">Biaya Maintenance</a>
    </div>
</div>

{{-- Ringkasan --}}
<div class="card">
    <h3 style="margin-top:0">Ringkasan Periode: {{ $from->format('d M Y') }} → {{ $to->format('d M Y') }}</h3>
    <table>
        @foreach($summary as $label => $value)
        <tr><th style="width:60%">{{ ucwords(str_replace('_', ' ', $label)) }}</th><td><strong>{{ $value }}</strong></td></tr>
        @endforeach
    </table>
</div>

{{-- Detail peminjaman --}}
<div class="card">
    <h3 style="margin-top:0">Detail Peminjaman</h3>
    <table>
        <tr><th>Kode</th><th>Peminjam</th><th>Status</th><th>Check-out</th><th>Dikembalikan</th></tr>
        @forelse($borrowings as $b)
        <tr>
            <td><strong>{{ $b->code }}</strong></td>
            <td>{{ $b->borrower->name }}</td>
            <td><span class="badge b-{{ $b->status->value }}">{{ $b->status->value }}</span></td>
            <td>{{ $b->checked_out_at?->format('d M Y H:i') ?? '—' }}</td>
            <td>{{ $b->returned_at?->format('d M Y H:i') ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="muted">Tidak ada peminjaman pada periode/filter ini.</td></tr>
        @endforelse
    </table>
    {{ $borrowings->links() }}
</div>

{{-- Utilisasi aset --}}
<div class="card">
    <h3 style="margin-top:0">Utilisasi Aset <span class="muted" style="font-size:13px">(waktu keluar ÷ panjang periode)</span></h3>
    <table>
        <tr><th>Aset</th><th>Tipe</th><th>Peminjaman</th><th>Total Hari</th><th>Utilisasi</th></tr>
        @forelse($utilization as $row)
        <tr>
            <td><strong>{{ $row->asset_code }}</strong></td>
            <td class="muted">{{ $row->type_name }}</td>
            <td>{{ $row->borrow_count }}x</td>
            <td>{{ $row->days_out }}</td>
            <td style="min-width:120px">
                <div style="background:#0B0E14;border:1px solid #252B38;border-radius:6px;height:12px">
                    <div style="height:100%;border-radius:6px;background:{{ $row->utilization_pct >= 70 ? '#22c55e' : ($row->utilization_pct >= 30 ? '#eab308' : '#8B93A7') }};width:{{ $row->utilization_pct }}%"></div>
                </div>
                <small class="muted">{{ $row->utilization_pct }}%</small>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="muted">Belum ada data utilisasi.</td></tr>
        @endforelse
    </table>
</div>

{{-- Biaya maintenance --}}
<div class="card">
    <h3 style="margin-top:0">Biaya Maintenance per Aset</h3>
    <table>
        <tr><th>Aset</th><th>Tipe</th><th>Jumlah Tiket</th><th>Total Biaya</th></tr>
        @forelse($maintenanceCosts as $row)
        <tr>
            <td><strong>{{ $row->asset_code }}</strong></td>
            <td class="muted">{{ $row->type_name }}</td>
            <td>{{ $row->ticket_count }}</td>
            <td>Rp {{ number_format($row->total_cost, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="muted">Belum ada biaya maintenance pada periode ini.</td></tr>
        @endforelse
    </table>
</div>
@endsection
