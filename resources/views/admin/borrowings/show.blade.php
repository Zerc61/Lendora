{{-- resources/views/admin/borrowings/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Detail Peminjaman')
@section('content')
<div class="card row">
    <h1 style="margin:0">{{ $borrowing->code }}</h1>
    <div>
        <span class="badge b-{{ $borrowing->status->value }}">{{ $borrowing->status->value }}</span>
        @if($borrowing->isOverdue())<span class="badge b-overdue">Terlambat</span>@endif
    </div>
</div>
<div class="card">
    <table>
        <tr><th style="width:35%">Peminjam</th><td>{{ $borrowing->borrower->name }} ({{ $borrowing->borrower->email }})</td></tr>
        <tr><th>Reservasi</th><td>{{ $borrowing->reservation?->code ?? '—' }} — {{ $borrowing->purpose }}</td></tr>
        <tr><th>Disetujui oleh</th><td>{{ $borrowing->approvedBy?->name ?? '—' }}</td></tr>
        <tr><th>Check-out</th><td>{{ $borrowing->checked_out_at?->format('d M Y H:i') ?? '—' }} oleh {{ $borrowing->checkedOutBy?->name ?? '—' }}</td></tr>
        <tr><th>Batas Kembali</th><td>{{ $borrowing->due_at?->format('d M Y H:i') ?? '—' }}</td></tr>
        <tr><th>Check-in</th><td>{{ $borrowing->returned_at?->format('d M Y H:i') ?? '—' }} oleh {{ $borrowing->checkedInBy?->name ?? '—' }}</td></tr>
        @if($borrowing->checkin_notes)
        <tr><th>Catatan Check-in</th><td>{{ $borrowing->checkin_notes }}</td></tr>
        @endif
    </table>
</div>
<div class="card">
    <h3 style="margin-top:0">Unit</h3>
    <table>
        <tr><th>Kode</th><th>Kondisi Keluar</th><th>Kondisi Kembali</th><th>Status Unit</th></tr>
        @foreach($borrowing->items as $item)
        <tr>
            <td><strong>{{ $item->asset->asset_code }}</strong> <span class="muted">{{ $item->asset->assetType->name }}</span></td>
            <td>{{ $item->condition_out?->value ?? '—' }} @if($item->notes_out)<span class="muted">({{ $item->notes_out }})</span>@endif</td>
            <td>{{ $item->condition_in?->value ?? '—' }} @if($item->notes_in)<span class="muted">({{ $item->notes_in }})</span>@endif</td>
            <td><span class="badge b-{{ $item->asset->status->value }}">{{ $item->asset->status->label() }}</span></td>
        </tr>
        @endforeach
    </table>
</div>
<div class="card">
    <h3 style="margin-top:0">Riwayat Inspeksi</h3>
    @forelse($borrowing->inspections as $ins)
    <div style="border-bottom:1px solid #252B38;padding:6px 0">
        <strong>{{ $ins->stage->value }}</strong> — {{ $ins->asset->asset_code }} | kondisi: {{ $ins->condition->value }}
        oleh {{ $ins->inspector->name }} pada {{ $ins->inspected_at->format('d M Y H:i') }}
        @if($ins->notes)<div class="muted">{{ $ins->notes }}</div>@endif
    </div>
    @empty
    <p class="muted">Belum ada inspeksi.</p>
    @endforelse
</div>
@if($issues->isNotEmpty())
<div class="card">
    <h3 style="margin-top:0">Issue Terkait</h3>
    @foreach($issues as $issue)
    <div style="border-bottom:1px solid #252B38;padding:6px 0">
        <strong>{{ $issue->code }}</strong> — {{ $issue->type->value }} ({{ $issue->severity->value }})
        <span class="badge b-{{ $issue->status->value }}">{{ $issue->status->value }}</span>
        <div class="muted">{{ $issue->description }}</div>
    </div>
    @endforeach
</div>
@endif
@endsection
