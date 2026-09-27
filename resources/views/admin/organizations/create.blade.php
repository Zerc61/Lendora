{{-- resources/views/admin/organizations/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Tambah Organisasi')
@section('chrome', 'Organisasi')

@section('content')
<x-page-head :back="route('admin.organizations.index')" title="Tambah Organisasi"
             subtitle="Kode organisasi dipakai sebagai identitas ringkas pada data peminjam." />

<form method="POST" action="{{ route('admin.organizations.store') }}" class="card">
    @csrf

    <div class="divider--label">Identitas</div>
    <div class="form-grid">
        <x-field name="name" label="Nama Organisasi" required hint="Contoh: Politeknik Eligible, CV Logistik Sejahtera.">
            <x-slot:control>
                <input name="name" value="{{ old('name') }}" maxlength="255" required placeholder="mis. Politeknik Eligible">
            </x-slot:control>
        </x-field>

        <x-field name="code" label="Kode" required hint="Maksimal 20 karakter, hanya huruf, angka, tanda hubung.">
            <x-slot:control>
                <input name="code" value="{{ old('code') }}" maxlength="20" required placeholder="mis. POLTEK">
            </x-slot:control>
        </x-field>

        <x-field name="status" label="Status" required>
            <x-slot:control>
                <select name="status" required>
                    @foreach (\App\Enums\OrganizationStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected(old('status', 'active') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="description" label="Deskripsi" hint="Opsional.">
            <x-slot:control>
                <textarea name="description" rows="3" maxlength="2000">{{ old('description') }}</textarea>
            </x-slot:control>
        </x-field>
    </div>

    <div class="card__foot btn-row btn-row--end">
        <x-btn :href="route('admin.organizations.index')" variant="ghost">Batal</x-btn>
        <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan</button>
    </div>
</form>
@endsection
