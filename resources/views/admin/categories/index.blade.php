{{-- resources/views/admin/categories/index.blade.php — master kategori aset --}}
@extends('layouts.app')

@section('title', 'Kategori')
@section('chrome', 'Kategori')

@section('content')
<x-page-head title="Kategori"
             subtitle="Kategori induk tipe aset. Dipakai untuk mengelompokkan inventori saat peminjaman.">
    @can('category.manage')
        <x-btn href="{{ route('admin.categories.create') }}" variant="primary" icon="plus">Tambah Kategori</x-btn>
    @endcan
</x-page-head>

<x-card flush icon="tag" title="Daftar Kategori"
        subtitle="Kolom jumlah tipe menunjukkan pemakaian kategori pada inventori." :delay="0">
    <x-slot:actions>
        <span class="badge tone-muted">{{ number_format($categories->total(), 0, ',', '.') }} kategori</span>
    </x-slot:actions>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Kategori</th>
                    <th scope="col" class="hide-sm">Deskripsi</th>
                    <th scope="col" class="num">Tipe Aset</th>
                    <th scope="col" class="col-actions"><span class="hide-sm">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            <div class="cell-media">
                                <span class="thumb thumb--ph"><x-icon name="tag" /></span>
                                <div class="cell-media__body">
                                    <b>{{ $category->name }}</b>
                                    <span>Dibuat {{ $category->created_at->format('d M Y') }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="hide-sm muted">{{ \Illuminate\Support\Str::limit($category->description ?? '', 70) ?: '—' }}</td>
                        <td class="num tnum">
                            @if ($category->asset_types_count > 0)
                                <span class="badge tone-brand">{{ number_format($category->asset_types_count, 0, ',', '.') }}</span>
                            @else
                                <span class="dim">0</span>
                            @endif
                        </td>
                        <td class="col-actions">
                            <div class="btn-row btn-row--end">
                                <x-btn :href="route('admin.categories.edit', $category)" size="sm" variant="ghost" icon="edit" class="btn--icon"
                                       title="Edit {{ $category->name }}" aria-label="Edit {{ $category->name }}" />
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                      data-confirm="Hapus kategori {{ $category->name }}? Tipe aset yang terisi tidak ikut terhapus.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm btn--icon"
                                            title="Hapus {{ $category->name }}" aria-label="Hapus {{ $category->name }}">
                                        <x-icon name="trash" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <x-empty icon="tag" title="Belum ada kategori"
                                     text="Buat kategori dulu agar tipe aset bisa dikelompokkan saat registrasi.">
                                @can('category.manage')
                                    <x-btn href="{{ route('admin.categories.create') }}" variant="primary" size="sm" icon="plus">Tambah Kategori</x-btn>
                                @endcan
                            </x-empty>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card__foot">{{ $categories->links() }}</div>
</x-card>
@endsection
