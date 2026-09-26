{{-- resources/views/admin/issues/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Detail Issue')
@section('content')
<div class="card row">
    <h1 style="margin:0">{{ $issue->code }}</h1>
    <span class="badge b-{{ $issue->status->value }}">{{ $issue->status->label() }}</span>
</div>
<div class="card">
    <table>
        <tr><th style="width:35%">Aset</th><td><a href="{{ route('admin.assets.show', $issue->asset) }}">{{ $issue->asset->asset_code }}</a> — {{ $issue->asset->assetType->name }}</td></tr>
        <tr><th>Jenis / Tingkat</th><td>{{ $issue->type->value }} / {{ $issue->severity->value }}</td></tr>
        <tr><th>Pelapor</th><td>{{ $issue->reportedBy->name }} — {{ $issue->created_at->format('d M Y H:i') }}</td></tr>
        <tr><th>Deskripsi</th><td>{{ $issue->description }}</td></tr>
        @if($issue->borrowing)
        <tr><th>Terkait Peminjaman</th><td><a href="{{ route('admin.borrowings.show', $issue->borrowing) }}">{{ $issue->borrowing->code }}</a></td></tr>
        @endif
        @if($issue->resolution)
        <tr><th>Hasil</th><td>{{ $issue->resolution }}</td></tr>
        <tr><th>Diputuskan oleh</th><td>{{ $issue->resolvedBy?->name ?? '—' }} pada {{ $issue->resolved_at?->format('d M Y H:i') }}</td></tr>
        @endif
    </table>
</div>

@can('transition', $issue)
@if($issue->status->value === 'open')
<div class="card">
    <h3 style="margin-top:0">🔍 Mulai Investigasi</h3>
    <form method="POST" action="{{ route('admin.issues.transition', $issue) }}">
        @csrf
        <input type="hidden" name="action" value="investigate">
        <button>Set Status: Diselidiki</button>
    </form>
</div>
@elseif($issue->status->value === 'investigating')
<div class="card">
    <h3 style="margin-top:0">Keputusan Investigasi</h3>
    <form method="POST" action="{{ route('admin.issues.transition', $issue) }}" style="margin-bottom:14px">
        @csrf
        <input type="hidden" name="action" value="resolve">
        <label>Catatan Hasil (wajib)</label>
        <textarea name="resolution" rows="2" required></textarea>
        @if($issue->type->value === 'loss')
            <label>Hasil Investigasi Alat Hilang (PDF 5.4)</label>
            <label style="display:flex;gap:6px;align-items:center"><input type="radio" name="loss_outcome" value="recovered" style="width:auto" checked> Pulih (recovered) → aset kembali Available</label>
            <label style="display:flex;gap:6px;align-items:center"><input type="radio" name="loss_outcome" value="retired" style="width:auto"> Pensiun (retired) → aset keluar dari siklus</label>
        @endif
        <button>✅ Resolve</button>
    </form>
    <form method="POST" action="{{ route('admin.issues.transition', $issue) }}" style="margin-bottom:14px">
        @csrf
        <input type="hidden" name="action" value="reject">
        <label>Alasan Penolakan (wajib)</label>
        <textarea name="resolution" rows="2" required></textarea>
        <button style="background:#7f1d1d">❌ Reject</button>
    </form>
    <form method="POST" action="{{ route('admin.issues.transition', $issue) }}">
        @csrf
        <input type="hidden" name="action" value="close">
        <label>Catatan Penutupan (wajib)</label>
        <textarea name="resolution" rows="2" required></textarea>
        <button style="background:#334155">📁 Close</button>
    </form>
</div>
@endif
@endcan

@if($issue->type->value === 'damage' && $tickets->isEmpty() && $issue->status->value !== 'resolved')
@can('maintenance.create')
<div class="card">
    <h3 style="margin-top:0">🔧 Tindak Lanjut</h3>
    <a href="{{ route('admin.tickets.create', ['issue' => $issue->id, 'asset' => $issue->asset_id]) }}">Buat Tiket Maintenance dari Issue ini</a>
</div>
@endcan
@endif

@if($tickets->isNotEmpty())
<div class="card">
    <h3 style="margin-top:0">Tiket Maintenance Terkait</h3>
    @foreach($tickets as $t)
    <div style="border-bottom:1px solid #252B38;padding:6px 0">
        <a href="{{ route('admin.tickets.show', $t) }}"><strong>{{ $t->code }}</strong></a>
        — {{ $t->type->value }} | <span class="badge b-{{ $t->status->value }}">{{ $t->status->label() }}</span>
    </div>
    @endforeach
</div>
@endif
@endsection
