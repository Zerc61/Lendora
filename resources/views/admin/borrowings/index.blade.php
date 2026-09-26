{{-- resources/views/admin/borrowings/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Peminjaman')
@section('content')
<h1>Peminjaman</h1>
<div class="card">
    <form method="GET" class="row">
        <div style="flex:1"><label>Cari kode</label><input name="search" value="{{ request('search') }}"></div>
        <div style="flex:1">
            <label>Status</label>
            <select name="status">
                <option value="">Semua</option>
                @foreach($statuses as $s)
                <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->value }}</option>
                @endforeach
            </select>
        </div>
        <div style="align-self:flex-end"><button>Filter</button> <a href="{{ route('admin.borrowings.index') }}">Reset</a></div>
    </form>
</div>
<div class="card">
    <table>
        <tr><th>Kode</th><th>Peminjam</th><th>Status</th><th>Due</th><th>Unit</th><th>Aksi</th></tr>
        @forelse($borrowings as $b)
        <tr>
            <td><strong>{{ $b->code }}</strong></td>
            <td>{{ $b->borrower->name }}</td>
            <td>
                <span class="badge b-{{ $b->status->value }}">{{ $b->status->value }}</span>
                @if($b->isOverdue())<span class="badge b-overdue">Terlambat</span>@endif
            </td>
            <td>{{ $b->due_at?->format('d M Y') ?? '—' }}</td>
            <td>{{ $b->items->pluck('asset.asset_code')->implode(', ') }}</td>
            <td><a href="{{ route('admin.borrowings.show', $b) }}">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="muted">Belum ada peminjaman.</td></tr>
        @endforelse
    </table>
    {{ $borrowings->links() }}
</div>
@endsection
