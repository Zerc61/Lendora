{{-- resources/views/admin/tickets/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Detail Tiket')
@section('content')
<div class="card row">
    <h1 style="margin:0">{{ $ticket->code }}</h1>
    <div>
        <span class="badge b-{{ $ticket->status->value }}">{{ $ticket->status->label() }}</span>
        @can('update', $ticket)<a href="{{ route('admin.tickets.edit', $ticket) }}">Edit</a>@endcan
    </div>
</div>
<div class="card">
    <table>
        <tr><th style="width:35%">Aset</th><td><a href="{{ route('admin.assets.show', $ticket->asset) }}">{{ $ticket->asset->asset_code }}</a> — {{ $ticket->asset->assetType->name }}</td></tr>
        @if($ticket->issue)
        <tr><th>Issue Terkait</th><td><a href="{{ route('admin.issues.show', $ticket->issue) }}">{{ $ticket->issue->code }}</a></td></tr>
        @endif
        <tr><th>Jenis / Prioritas</th><td>{{ $ticket->type->value }} / {{ $ticket->priority->value }}</td></tr>
        <tr><th>Pelapor</th><td>{{ $ticket->reportedBy->name }}</td></tr>
        <tr><th>Teknisi</th><td>{{ $ticket->technician?->name ?? '—' }}</td></tr>
        <tr><th>Deskripsi</th><td>{{ $ticket->description }}</td></tr>
        @if($ticket->diagnosis)
        <tr><th>Diagnosis</th><td>{{ $ticket->diagnosis }}</td></tr>
        @endif
        <tr><th>Jadwal / Selesai</th><td>{{ $ticket->scheduled_at?->format('d M Y') ?? '—' }} / {{ $ticket->completed_at?->format('d M Y H:i') ?? '—' }}</td></tr>
        <tr><th>Total Biaya</th><td><strong>Rp {{ number_format($totalCost, 0, ',', '.') }}</strong></td></tr>
    </table>
</div>

{{-- Workflow buttons --}}
<div class="card">
    <h3 style="margin-top:0">⚙️ Aksi Workflow</h3>
    @can('maintenance.create')
        @if($ticket->status->value === 'open')
        <form method="POST" action="{{ route('admin.tickets.transition', $ticket) }}" class="row" style="margin-bottom:10px">
            @csrf
            <input type="hidden" name="action" value="assign">
            <div style="flex:1">
                <label>Tugaskan Teknisi</label>
                <select name="technician_id" required>
                    <option value="">— pilih teknisi —</option>
                    @foreach($technicians as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="align-self:flex-end"><button>👨‍🔧 Assign</button></div>
        </form>
        @endif
        @if($ticket->status->value === 'completed')
        <form method="POST" action="{{ route('admin.tickets.transition', $ticket) }}" class="row" style="margin-bottom:10px">
            @csrf
            <input type="hidden" name="action" value="verify">
            <div style="flex:1">
                <label>Kondisi Akhir Unit (wajib)</label>
                <select name="final_condition" required>
                    @foreach(['excellent','good','fair','poor','broken'] as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div style="align-self:flex-end"><button>✅ Verify → Unit Kembali Available</button></div>
        </form>
        @endif
    @endcan

    @can('work', $ticket)
        @if($ticket->status->value === 'assigned')
        <form method="POST" action="{{ route('admin.tickets.transition', $ticket) }}" style="margin-bottom:10px">
            @csrf <input type="hidden" name="action" value="start">
            <button>▶️ Mulai Kerjakan</button>
        </form>
        @endif
        @if($ticket->status->value === 'in_progress')
        <form method="POST" action="{{ route('admin.tickets.transition', $ticket) }}" style="margin-bottom:10px">
            @csrf <input type="hidden" name="action" value="wait_parts">
            <button>⏸️ Tunggu Sparepart</button>
        </form>
        @endif
        @if(in_array($ticket->status->value, ['in_progress', 'waiting_parts']))
        <form method="POST" action="{{ route('admin.tickets.transition', $ticket) }}" style="margin-bottom:10px">
            @csrf <input type="hidden" name="action" value="complete">
            <label>Diagnosis & Hasil (opsional)</label>
            <textarea name="diagnosis" rows="2"></textarea>
            <button>🏁 Tandai Selesai</button>
        </form>
        @endif
    @endcan

    @can('maintenance.create')
        @if(!in_array($ticket->status->value, ['verified', 'cancelled']))
        <form method="POST" action="{{ route('admin.tickets.transition', $ticket) }}" onsubmit="return confirm('Batalkan tiket ini?')">
            @csrf <input type="hidden" name="action" value="cancel">
            <button style="background:#7f1d1d">❌ Cancel</button>
        </form>
        @endif
    @endcan
    @if($ticket->status->value === 'verified')<p class="muted">✅ Tiket terverifikasi — unit kembali beroperasi.</p>@endif
    @if($ticket->status->value === 'cancelled')<p class="muted">Tiket dibatalkan.</p>@endif
</div>

{{-- Work Log --}}
@can('work', $ticket)
@if(in_array($ticket->status->value, ['assigned', 'in_progress', 'waiting_parts']))
<div class="card">
    <h3 style="margin-top:0">📝 Tambah Work Log</h3>
    <form method="POST" action="{{ route('admin.tickets.logs.store', $ticket) }}">
        @csrf
        <div class="row">
            <div style="flex:2"><label>Pekerjaan</label><input name="action" required placeholder="Ganti lampu proyektor"></div>
            <div style="flex:1"><label>Biaya (Rp)</label><input type="number" step="0.01" min="0" name="cost" value="0"></div>
            <div style="flex:1"><label>Tanggal</label><input type="date" name="performed_at"></div>
        </div>
        <label>Catatan / Sparepart</label>
        <input name="note">
        <button style="margin-top:8px">+ Catat</button>
    </form>
</div>
@endif
@endcan

<div class="card">
    <h3 style="margin-top:0">Riwayat Pekerjaan ({{ $ticket->logs->count() }})</h3>
    @forelse($ticket->logs->sortByDesc('performed_at') as $log)
    <div style="border-bottom:1px solid #252B38;padding:6px 0">
        <strong>{{ $log->action }}</strong>
        <span class="muted">— {{ $log->user?->name ?? 'Sistem' }}, {{ $log->performed_at->format('d M Y H:i') }}</span>
        @if($log->cost > 0)<span class="muted">| Rp {{ number_format($log->cost, 0, ',', '.') }}</span>@endif
        @if($log->note)<div class="muted">{{ $log->note }}</div>@endif
    </div>
    @empty
    <p class="muted">Belum ada riwayat.</p>
    @endforelse
</div>
@endsection
