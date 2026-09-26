{{-- resources/views/admin/asset-types/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Tambah Tipe Aset')
@section('content')
<h1>Tambah Tipe Aset</h1>
<div class="card" style="max-width:480px">
    <form method="POST" action="{{ route('admin.asset-types.store') }}">
        @csrf
        <label>Kategori</label>
        <select name="category_id" required>
            <option value="">— pilih kategori —</option>
            @foreach($categories as $c)
            <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <label>Nama Tipe</label>
        <input name="name" value="{{ old('name') }}" required>
        <label>Brand</label>
        <input name="brand" value="{{ old('brand') }}">
        <label>Model</label>
        <input name="model" value="{{ old('model') }}">
        <label>Deskripsi</label>
        <textarea name="description" rows="3">{{ old('description') }}</textarea>
        <button>Simpan</button>
        <a href="{{ route('admin.asset-types.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
