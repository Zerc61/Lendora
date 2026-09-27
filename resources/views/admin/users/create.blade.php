{{-- resources/views/admin/users/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('chrome', 'Pengguna')

@section('content')
<x-page-head :back="route('admin.users.index')" title="Tambah Pengguna"
             subtitle="Akun baru langsung dapat masuk dengan peran yang dipilih." />

<form method="POST" action="{{ route('admin.users.store') }}" class="card" enctype="multipart/form-data">
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

        <div class="divider--label">Data Siswa</div>
        <p class="tiny dim" style="margin-top:-8px">
            Isi bagian ini bila akun berperan <b>borrower</b> (siswa). Untuk staff, teknisi, dan admin boleh dikosongkan.
        </p>
        <div class="form-grid">
            <x-field name="school_class_id" label="Kelas" hint="Pilih kelas untuk menautkan siswa ke rombelan.">
                <x-slot:control>
                    <select name="school_class_id" data-cascade="school">
                        <option value="">— bukan siswa —</option>
                        @foreach ($schoolClasses as $class)
                            <option value="{{ $class->id }}" @selected((string) old('school_class_id') === (string) $class->id)>
                                {{ $class->program->education_level->label() }} · {{ $class->program->name }} — {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </x-slot:control>
            </x-field>

            <x-field name="identity_number" label="NIS / NISN">
                <x-slot:control>
                    <input name="identity_number" value="{{ old('identity_number') }}" maxlength="40"
                           placeholder="mis. SMK-1234-0001">
                </x-slot:control>
            </x-field>

            <x-field name="gender" label="Jenis Kelamin">
                <x-slot:control>
                    <select name="gender">
                        <option value="">—</option>
                        @foreach (\App\Enums\Gender::cases() as $g)
                            <option value="{{ $g->value }}" @selected(old('gender') === $g->value)>{{ $g->label() }}</option>
                        @endforeach
                    </select>
                </x-slot:control>
            </x-field>

            <x-field name="birth_date" label="Tanggal Lahir">
                <x-slot:control>
                    <input type="date" name="birth_date" value="{{ old('birth_date') }}">
                </x-slot:control>
            </x-field>

            <x-field name="phone" label="Telepon">
                <x-slot:control>
                    <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="25" placeholder="08xxxxxxxxxx">
                </x-slot:control>
            </x-field>

            <x-field name="photo" label="Foto Profil" hint="JPG/PNG/WebP, maks 2 MB.">
                <x-slot:control>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="input"
                           style="padding:7px 10px;height:auto">
                </x-slot:control>
            </x-field>
        </div>

        <div class="form-grid">
            <x-field name="address" label="Alamat">
                <x-slot:control>
                    <input name="address" value="{{ old('address') }}" maxlength="255" placeholder="Jl. Merdeka No 1">
                </x-slot:control>
            </x-field>

            <x-field name="bio" label="Deskripsi">
                <x-slot:control>
                    <textarea name="bio" rows="2" maxlength="500" placeholder="Catatan singkat tentang siswa.">{{ old('bio') }}</textarea>
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
