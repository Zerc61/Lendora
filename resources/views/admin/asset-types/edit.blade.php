{{-- resources/views/admin/asset-types/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Tipe Aset')
@section('chrome', 'Tipe Aset')

@section('content')
<x-page-head :back="route('admin.asset-types.index')" title="Edit Tipe Aset"
             :subtitle="'Perbarui klasifikasi dan spesifikasi ' . $assetType->name . '.'" />

<form method="POST" action="{{ route('admin.asset-types.update', $assetType) }}" class="card">
    @csrf
    @method('PUT')

    <div class="stack" style="--gap:18px">
        <div class="divider--label">Klasifikasi</div>
        <div class="form-grid">
            <x-field name="category_id" label="Kategori" required>
                <x-slot:control>
                    <select name="category_id" required>
                        <option value="">— pilih kategori —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $assetType->category_id) === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </x-slot:control>
            </x-field>

            <x-field name="name" label="Nama Tipe" required>
                <x-slot:control>
                    <input name="name" value="{{ old('name', $assetType->name) }}" maxlength="255" required>
                </x-slot:control>
            </x-field>
        </div>

        <div class="divider--label">Spesifikasi</div>
        <div class="form-grid">
            <x-field name="brand" label="Merek" hint="Opsional.">
                <x-slot:control>
                    <input name="brand" value="{{ old('brand', $assetType->brand) }}" maxlength="100">
                </x-slot:control>
            </x-field>

            <x-field name="model" label="Model" hint="Opsional.">
                <x-slot:control>
                    <input name="model" value="{{ old('model', $assetType->model) }}" maxlength="100">
                </x-slot:control>
            </x-field>

            <x-field name="description" label="Deskripsi" hint="Opsional. Muncul di katalog aset.">
                <x-slot:control>
                    <textarea name="description" rows="3" maxlength="2000">{{ old('description', $assetType->description) }}</textarea>
                </x-slot:control>
            </x-field>
        </div>
    </div>

    <div class="card__foot btn-row btn-row--end">
        <x-btn :href="route('admin.asset-types.index')" variant="ghost">Batal</x-btn>
        <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan Perubahan</button>
    </div>
</form>
@endsection
