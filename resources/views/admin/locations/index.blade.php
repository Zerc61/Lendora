{{-- resources/views/admin/locations/index.blade.php — master lokasi aset --}}
@extends('layouts.app')

@section('title', 'Lokasi')
@section('chrome', 'Lokasi')

@section('content')
<x-page-head title="Lokasi"
             subtitle="Lokasi penyimpanan aset, boleh bertingkat: ruang menjadi induk, lemari menjadi sub-lokasi.">
    @can('location.manage')
        <x-btn href="{{ route('admin.locations.create') }}" variant="primary" icon="plus">Tambah Lokasi</x-btn>
    @endcan
</x-page-head>

<x-card flush icon="pin" title="Daftar Lokasi"
        subtitle="Kolom unit menghitung aset yang tercatat pada lokasi tersebut." :delay="0">
    <x-slot:actions>
        <span class="badge tone-muted">{{ number_format($locations->total(), 0, ',', '.') }} lokasi</span>
    </x-slot:actions>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Lokasi</th>
                    <th scope="col" class="hide-sm">Lokasi Induk</th>
                    <th scope="col" class="hide-sm num">Sub-lokasi</th>
                    <th scope="col" class="num">Unit Aset</th>
                    <th scope="col" class="col-actions"><span class="hide-sm">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($locations as $location)
                    <tr>
                        <td>
                            <div class="cell-media">
                                <span class="thumb thumb--ph"><x-icon name="pin" /></span>
                                <div class="cell-media__body">
                                    <b>{{ $location->name }}</b>
                                    <span>{{ \Illuminate\Support\Str::limit($location->description ?? '', 46) ?: 'Tanpa deskripsi' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="hide-sm">
                            @if ($location->parent)
                                <span class="badge tone-muted badge--plain">{{ $location->parent->name }}</span>
                            @else
                                <span class="dim">Lokasi akar</span>
                            @endif
                        </td>
                        <td class="hide-sm num tnum">
                            @if ($location->children->isNotEmpty())
                                {{ number_format($location->children->count(), 0, ',', '.') }}
                            @else
                                <span class="dim">—</span>
                            @endif
                        </td>
                        <td class="num tnum">
                            @if ($location->assets_count > 0)
                                <span class="badge tone-accent">{{ number_format($location->assets_count, 0, ',', '.') }}</span>
                            @else
                                <span class="dim">0</span>
                            @endif
                        </td>
                        <td class="col-actions">
                            <div class="btn-row btn-row--end">
                                <x-btn :href="route('admin.locations.edit', $location)" size="sm" variant="ghost" icon="edit" class="btn--icon"
                                       title="Edit {{ $location->name }}" aria-label="Edit {{ $location->name }}" />
                                <form method="POST" action="{{ route('admin.locations.destroy', $location) }}"
                                      data-confirm="Hapus lokasi {{ $location->name }}? Aset yang menunjuk lokasi ini tidak ikut terhapus.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm btn--icon"
                                            title="Hapus {{ $location->name }}" aria-label="Hapus {{ $location->name }}">
                                        <x-icon name="trash" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty icon="pin" title="Belum ada lokasi"
                                     text="Tambahkan lokasi penyimpanan agar aset mudah dicari saat serah-terima.">
                                @can('location.manage')
                                    <x-btn href="{{ route('admin.locations.create') }}" variant="primary" size="sm" icon="plus">Tambah Lokasi</x-btn>
                                @endcan
                            </x-empty>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card__foot">{{ $locations->links() }}</div>
</x-card>
@endsection
