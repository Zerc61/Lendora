{{-- resources/views/admin/locations/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Lokasi')
@section('content')
<div class="card row">
    <h1 style="margin:0">Lokasi</h1>
    @can('location.manage')<a href="{{ route('admin.locations.create') }}">+ Tambah</a>@endcan
</div>
<div class="card">
    <table>
        <tr><th>Nama</th><th>Parent</th><th>Jumlah Aset</th><th>Aksi</th></tr>
        @forelse($locations as $l)
        <tr>
            <td>{{ $l->name }}</td>
            <td class="muted">{{ $l->parent?->name ?? '—' }}</td>
            <td>{{ $l->assets_count }}</td>
            <td>
                <a href="{{ route('admin.locations.edit', $l) }}">Edit</a>
                <form method="POST" action="{{ route('admin.locations.destroy', $l) }}" style="display:inline" onsubmit="return confirm('Hapus lokasi?')">
                    @csrf @method('DELETE')
                    <button style="background:#7f1d1d;padding:5px 10px;font-size:12px">Hapus</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="muted">Belum ada lokasi.</td></tr>
        @endforelse
    </table>
    {{ $locations->links() }}
</div>
@endsection
