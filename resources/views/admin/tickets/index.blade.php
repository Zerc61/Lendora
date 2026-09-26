{{-- resources/views/admin/tickets/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Maintenance')
@section('content')
<div class="card row">
    <h1 style="margin:0">Tiket Maintenance</h1>
    @can('maintenance.create')<a href="{{ route('admin.tickets.create') }}">+ Buat Tiket</a>@endcan
</div>
<div class="card">
    <form method="GET" class="row">
        <div style="flex:1">
            <label>Status</label>
            <select name="status">
                <option value="">Semua</option>
                @foreach($statuses as $s)
                <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1">
            <label>Teknisi</label>
            <select name="technician_id">
                <option value="">Semua</option>
                @foreach($technicians as $t)
                <option value="{{ $t->id }}" {{ request('technician_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="align-self:flex-end"><button>Filter</button> <a href="{{ route('admin.tickets.index') }}">Reset</a></div>
    </form>
</div>
<div class="card">
    <table>
        <tr><th>Kode</th><th>Aset</th><th>Jenis</th><th>Prioritas</th><th>Status</th><th>Teknisi</th><th>Aksi</th></tr>
        @forelse($tickets as $t)
        <tr>
            <td><strong>{{ $t->code }}</strong></td>
            <td>{{ $t->asset->asset_code }}</td>
            <td>{{ $t->type->value }}</td>
            <td>{{ $t->priority->value }}</td>
            <td><span class="badge b-{{ $t->status->value }}">{{ $t->status->label() }}</span></td>
            <td>{{ $t->technician?->name ?? '—' }}</td>
            <td><a href="{{ route('admin.tickets.show', $t) }}">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="7" class="muted">Belum ada tiket maintenance.</td></tr>
        @endforelse
    </table>
    {{ $tickets->links() }}
</div>
@endsection
