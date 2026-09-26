{{-- resources/views/admin/categories/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Kategori')
@section('content')
<h1>Edit Kategori</h1>
<div class="card" style="max-width:480px">
    <form method="POST" action="{{ route('admin.categories.update', $category) }}">
        @csrf @method('PUT')
        <label>Nama</label>
        <input name="name" value="{{ old('name', $category->name) }}" required>
        <label>Deskripsi</label>
        <textarea name="description" rows="3">{{ old('description', $category->description) }}</textarea>
        <button>Update</button>
        <a href="{{ route('admin.categories.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
