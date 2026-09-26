{{-- resources/views/admin/checkin/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Check-in')
@section('content')
<h1>Peminjaman Aktif <span class="muted" style="font-size:14px">(menunggu pengembalian)</span></h1>
<div class="card">
    <table>
        <tr><th>Kode</th><th>Peminjam</th><th>Batas Kembali</th><th>Unit</th><th>Aksi</th></tr>
        @forelse($borrowings as $b)
        <tr>
            <td><strong>{{ $b->code }}</strong></td>
            <td>{{ $b->borrower->name }}</td>
            <td>
                {{ $b->due_at?->format('d M Y H:i') ?? '—' }}
                @if($b->isOverdue())<span class="badge b-overdue">Terlambat</span>@endif
            </td>
            <td>{{ $b->items->pluck('asset.asset_code')->implode(', ') }}</td>
            <td><a href="{{ route('admin.checkin.show', $b) }}">Proses Check-in</a></td>
        </tr>
        @empty
        <tr><td colspan="5" class="muted">Tidak ada peminjaman aktif.</td></tr>
        @endforelse
    </table>
    {{ $borrowings->links() }}
</div>
@endsection
