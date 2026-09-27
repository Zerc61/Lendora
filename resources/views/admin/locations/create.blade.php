{{-- resources/views/admin/locations/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Tambah Lokasi')
@section('chrome', 'Lokasi')

@section('content')
<x-page-head :back="route('admin.locations.index')" title="Tambah Lokasi"
             subtitle="Lokasi akar berdiri sendiri, sub-lokasi menunjuk satu induk." />

<form method="POST" action="{{ route('admin.locations.store') }}" class="card">
    @csrf

    <div class="divider--label">Identitas</div>
    <div class="form-grid">
        <x-field name="name" label="Nama Lokasi" required hint="Contoh: Ruang Arsip 2, Lemari Rak A-3.">
            <x-slot:control>
                <input name="name" value="{{ old('name') }}" maxlength="255" required placeholder="mis. Ruang Arsip 2">
            </x-slot:control>
        </x-field>

        <x-field name="parent_id" label="Lokasi Induk" hint="Kosongkan bila lokasi ini adalah lokasi akar.">
            <x-slot:control>
                <select name="parent_id">
                    <option value="">— tanpa induk —</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}" @selected((string) old('parent_id') === (string) $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="description" label="Deskripsi" hint="Opsional. Ditampilkan pada detail aset.">
            <x-slot:control>
                <textarea name="description" rows="3" maxlength="2000" placeholder="mis. Lantai 2, dekat pintu tangga.">{{ old('description') }}</textarea>
            </x-slot:control>
        </x-field>
    </div>

    <div class="card__foot btn-row btn-row--end">
        <x-btn :href="route('admin.locations.index')" variant="ghost">Batal</x-btn>
        <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan</button>
    </div>
</form>
@endsection
