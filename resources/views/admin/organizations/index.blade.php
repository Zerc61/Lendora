{{-- resources/views/admin/organizations/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Organisasi')
@section('content')
<div class="card row">
    <h1 style="margin:0">Organisasi</h1>
    <a href="{{ route('admin.organizations.create') }}">+ Tambah Organisasi</a>
</div>
<div class="card">
    <table>
        <tr><th>Nama</th><th>Kode</th><th>Status</th><th>Users</th><th>Aksi</th></tr>
        @forelse($organizations as $org)
        <tr>
            <td>{{ $org->name }}</td>
            <td>{{ $org->code }}</td>
            <td>{{ $org->status->value }}</td>
            <td>{{ $org->users_count }}</td>
            <td>
                <a href="{{ route('admin.organizations.edit', $org) }}">Edit</a>
                <form method="POST" action="{{ route('admin.organizations.destroy', $org) }}" style="display:inline" onsubmit="return confirm('Hapus organisasi ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="background:#7f1d1d;padding:5px 10px;font-size:12px">Hapus</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="muted">Belum ada organisasi.</td></tr>
        @endforelse
    </table>
    {{ $organizations->links() }}
</div>
@endsection