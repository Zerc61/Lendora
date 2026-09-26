{{-- resources/views/admin/categories/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Tambah Kategori')
@section('content')
<h1>Tambah Kategori</h1>
<div class="card" style="max-width:480px">
    <form method="POST" action="{{ route('admin.categories.store') }}">
        @csrf
        <label>Nama</label>
        <input name="name" value="{{ old('name') }}" required>
        <label>Deskripsi</label>
        <textarea name="description" rows="3">{{ old('description') }}</textarea>
        <button>Simpan</button>
        <a href="{{ route('admin.categories.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
