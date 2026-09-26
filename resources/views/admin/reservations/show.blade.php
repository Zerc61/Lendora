{{-- resources/views/admin/reservations/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Detail Reservasi')
@section('content')
<div class="card row">
    <h1 style="margin:0">{{ $reservation->code }}</h1>
    <span class="badge b-{{ $reservation->status->value }}">{{ $reservation->status->value }}</span>
</div>
<div class="card">
    <table>
        <tr><th style="width:35%">Pemohon</th><td>{{ $reservation->user->name }} ({{ $reservation->user->email }})</td></tr>
        <tr><th>Rentang Waktu</th><td>{{ $reservation->start_at->format('d M Y H:i') }} → {{ $reservation->end_at->format('d M Y H:i') }}</td></tr>
        <tr><th>Tujuan</th><td>{{ $reservation->purpose }}</td></tr>
        @if($reservation->approvedBy)
        <tr><th>Diputuskan oleh</th><td>{{ $reservation->approvedBy->name }} pada {{ $reservation->approved_at?->format('d M Y H:i') }}</td></tr>
        @endif
        @if($reservation->rejection_reason)
        <tr><th>Alasan penolakan</th><td>{{ $reservation->rejection_reason }}</td></tr>
        @endif
        @if($reservation->borrowing)
        <tr><th>Transaksi Peminjaman</th><td><a href="{{ route('admin.borrowings.show', $reservation->borrowing) }}">{{ $reservation->borrowing->code }}</a> ({{ $reservation->borrowing->status->value }})</td></tr>
        @endif
    </table>
</div>
<div class="card">
    <h3 style="margin-top:0">Unit Diajukan</h3>
    <table>
        <tr><th>Kode</th><th>Tipe</th><th>Lokasi</th><th>Status Unit</th></tr>
        @foreach($reservation->items as $item)
        <tr>
            <td><strong>{{ $item->asset->asset_code }}</strong></td>
            <td>{{ $item->asset->assetType->name }}</td>
            <td>{{ $item->asset->location?->name ?? '—' }}</td>
            <td><span class="badge b-{{ $item->asset->status->value }}">{{ $item->asset->status->label() }}</span></td>
        </tr>
        @endforeach
    </table>
</div>
@can('approve', $reservation)
@if($reservation->status->value === 'pending')
<div class="card">
    <h3 style="margin-top:0">Keputusan</h3>
    <form method="POST" action="{{ route('admin.reservations.approve', $reservation) }}" style="margin-bottom:14px">
        @csrf
        <button>✅ Setujui & Buat Transaksi Peminjaman</button>
    </form>
    <form method="POST" action="{{ route('admin.reservations.reject', $reservation) }}">
        @csrf
        <label>Alasan Penolakan (wajib)</label>
        <textarea name="reason" rows="2" required></textarea>
        <button style="background:#7f1d1d">❌ Tolak</button>
    </form>
</div>
@endif
@endcan
@endsection
