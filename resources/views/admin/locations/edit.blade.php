{{-- resources/views/admin/locations/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Lokasi')
@section('chrome', 'Lokasi')

@section('content')
<x-page-head :back="route('admin.locations.index')" title="Edit Lokasi"
             :subtitle="'Perbarui data lokasi ' . $location->name . '. Lokasi ini tidak dapat menjadi induk dirinya sendiri.'" />

<form method="POST" action="{{ route('admin.locations.update', $location) }}" class="card">
    @csrf
    @method('PUT')

    <div class="divider--label">Identitas</div>
    <div class="form-grid">
        <x-field name="name" label="Nama Lokasi" required>
            <x-slot:control>
                <input name="name" value="{{ old('name', $location->name) }}" maxlength="255" required>
            </x-slot:control>
        </x-field>

        <x-field name="parent_id" label="Lokasi Induk" hint="Daftar tanpa lokasi ini sendiri.">
            <x-slot:control>
                <select name="parent_id">
                    <option value="">— tanpa induk —</option>
                    @foreach ($locations->where('id', '!=', $location->id) as $loc)
                        <option value="{{ $loc->id }}" @selected((string) old('parent_id', $location->parent_id) === (string) $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="description" label="Deskripsi" hint="Opsional. Ditampilkan pada detail aset.">
            <x-slot:control>
                <textarea name="description" rows="3" maxlength="2000">{{ old('description', $location->description) }}</textarea>
            </x-slot:control>
        </x-field>
    </div>

    <div class="card__foot btn-row btn-row--end">
        <x-btn :href="route('admin.locations.index')" variant="ghost">Batal</x-btn>
        <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan Perubahan</button>
    </div>
</form>
@endsection
