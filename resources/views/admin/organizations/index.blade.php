{{-- resources/views/admin/organizations/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Organisasi')
@section('chrome', 'Organisasi')

@section('content')
<x-page-head title="Organisasi"
             subtitle="Unit kerja peminjam aset. Pengguna tanpa organisasi behaving sebagai akun platform-level.">
    @can('create', \App\Models\Organization::class)
        <x-btn href="{{ route('admin.organizations.create') }}" variant="primary" icon="plus">Tambah Organisasi</x-btn>
    @endcan
</x-page-head>

<x-card flush icon="building" title="Daftar Organisasi"
        subtitle="Penghapusan memakai soft delete sehingga histori transaksi tetap utuh." :delay="0">
    <x-slot:actions>
        <span class="badge tone-muted">{{ number_format($organizations->total(), 0, ',', '.') }} organisasi</span>
    </x-slot:actions>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Organisasi</th>
                    <th scope="col" class="hide-sm">Kode</th>
                    <th scope="col" class="hide-sm">Status</th>
                    <th scope="col" class="num">Pengguna</th>
                    <th scope="col" class="num hide-sm">Jurusan</th>
                    <th scope="col" class="num hide-sm">Kelas</th>
                    <th scope="col" class="num hide-sm">Ruang</th>
                    <th scope="col" class="col-actions"><span class="hide-sm">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($organizations as $org)
                    <tr>
                        <td>
                            <div class="cell-media">
                                <span class="thumb thumb--ph"><x-icon name="building" /></span>
                                <div class="cell-media__body">
                                    <b>{{ $org->name }}</b>
                                    <span>{{ \Illuminate\Support\Str::limit($org->description ?? '', 52) ?: 'Tanpa deskripsi' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="hide-sm"><span class="table__code">{{ $org->code }}</span></td>
                        <td class="hide-sm"><x-status :status="$org->status" /></td>
                        <td class="num tnum">
                            @if ($org->users_count > 0)
                                <span class="badge tone-accent">{{ number_format($org->users_count, 0, ',', '.') }}</span>
                            @else
                                <span class="dim">0</span>
                            @endif
                        </td>
                        <td class="num tnum hide-sm">{{ $org->programs_count ?: '—' }}</td>
                        <td class="num tnum hide-sm">{{ $org->school_classes_count ?: '—' }}</td>
                        <td class="num tnum hide-sm">{{ $org->classrooms_count ?: '—' }}</td>
                        <td class="col-actions">
                            <div class="btn-row btn-row--end">
                                <x-btn :href="route('admin.organizations.show', $org)" size="sm" variant="ghost" icon="eye" class="btn--icon"
                                       title="Detail {{ $org->name }}" aria-label="Detail {{ $org->name }}" />
                                <x-btn :href="route('admin.users.index', ['organization_id' => $org->id])" size="sm" variant="ghost" icon="users" class="btn--icon"
                                       title="Pengguna {{ $org->name }}" aria-label="Pengguna {{ $org->name }}" />
                                <x-btn :href="route('admin.organizations.edit', $org)" size="sm" variant="ghost" icon="edit" class="btn--icon"
                                       title="Edit {{ $org->name }}" aria-label="Edit {{ $org->name }}" />
                                @can('delete', $org)
                                    <form method="POST" action="{{ route('admin.organizations.destroy', $org) }}"
                                          data-confirm="Hapus organisasi {{ $org->name }}? Pengguna di dalamnya menjadi platform-level.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--danger btn--sm btn--icon"
                                                title="Hapus {{ $org->name }}" aria-label="Hapus {{ $org->name }}">
                                            <x-icon name="trash" />
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-empty icon="building" title="Belum ada organisasi"
                                     text="Satu organisasi sudah cukup untuk memulai; tambahkan bila ada unit kerja lain.">
                                @can('create', \App\Models\Organization::class)
                                    <x-btn href="{{ route('admin.organizations.create') }}" variant="primary" size="sm" icon="plus">Tambah Organisasi</x-btn>
                                @endcan
                            </x-empty>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card__foot">{{ $organizations->links() }}</div>
</x-card>
@endsection
