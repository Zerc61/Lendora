{{-- resources/views/admin/organizations/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Organisasi')
@section('chrome', 'Organisasi')

@section('content')
<x-page-head :back="route('admin.organizations.index')" title="Edit Organisasi"
             :subtitle="'Perbarui data ' . $organization->name . '. Kode dipakai pada laporan dan ekspor CSV.'" />

<form method="POST" action="{{ route('admin.organizations.update', $organization) }}" class="card">
    @csrf
    @method('PUT')

    <div class="divider--label">Identitas</div>
    <div class="form-grid">
        <x-field name="name" label="Nama Organisasi" required>
            <x-slot:control>
                <input name="name" value="{{ old('name', $organization->name) }}" maxlength="255" required>
            </x-slot:control>
        </x-field>

        <x-field name="code" label="Kode" required hint="Harus unik dan hanya berisi huruf, angka, atau tanda hubung.">
            <x-slot:control>
                <input name="code" value="{{ old('code', $organization->code) }}" maxlength="20" required>
            </x-slot:control>
        </x-field>

        <x-field name="status" label="Status" required>
            <x-slot:control>
                <select name="status" required>
                    @foreach (\App\Enums\OrganizationStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected(old('status', $organization->status->value) === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="description" label="Deskripsi" hint="Opsional.">
            <x-slot:control>
                <textarea name="description" rows="3" maxlength="2000">{{ old('description', $organization->description) }}</textarea>
            </x-slot:control>
        </x-field>
    </div>

    <div class="card__foot btn-row btn-row--end">
        <x-btn :href="route('admin.organizations.index')" variant="ghost">Batal</x-btn>
        <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan Perubahan</button>
    </div>
</form>
@endsection
