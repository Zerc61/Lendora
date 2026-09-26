{{-- resources/views/admin/users/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Pengguna')
@section('content')
<h1>Edit Pengguna: {{ $user->name }}</h1>
<div class="card" style="max-width:480px">
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf @method('PUT')
        <label>Nama</label>
        <input name="name" value="{{ old('name', $user->name) }}" required>
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
        <label>Password (kosongkan jika tidak diubah)</label>
        <input type="password" name="password" minlength="8">
        <label>Organisasi</label>
        <select name="organization_id">
            <option value="">— tanpa organisasi (platform-level) —</option>
            @foreach($organizations as $id => $name)
            <option value="{{ $id }}" {{ old('organization_id', $user->organization_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        <label>Role</label>
        <select name="role">
            @foreach($roles as $role)
            <option value="{{ $role }}" {{ old('role', $user->roles->first()?->name) === $role ? 'selected' : '' }}>{{ $role }}</option>
            @endforeach
        </select>
        <label>Status</label>
        <select name="status">
            <option value="active" {{ old('status', $user->status->value) === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $user->status->value) === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <label style="display:flex;gap:8px;align-items:center">
            <input type="checkbox" name="email_notifications" value="1" style="width:auto"
                {{ old('email_notifications', $user->wantsMail()) ? 'checked' : '' }}>
            Kirim notifikasi via email (opsional)
        </label>
        <button type="submit">Update</button>
        <a href="{{ route('admin.users.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection