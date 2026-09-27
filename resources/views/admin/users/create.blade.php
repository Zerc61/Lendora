{{-- resources/views/admin/users/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('chrome', 'Pengguna')

@section('content')
<x-page-head :back="route('admin.users.index')" title="Tambah Pengguna"
             subtitle="Akun baru langsung dapat masuk dengan peran yang dipilih." />

<form method="POST" action="{{ route('admin.users.store') }}" class="card">
    @csrf

    <div class="stack" style="--gap:18px">
        <div class="divider--label">Identitas</div>
        <div class="form-grid">
            <x-field name="name" label="Nama Lengkap" required>
                <x-slot:control>
                    <input name="name" value="{{ old('name') }}" maxlength="255" required placeholder="mis. Rina Prasetyo">
                </x-slot:control>
            </x-field>

            <x-field name="email" label="Email" required hint="Dipakai untuk masuk dan notifikasi.">
                <x-slot:control>
                    <input type="email" name="email" value="{{ old('email') }}" maxlength="255" required
                           placeholder="nama@organisasi.test">
                </x-slot:control>
            </x-field>

            <x-field name="organization_id" label="Organisasi" hint="Kosongkan untuk akun platform-level.">
                <x-slot:control>
                    <select name="organization_id">
                        <option value="">— tanpa organisasi (platform-level) —</option>
                        @foreach ($organizations as $id => $orgName)
                            <option value="{{ $id }}" @selected((string) old('organization_id') === (string) $id)>{{ $orgName }}</option>
                        @endforeach
                    </select>
                </x-slot:control>
            </x-field>
        </div>

        <div class="divider--label">Akses</div>
        <div class="form-grid">
            <x-field name="role" label="Peran" required hint="Satu peran per akun.">
                <x-slot:control>
                    <select name="role" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                </x-slot:control>
            </x-field>

            <x-field name="status" label="Status" required>
                <x-slot:control>
                    <select name="status" required>
                        @foreach (\App\Enums\UserStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('status', 'active') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </x-slot:control>
            </x-field>
        </div>

        <div class="divider--label">Keamanan</div>
        <div class="form-grid">
            <x-field name="password" label="Password" required hint="Minimal 8 karakter. Sampaikan lewat kanal aman.">
                <x-slot:control>
                    <input type="password" name="password" minlength="8" required autocomplete="new-password">
                </x-slot:control>
            </x-field>
        </div>
    </div>

    <div class="card__foot btn-row btn-row--end">
        <x-btn :href="route('admin.users.index')" variant="ghost">Batal</x-btn>
        <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan Pengguna</button>
    </div>
</form>
@endsection
