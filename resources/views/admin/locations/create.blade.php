{{-- resources/views/admin/locations/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Tambah Lokasi')
@section('content')
<h1>Tambah Lokasi</h1>
<div class="card" style="max-width:480px">
    <form method="POST" action="{{ route('admin.locations.store') }}">
        @csrf
        <label>Nama</label>
        <input name="name" value="{{ old('name') }}" required>
        <label>Lokasi Induk (opsional)</label>
        <select name="parent_id">
            <option value="">— tanpa induk —</option>
            @foreach($locations as $loc)
            <option value="{{ $loc->id }}" {{ old('parent_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
            @endforeach
        </select>
        <label>Deskripsi</label>
        <textarea name="description" rows="3">{{ old('description') }}</textarea>
        <button>Simpan</button>
        <a href="{{ route('admin.locations.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
