{{-- resources/views/admin/reservations/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Reservasi')
@section('content')
<h1>Reservasi</h1>
<div class="card">
    <form method="GET" class="row">
        <div style="flex:1">
            <label>Status</label>
            <select name="status">
                <option value="">Semua</option>
                @foreach($statuses as $s)
                <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->value }}</option>
                @endforeach
            </select>
        </div>
        <div style="align-self:flex-end"><button>Filter</button> <a href="{{ route('admin.reservations.index') }}">Reset</a></div>
    </form>
</div>
<div class="card">
    <table>
        <tr><th>Kode</th><th>Pemohon</th><th>Rentang</th><th>Unit</th><th>Status</th><th>Aksi</th></tr>
        @forelse($reservations as $r)
        <tr>
            <td><strong>{{ $r->code }}</strong></td>
            <td>{{ $r->user->name }}</td>
            <td>{{ $r->start_at->format('d M H:i') }} → {{ $r->end_at->format('d M H:i') }}</td>
            <td>{{ $r->items->pluck('asset.asset_code')->implode(', ') }}</td>
            <td><span class="badge b-{{ $r->status->value }}">{{ $r->status->value }}</span></td>
            <td><a href="{{ route('admin.reservations.show', $r) }}">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="muted">Belum ada reservasi.</td></tr>
        @endforelse
    </table>
    {{ $reservations->links() }}
</div>
@endsection
