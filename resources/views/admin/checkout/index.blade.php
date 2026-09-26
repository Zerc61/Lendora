{{-- resources/views/admin/checkout/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Check-out')
@section('content')
<h1>Antrean Check-out <span class="muted" style="font-size:14px">(disetujui, menunggu serah-terima)</span></h1>
<div class="card">
    <table>
        <tr><th>Kode</th><th>Peminjam</th><th>Batas Kembali</th><th>Unit</th><th>Aksi</th></tr>
        @forelse($borrowings as $b)
        <tr>
            <td><strong>{{ $b->code }}</strong></td>
            <td>{{ $b->borrower->name }}</td>
            <td>{{ $b->due_at?->format('d M Y H:i') ?? '—' }}</td>
            <td>{{ $b->items->pluck('asset.asset_code')->implode(', ') }}</td>
            <td><a href="{{ route('admin.checkout.show', $b) }}">Proses Check-out</a></td>
        </tr>
        @empty
        <tr><td colspan="5" class="muted">Tidak ada transaksi menunggu checkout.</td></tr>
        @endforelse
    </table>
    {{ $borrowings->links() }}
</div>
@endsection
