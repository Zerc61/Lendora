{{-- resources/views/admin/categories/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Kategori')
@section('chrome', 'Kategori')

@section('content')
<x-page-head :back="route('admin.categories.index')" title="Edit Kategori"
             :subtitle="'Perbarui data kategori ' . $category->name . '. Tipe aset yang memakainya tidak berubah.'" />

<form method="POST" action="{{ route('admin.categories.update', $category) }}" class="card">
    @csrf
    @method('PUT')

    <div class="divider--label">Identitas</div>
    <div class="form-grid">
        <x-field name="name" label="Nama Kategori" required>
            <x-slot:control>
                <input name="name" value="{{ old('name', $category->name) }}" maxlength="255" required>
            </x-slot:control>
        </x-field>

        <x-field name="description" label="Deskripsi" hint="Opsional.">
            <x-slot:control>
                <textarea name="description" rows="3" maxlength="2000">{{ old('description', $category->description) }}</textarea>
            </x-slot:control>
        </x-field>
    </div>

    <div class="card__foot btn-row btn-row--end">
        <x-btn :href="route('admin.categories.index')" variant="ghost">Batal</x-btn>
        <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan Perubahan</button>
    </div>
</form>
@endsection
