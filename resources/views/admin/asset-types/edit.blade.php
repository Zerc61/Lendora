{{-- resources/views/admin/asset-types/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Tipe Aset')
@section('content')
<h1>Edit Tipe Aset</h1>
<div class="card" style="max-width:480px">
    <form method="POST" action="{{ route('admin.asset-types.update', $assetType) }}">
        @csrf @method('PUT')
        <label>Kategori</label>
        <select name="category_id" required>
            @foreach($categories as $c)
            <option value="{{ $c->id }}" {{ old('category_id', $assetType->category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <label>Nama Tipe</label>
        <input name="name" value="{{ old('name', $assetType->name) }}" required>
        <label>Brand</label>
        <input name="brand" value="{{ old('brand', $assetType->brand) }}">
        <label>Model</label>
        <input name="model" value="{{ old('model', $assetType->model) }}">
        <label>Deskripsi</label>
        <textarea name="description" rows="3">{{ old('description', $assetType->description) }}</textarea>
        <button>Update</button>
        <a href="{{ route('admin.asset-types.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
