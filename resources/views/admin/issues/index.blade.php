{{-- resources/views/admin/issues/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Issue')
@section('content')
<h1>Issue & Masalah Aset</h1>
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
            <label>Jenis</label>
            <select name="type">
                <option value="">Semua</option>
                @foreach($types as $t)
                <option value="{{ $t->value }}" {{ request('type') === $t->value ? 'selected' : '' }}>{{ $t->value }}</option>
                @endforeach
            </select>
        </div>
        <div style="align-self:flex-end"><button>Filter</button> <a href="{{ route('admin.issues.index') }}">Reset</a></div>
    </form>
</div>
<div class="card">
    <table>
        <tr><th>Kode</th><th>Aset</th><th>Jenis</th><th>Tingkat</th><th>Status</th><th>Pelapor</th><th>Aksi</th></tr>
        @forelse($issues as $i)
        <tr>
            <td><strong>{{ $i->code }}</strong></td>
            <td>{{ $i->asset?->asset_code ?? '—' }}</td>
            <td>{{ $i->type->value }}</td>
            <td>{{ $i->severity->value }}</td>
            <td><span class="badge b-{{ $i->status->value }}">{{ $i->status->label() }}</span></td>
            <td>{{ $i->reportedBy->name }}</td>
            <td><a href="{{ route('admin.issues.show', $i) }}">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="7" class="muted">Belum ada issue.</td></tr>
        @endforelse
    </table>
    {{ $issues->links() }}
</div>
@endsection
