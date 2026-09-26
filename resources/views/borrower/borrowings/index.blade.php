{{-- resources/views/borrower/borrowings/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Peminjaman Saya')
@section('content')
<h1>Peminjaman Saya</h1>
<div class="card">
    <table>
        <tr><th>Kode</th><th>Status</th><th>Batas Kembali</th><th>Unit</th><th>Aksi</th></tr>
        @forelse($borrowings as $b)
        <tr>
            <td><strong>{{ $b->code }}</strong></td>
            <td>
                <span class="badge b-{{ $b->status->value }}">{{ $b->status->value }}</span>
                @if($b->isOverdue())<span class="badge b-overdue">Terlambat</span>@endif
            </td>
            <td>{{ $b->due_at?->format('d M Y H:i') ?? '—' }}</td>
            <td>{{ $b->items->pluck('asset.asset_code')->implode(', ') }}</td>
            <td><a href="{{ route('my.borrowings.show', $b) }}">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="5" class="muted">Belum ada peminjaman.</td></tr>
        @endforelse
    </table>
    {{ $borrowings->links() }}
</div>
@endsection
