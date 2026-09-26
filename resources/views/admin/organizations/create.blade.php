{{-- resources/views/admin/organizations/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Tambah Organisasi')
@section('content')
<h1>Tambah Organisasi</h1>
<div class="card" style="max-width:480px">
    <form method="POST" action="{{ route('admin.organizations.store') }}">
        @csrf
        <label>Nama</label>
        <input name="name" value="{{ old('name') }}" required>
        <label>Kode (unik)</label>
        <input name="code" value="{{ old('code') }}" required>
        <label>Status</label>
        <select name="status">
            <option value="active" {{ old('status') === 'inactive' ? '' : 'selected' }}>Active</option>
            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <label>Deskripsi</label>
        <textarea name="description" rows="3">{{ old('description') }}</textarea>
        <button type="submit">Simpan</button>
        <a href="{{ route('admin.organizations.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection