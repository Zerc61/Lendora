{{-- resources/views/admin/categories/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Kategori')
@section('content')
<div class="card row">
    <h1 style="margin:0">Kategori</h1>
    @can('category.manage')<a href="{{ route('admin.categories.create') }}">+ Tambah</a>@endcan
</div>
<div class="card">
    <table>
        <tr><th>Nama</th><th>Deskripsi</th><th>Jumlah Tipe</th><th>Aksi</th></tr>
        @forelse($categories as $c)
        <tr>
            <td>{{ $c->name }}</td>
            <td class="muted">{{ Str::limit($c->description, 50) }}</td>
            <td>{{ $c->asset_types_count }}</td>
            <td>
                <a href="{{ route('admin.categories.edit', $c) }}">Edit</a>
                <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" style="display:inline" onsubmit="return confirm('Hapus kategori?')">
                    @csrf @method('DELETE')
                    <button style="background:#7f1d1d;padding:5px 10px;font-size:12px">Hapus</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="muted">Belum ada kategori.</td></tr>
        @endforelse
    </table>
    {{ $categories->links() }}
</div>
@endsection
