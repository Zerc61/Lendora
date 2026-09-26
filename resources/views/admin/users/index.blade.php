{{-- resources/views/admin/users/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Pengguna')
@section('content')
<div class="card row">
    <h1 style="margin:0">Pengguna</h1>
    <a href="{{ route('admin.users.create') }}">+ Tambah Pengguna</a>
</div>
<div class="card">
    <table>
        <tr><th>Nama</th><th>Email</th><th>Organisasi</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
        @forelse($users as $u)
        <tr>
            <td>{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td>{{ $u->organization?->name ?? '—' }}</td>
            <td>{{ $u->roles->pluck('name')->implode(', ') }}</td>
            <td>{{ $u->status->value }}</td>
            <td>
                <a href="{{ route('admin.users.edit', $u) }}">Edit</a>
                @can('delete', $u)
                <form method="POST" action="{{ route('admin.users.destroy', $u) }}" style="display:inline" onsubmit="return confirm('Hapus pengguna ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="background:#7f1d1d;padding:5px 10px;font-size:12px">Hapus</button>
                </form>
                @endcan
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="muted">Belum ada pengguna.</td></tr>
        @endforelse
    </table>
    {{ $users->links() }}
</div>
@endsection