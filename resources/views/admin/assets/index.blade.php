{{-- resources/views/admin/assets/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Aset')
@section('content')
<div class="card row">
    <h1 style="margin:0">Aset</h1>
    @can('create', App\Models\Asset::class)
        <a href="{{ route('admin.assets.create') }}">+ Tambah Aset</a>
    @endcan
</div>

{{-- Filter --}}
<div class="card">
    <form method="GET" class="row">
        <div style="flex:2">
            <label>Cari (kode / serial / tipe)</label>
            <input name="search" value="{{ request('search') }}">
        </div>
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
            <label>Kategori</label>
            <select name="category_id">
                <option value="">Semua</option>
                @foreach($categories as $c)
                <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="align-self:flex-end">
            <button>Filter</button>
            <a href="{{ route('admin.assets.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <table>
        <tr><th>Kode</th><th>Tipe</th><th>Serial</th><th>Status</th><th>Kondisi</th><th>Lokasi</th><th>Aksi</th></tr>
        @forelse($assets as $a)
        <tr>
            <td><strong>{{ $a->asset_code }}</strong></td>
            <td>{{ $a->assetType->name }}</td>
            <td class="muted">{{ $a->serial_number ?? '—' }}</td>
            <td><span class="badge b-{{ $a->status->value }}">{{ $a->status->label() }}</span></td>
            <td>{{ $a->condition->value }}</td>
            <td>{{ $a->location?->name ?? '—' }}</td>
            <td><a href="{{ route('admin.assets.show', $a) }}">Detail</a></td>
        </tr>
        @empty
        <tr><td colspan="7" class="muted">Tidak ada aset yang cocok.</td></tr>
        @endforelse
    </table>
    {{ $assets->links() }}
</div>
@endsection
