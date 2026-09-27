{{-- resources/views/admin/categories/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Tambah Kategori')
@section('chrome', 'Kategori')

@section('content')
<x-page-head :back="route('admin.categories.index')" title="Tambah Kategori"
             subtitle="Kategori menjadi induk tipe aset. Nama harus unik dalam satu organisasi." />

<form method="POST" action="{{ route('admin.categories.store') }}" class="card">
    @csrf

    <div class="divider--label">Identitas</div>
    <div class="form-grid">
        <x-field name="name" label="Nama Kategori" required
                 hint="Contoh: Laptop, Alat Laboratorium, Perlengkapan Kantor.">
            <x-slot:control>
                <input name="name" value="{{ old('name') }}" maxlength="255" required
                       placeholder="mis. Laptop">
            </x-slot:control>
        </x-field>

        <x-field name="description" label="Deskripsi"
                 hint="Opsional. Ditampilkan di daftar kategori.">
            <x-slot:control>
                <textarea name="description" rows="3" maxlength="2000"
                          placeholder="Catatan singkat tentang cakupan kategori ini.">{{ old('description') }}</textarea>
            </x-slot:control>
        </x-field>
    </div>

    <div class="card__foot btn-row btn-row--end">
        <x-btn :href="route('admin.categories.index')" variant="ghost">Batal</x-btn>
        <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan</button>
    </div>
</form>
@endsection
