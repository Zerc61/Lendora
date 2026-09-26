{{-- resources/views/admin/asset-types/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Tipe Aset')
@section('content')
<div class="card row">
    <h1 style="margin:0">Tipe Aset</h1>
    @can('asset-type.manage')<a href="{{ route('admin.asset-types.create') }}">+ Tambah</a>@endcan
</div>
<div class="card">
    <table>
        <tr><th>Nama</th><th>Kategori</th><th>Brand / Model</th><th>Unit</th><th>Aksi</th></tr>
        @forelse($assetTypes as $t)
        <tr>
            <td>{{ $t->name }}</td>
            <td>{{ $t->category->name }}</td>
            <td class="muted">{{ $t->brand }} {{ $t->model ? '/ '.$t->model : '' }}</td>
            <td>{{ $t->assets_count }}</td>
            <td>
                <a href="{{ route('admin.asset-types.edit', $t) }}">Edit</a>
                <form method="POST" action="{{ route('admin.asset-types.destroy', $t) }}" style="display:inline" onsubmit="return confirm('Hapus tipe aset?')">
                    @csrf @method('DELETE')
                    <button style="background:#7f1d1d;padding:5px 10px;font-size:12px">Hapus</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="muted">Belum ada tipe aset.</td></tr>
        @endforelse
    </table>
    {{ $assetTypes->links() }}
</div>
@endsection
