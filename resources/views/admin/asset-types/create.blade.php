{{-- resources/views/admin/asset-types/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Tambah Tipe Aset')
@section('chrome', 'Tipe Aset')

@section('content')
<x-page-head :back="route('admin.asset-types.index')" title="Tambah Tipe Aset"
             subtitle="Tipe aset menggabungkan kategori induk dengan spesifikasi merek dan model." />

<form method="POST" action="{{ route('admin.asset-types.store') }}" class="card">
    @csrf

    <div class="stack" style="--gap:18px">
        <div class="divider--label">Klasifikasi</div>
        <div class="form-grid">
            <x-field name="category_id" label="Kategori" required hint="Tentukan induk tipe aset ini.">
                <x-slot:control>
                    <select name="category_id" required>
                        <option value="">— pilih kategori —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </x-slot:control>
            </x-field>

            <x-field name="name" label="Nama Tipe" required hint="Contoh: Laptop Kerja, Proyektor Ruang Meeting.">
                <x-slot:control>
                    <input name="name" value="{{ old('name') }}" maxlength="255" required placeholder="mis. Laptop Kerja">
                </x-slot:control>
            </x-field>
        </div>

        <div class="divider--label">Spesifikasi</div>
        <div class="form-grid">
            <x-field name="brand" label="Merek" hint="Opsional.">
                <x-slot:control>
                    <input name="brand" value="{{ old('brand') }}" maxlength="100" placeholder="mis. Lenovo">
                </x-slot:control>
            </x-field>

            <x-field name="model" label="Model" hint="Opsional.">
                <x-slot:control>
                    <input name="model" value="{{ old('model') }}" maxlength="100" placeholder="mis. ThinkPad T14">
                </x-slot:control>
            </x-field>

            <x-field name="description" label="Deskripsi" hint="Opsional. Muncul di katalog aset.">
                <x-slot:control>
                    <textarea name="description" rows="3" maxlength="2000">{{ old('description') }}</textarea>
                </x-slot:control>
            </x-field>
        </div>
    </div>

    <div class="card__foot btn-row btn-row--end">
        <x-btn :href="route('admin.asset-types.index')" variant="ghost">Batal</x-btn>
        <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan</button>
    </div>
</form>
@endsection
