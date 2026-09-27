{{-- resources/views/admin/asset-types/index.blade.php — master tipe aset --}}
@extends('layouts.app')

@section('title', 'Tipe Aset')
@section('chrome', 'Tipe Aset')

@section('content')
<x-page-head title="Tipe Aset"
             subtitle="Spesifikasi unit aset: kategori induk, merek, dan model yang dipakai saat registrasi.">
    @can('category.manage')
        <x-btn :href="route('admin.categories.index')" variant="ghost" icon="tag">Kategori</x-btn>
    @endcan
    @can('asset-type.manage')
        <x-btn href="{{ route('admin.asset-types.create') }}" variant="primary" icon="plus">Tambah Tipe</x-btn>
    @endcan
</x-page-head>

<x-card flush icon="layers" title="Daftar Tipe Aset"
        subtitle="Kolom unit menghitung aset yang memakai tipe ini." :delay="0">
    <x-slot:actions>
        <span class="badge tone-muted">{{ number_format($assetTypes->total(), 0, ',', '.') }} tipe</span>
    </x-slot:actions>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Tipe Aset</th>
                    <th scope="col" class="hide-sm">Kategori</th>
                    <th scope="col" class="hide-sm">Merek / Model</th>
                    <th scope="col" class="num">Unit</th>
                    <th scope="col" class="col-actions"><span class="hide-sm">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assetTypes as $assetType)
                    <tr>
                        <td>
                            <div class="cell-media">
                                <span class="thumb thumb--ph"><x-icon name="layers" /></span>
                                <div class="cell-media__body">
                                    <b>{{ $assetType->name }}</b>
                                    <span>{{ \Illuminate\Support\Str::limit($assetType->description ?? '', 46) ?: 'Tanpa deskripsi' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="hide-sm">
                            <span class="badge tone-brand badge--plain">{{ $assetType->category?->name ?? 'Tanpa kategori' }}</span>
                        </td>
                        <td class="hide-sm muted">
                            {{ collect([$assetType->brand, $assetType->model])->filter()->implode(' / ') ?: '—' }}
                        </td>
                        <td class="num tnum">
                            @if ($assetType->assets_count > 0)
                                <span class="badge tone-accent">{{ number_format($assetType->assets_count, 0, ',', '.') }}</span>
                            @else
                                <span class="dim">0</span>
                            @endif
                        </td>
                        <td class="col-actions">
                            <div class="btn-row btn-row--end">
                                <x-btn :href="route('admin.asset-types.edit', $assetType)" size="sm" variant="ghost" icon="edit" class="btn--icon"
                                       title="Edit {{ $assetType->name }}" aria-label="Edit {{ $assetType->name }}" />
                                <form method="POST" action="{{ route('admin.asset-types.destroy', $assetType) }}"
                                      data-confirm="Hapus tipe aset {{ $assetType->name }}? Unit yang sudah terdaftar tidak ikut terhapus.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm btn--icon"
                                            title="Hapus {{ $assetType->name }}" aria-label="Hapus {{ $assetType->name }}">
                                        <x-icon name="trash" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty icon="layers" title="Belum ada tipe aset"
                                     text="Tipe aset wajib ada lebih dulu sebelum unit bisa didaftarkan.">
                                @can('asset-type.manage')
                                    <x-btn href="{{ route('admin.asset-types.create') }}" variant="primary" size="sm" icon="plus">Tambah Tipe</x-btn>
                                @endcan
                            </x-empty>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card__foot">{{ $assetTypes->links() }}</div>
</x-card>
@endsection
