{{-- resources/views/admin/assets/index.blade.php — Indeks inventori aset --}}
@extends('layouts.app')
@section('title', 'Aset')
@section('chrome', 'Aset')

@section('content')
<x-page-head title="Aset"
             subtitle="Inventori unit: kode, serial, status, kondisi, dan lokasi penyimpanan.">
    @can('create', App\Models\Asset::class)
        <x-btn :href="route('admin.assets.create')" variant="primary" icon="plus">Tambah Aset</x-btn>
    @endcan
</x-page-head>

{{-- Filter: pencarian + status + kategori --}}
<x-filter-bar :reset="route('admin.assets.index')" placeholder="Cari kode, serial, atau tipe aset…">
    <x-slot:controls>
        <x-field name="status" for="filter-status" label="Status">
            <x-slot:control>
                <select id="filter-status" name="status" data-autosubmit>
                    <option value="">Semua status</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="category_id" for="filter-kategori" label="Kategori">
            <x-slot:control>
                <select id="filter-kategori" name="category_id" data-autosubmit>
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>
    </x-slot:controls>
</x-filter-bar>

{{-- Tabel inventori --}}
<x-card title="Daftar Aset" icon="box" flush
        :subtitle="'Urut dari unit yang paling baru ditambahkan.'">
    <x-slot:actions>
        <span class="badge tone-muted">{{ number_format($assets->total(), 0, ',', '.') }} unit</span>
    </x-slot:actions>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Aset</th>
                    <th scope="col" class="hide-sm">Serial</th>
                    <th scope="col">Status</th>
                    <th scope="col">Kondisi</th>
                    <th scope="col" class="hide-sm">Lokasi</th>
                    <th scope="col" class="col-actions"><span class="hide-xs">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assets as $asset)
                    <tr>
                        <td>
                            <a href="{{ route('admin.assets.show', $asset) }}">
                                <b class="table__code">{{ $asset->asset_code }}</b>
                                <span class="table__sub">{{ $asset->assetType->name }}</span>
                            </a>
                        </td>
                        <td class="hide-sm">
                            <span class="mono muted">{{ $asset->serial_number ?? '—' }}</span>
                        </td>
                        <td><x-status :status="$asset->status" /></td>
                        <td><x-status :status="$asset->condition" /></td>
                        <td class="hide-sm muted">{{ $asset->location?->name ?? '—' }}</td>
                        <td class="col-actions">
                            <x-btn :href="route('admin.assets.show', $asset)" size="sm" variant="ghost" icon="eye"
                                   aria-label="Detail aset {{ $asset->asset_code }}" title="Detail aset" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty icon="box" title="Aset tidak ditemukan"
                                     text="Coba ubah kata kunci, status, atau kategori yang dipilih.">
                                <x-btn :href="route('admin.assets.index')" variant="ghost" size="sm" icon="refresh">Reset filter</x-btn>
                            </x-empty>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($assets->hasPages())
        <div class="card__foot btn-row btn-row--between">
            <span class="tiny dim">
                Halaman {{ $assets->currentPage() }} dari {{ $assets->lastPage() }}
            </span>
            {{ $assets->links() }}
        </div>
    @endif
</x-card>
@endsection
