{{-- resources/views/admin/organizations/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Organisasi')
@section('content')
<h1>Edit Organisasi</h1>
<div class="card" style="max-width:480px">
    <form method="POST" action="{{ route('admin.organizations.update', $organization) }}">
        @csrf @method('PUT')
        <label>Nama</label>
        <input name="name" value="{{ old('name', $organization->name) }}" required>
        <label>Kode (unik)</label>
        <input name="code" value="{{ old('code', $organization->code) }}" required>
        <label>Status</label>
        <select name="status">
            <option value="active" {{ old('status', $organization->status->value) === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $organization->status->value) === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <label>Deskripsi</label>
        <textarea name="description" rows="3">{{ old('description', $organization->description) }}</textarea>
        <button type="submit">Update</button>
        <a href="{{ route('admin.organizations.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection