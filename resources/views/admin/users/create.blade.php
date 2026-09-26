{{-- resources/views/admin/users/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Tambah Pengguna')
@section('content')
<h1>Tambah Pengguna</h1>
<div class="card" style="max-width:480px">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <label>Nama</label>
        <input name="name" value="{{ old('name') }}" required>
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        <label>Password (min. 8 karakter)</label>
        <input type="password" name="password" required minlength="8">
        <label>Organisasi</label>
        <select name="organization_id">
            <option value="">— tanpa organisasi (platform-level) —</option>
            @foreach($organizations as $id => $name)
            <option value="{{ $id }}" {{ old('organization_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        <label>Role</label>
        <select name="role">
            @foreach($roles as $role)
            <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>{{ $role }}</option>
            @endforeach
        </select>
        <label>Status</label>
        <select name="status">
            <option value="active" {{ old('status') === 'inactive' ? '' : 'selected' }}>Active</option>
            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="submit">Simpan</button>
        <a href="{{ route('admin.users.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection