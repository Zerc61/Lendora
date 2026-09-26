{{-- resources/views/admin/locations/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Lokasi')
@section('content')
<h1>Edit Lokasi</h1>
<div class="card" style="max-width:480px">
    <form method="POST" action="{{ route('admin.locations.update', $location) }}">
        @csrf @method('PUT')
        <label>Nama</label>
        <input name="name" value="{{ old('name', $location->name) }}" required>
        <label>Lokasi Induk (opsional)</label>
        <select name="parent_id">
            <option value="">— tanpa induk —</option>
            @foreach($locations->where('id', '!=', $location->id) as $loc)
            <option value="{{ $loc->id }}" {{ old('parent_id', $location->parent_id) == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
            @endforeach
        </select>
        <label>Deskripsi</label>
        <textarea name="description" rows="3">{{ old('description', $location->description) }}</textarea>
        <button>Update</button>
        <a href="{{ route('admin.locations.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
