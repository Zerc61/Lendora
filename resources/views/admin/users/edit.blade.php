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

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="card">
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
