{{-- resources/views/admin/users/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Pengguna')
@section('chrome', 'Pengguna')

@section('content')
<x-page-head :back="route('admin.users.index')" title="Edit Pengguna" :subtitle="$user->name" />

<div class="stack" style="--gap:18px">
    <div class="inline-alert tone-brand">
        <x-icon name="info" />
        <div>
            <b>{{ $user->email }}</b>
            · Bergabung {{ $user->created_at->translatedFormat('d F Y') }}
            · Terakhir diubah {{ $user->updated_at->diffForHumans() }}
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="card" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="stack" style="--gap:18px">
            <div class="divider--label">Identitas</div>
            <div class="form-grid">
                <x-field name="name" label="Nama Lengkap" required>
                    <x-slot:control>
                        <input name="name" value="{{ old('name', $user->name) }}" maxlength="255" required>
                    </x-slot:control>
                </x-field>

                <x-field name="email" label="Email" required hint="Dipakai untuk masuk dan notifikasi.">
                    <x-slot:control>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" required>
                    </x-slot:control>
                </x-field>

                <x-field name="organization_id" label="Organisasi" hint="Kosongkan untuk akun platform-level.">
                    <x-slot:control>
                        <select name="organization_id">
                            <option value="">— tanpa organisasi (platform-level) —</option>
                            @foreach ($organizations as $id => $orgName)
                                <option value="{{ $id }}" @selected((string) old('organization_id', $user->organization_id) === (string) $id)>{{ $orgName }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="divider--label">Data Siswa</div>
            <p class="tiny dim" style="margin-top:-8px">
                Berisi bila akun berperan <b>borrower</b> (siswa).
            </p>
            <div class="form-grid">
                <x-field name="school_class_id" label="Kelas">
                    <x-slot:control>
                        <select name="school_class_id" data-cascade="school">
                            <option value="">— bukan siswa —</option>
                            @foreach ($schoolClasses as $class)
                                <option value="{{ $class->id }}"
                                        @selected((string) old('school_class_id', $user->school_class_id) === (string) $class->id)>
                                    {{ $class->program->education_level->label() }} · {{ $class->program->name }} — {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="identity_number" label="NIS / NISN">
                    <x-slot:control>
                        <input name="identity_number" value="{{ old('identity_number', $user->identity_number) }}"
                               maxlength="40" placeholder="mis. SMK-1234-0001">
                    </x-slot:control>
                </x-field>

                <x-field name="gender" label="Jenis Kelamin">
                    <x-slot:control>
                        <select name="gender">
                            <option value="">—</option>
                            @foreach (\App\Enums\Gender::cases() as $g)
                                <option value="{{ $g->value }}" @selected(old('gender', $user->gender?->value) === $g->value)>
                                    {{ $g->label() }}
                                </option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="birth_date" label="Tanggal Lahir">
                    <x-slot:control>
                        <input type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}">
                    </x-slot:control>
                </x-field>

                <x-field name="phone" label="Telepon">
                    <x-slot:control>
                        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="25">
                    </x-slot:control>
                </x-field>

                <x-field name="photo" label="Foto Profil" hint="JPG/PNG/WebP, maks 2 MB. Kosongkan bila tidak ingin mengganti.">
                    <x-slot:control>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="input"
                               style="padding:7px 10px;height:auto">
                    </x-slot:control>
                </x-field>
            </div>

            <div class="form-grid">
                <x-field name="address" label="Alamat">
                    <x-slot:control>
                        <input name="address" value="{{ old('address', $user->address) }}" maxlength="255">
                    </x-slot:control>
                </x-field>

                <x-field name="bio" label="Deskripsi">
                    <x-slot:control>
                        <textarea name="bio" rows="2" maxlength="500">{{ old('bio', $user->bio) }}</textarea>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="divider--label">Akses</div>
            <div class="form-grid">
                <x-field name="role" label="Peran" required hint="Satu peran per akun.">
                    <x-slot:control>
                        <select name="role" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('role', $user->roles->first()?->name) === $role)>{{ $role }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="status" label="Status" required>
                    <x-slot:control>
                        <select name="status" required>
                            @foreach (\App\Enums\UserStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected(old('status', $user->status->value) === $case->value)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="divider--label">Keamanan &amp; Notifikasi</div>
            <div class="form-grid">
                <x-field name="password" label="Password Baru" hint="Kosongkan bila tidak ingin mengganti password.">
                    <x-slot:control>
                        <input type="password" name="password" minlength="8" autocomplete="new-password">
                    </x-slot:control>
                </x-field>

                <div class="field">
                    <span class="label">Notifikasi</span>
                    <div>
                        <label class="check">
                            <input type="checkbox" name="email_notifications" value="1"
                                   @checked(old('email_notifications', $user->wantsMail()))>
                            <span>
                                Kirim notifikasi via email
                                <small>Persetujuan, tenggat pengembalian, dan jadwal maintenance.</small>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card__foot btn-row btn-row--end">
            <x-btn :href="route('admin.users.index')" variant="ghost">Batal</x-btn>
            <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
