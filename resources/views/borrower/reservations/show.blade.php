{{-- resources/views/borrower/reservations/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Detail Reservasi')
@section('content')
<div class="card row">
    <h1 style="margin:0">{{ $reservation->code }}</h1>
    <span class="badge b-{{ $reservation->status->value }}">{{ $reservation->status->value }}</span>
</div>
<div class="card">
    <table>
        <tr><th style="width:35%">Pemohon</th><td>{{ $reservation->user->name }}</td></tr>
        <tr><th>Rentang Waktu</th><td>{{ $reservation->start_at->format('d M Y H:i') }} → {{ $reservation->end_at->format('d M Y H:i') }}</td></tr>
        <tr><th>Tujuan</th><td>{{ $reservation->purpose }}</td></tr>
        <tr><th>Diputuskan oleh</th><td>{{ $reservation->approvedBy?->name ?? '—' }}</td></tr>
        @if($reservation->rejection_reason)
        <tr><th>Alasan penolakan</th><td>{{ $reservation->rejection_reason }}</td></tr>
        @endif
    </table>
</div>
<div class="card">
    <h3 style="margin-top:0">Unit yang Diajukan</h3>
    <table>
        <tr><th>Kode</th><th>Tipe</th><th>Status Unit</th></tr>
        @foreach($reservation->items as $item)
        <tr>
            <td><strong>{{ $item->asset->asset_code }}</strong></td>
            <td>{{ $item->asset->assetType->name }}</td>
            <td><span class="badge b-{{ $item->asset->status->value }}">{{ $item->asset->status->label() }}</span></td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
