{{-- resources/views/borrower/reservations/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Reservasi Saya')
@section('content')
<div class="card row">
    <h1 style="margin:0">Reservasi Saya</h1>
    <a href="{{ route('my.reservations.create') }}">+ Ajukan Reservasi</a>
</div>
<div class="card">
    <table>
        <tr><th>Kode</th><th>Rentang Waktu</th><th>Tujuan</th><th>Status</th><th>Aksi</th></tr>
        @forelse($reservations as $r)
        <tr>
            <td><strong>{{ $r->code }}</strong></td>
            <td>{{ $r->start_at->format('d M Y H:i') }} → {{ $r->end_at->format('d M Y H:i') }}</td>
            <td class="muted">{{ Str::limit($r->purpose, 40) }}</td>
            <td><span class="badge b-{{ $r->status->value }}">{{ $r->status->value }}</span></td>
            <td>
                <a href="{{ route('my.reservations.show', $r) }}">Detail</a>
                @if(in_array($r->status->value, ['pending', 'approved']))
                <form method="POST" action="{{ route('my.reservations.cancel', $r) }}" style="display:inline" onsubmit="return confirm('Batalkan reservasi ini?')">
                    @csrf
                    <button style="background:#7f1d1d;padding:5px 10px;font-size:12px">Batalkan</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="muted">Belum ada reservasi. <a href="{{ route('my.reservations.create') }}">Ajukan sekarang</a>.</td></tr>
        @endforelse
    </table>
    {{ $reservations->links() }}
</div>
@endsection
