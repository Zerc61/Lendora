{{-- resources/views/admin/users/index.blade.php — master pengguna --}}
@extends('layouts.app')

@section('title', 'Pengguna')
@section('chrome', 'Pengguna')

@section('content')
<x-page-head title="Pengguna"
             subtitle="Akun staf, teknisi, dan peminjam. Peran menentukan menu dan hak akses.">
    @can('create', \App\Models\User::class)
        <x-btn href="{{ route('admin.users.create') }}" variant="primary" icon="plus">Tambah Pengguna</x-btn>
    @endcan
</x-page-head>

<x-card flush icon="users" title="Daftar Pengguna"
        subtitle="Akun nonaktif tetap tampil namun tidak dapat masuk sistem." :delay="0">
    <x-slot:actions>
        <span class="badge tone-muted">{{ number_format($users->total(), 0, ',', '.') }} pengguna</span>
    </x-slot:actions>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Pengguna</th>
                    <th scope="col" class="hide-sm">Organisasi</th>
                    <th scope="col">Peran</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="hide-sm">Bergabung</th>
                    <th scope="col" class="col-actions"><span class="hide-sm">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td>
                            <div class="cell-media">
                                <span class="avatar avatar--plain">{{ $u->initials() }}</span>
                                <div class="cell-media__body">
                                    <b>{{ $u->name }}</b>
                                    <span class="truncate">{{ $u->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="hide-sm">
                            @if ($u->organization)
                                <span class="badge tone-muted badge--plain">{{ $u->organization->name }}</span>
                            @else
                                <span class="dim">Platform-level</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-row">
                                @forelse ($u->getRoleNames() as $roleName)
                                    <span class="badge tone-brand badge--plain">{{ $roleName }}</span>
                                @empty
                                    <span class="dim">Tanpa peran</span>
                                @endforelse
                            </div>
                        </td>
                        <td><x-status :status="$u->status" /></td>
                        <td class="hide-sm muted nowrap">{{ $u->created_at->format('d M Y') }}</td>
                        <td class="col-actions">
                            <div class="btn-row btn-row--end">
                                <x-btn :href="route('admin.users.edit', $u)" size="sm" variant="ghost" icon="edit" class="btn--icon"
                                       title="Edit {{ $u->name }}" aria-label="Edit {{ $u->name }}" />
                                @can('delete', $u)
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                          data-confirm="Hapus pengguna {{ $u->name }}? Akun dinonaktifkan permanen (soft delete).">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--danger btn--sm btn--icon"
                                                title="Hapus {{ $u->name }}" aria-label="Hapus {{ $u->name }}">
                                            <x-icon name="trash" />
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty icon="users" title="Belum ada pengguna"
                                     text="Tambahkan staf atau peminjam agar aset bisa dipinjam dan dipelihara.">
                                @can('create', \App\Models\User::class)
                                    <x-btn href="{{ route('admin.users.create') }}" variant="primary" size="sm" icon="plus">Tambah Pengguna</x-btn>
                                @endcan
                            </x-empty>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card__foot">{{ $users->links() }}</div>
</x-card>
@endsection
