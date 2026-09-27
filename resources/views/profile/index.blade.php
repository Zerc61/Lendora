{{-- resources/views/profile/index.blade.php — Data akun, ganti password, preferensi notifikasi --}}
@extends('layouts.app')
@section('title', 'Profil Saya')
@section('chrome', 'Profil')

@section('content')
<x-page-head title="Profil Saya"
             subtitle="Data akun, keamanan, dan cara Lendora mengabari Anda." />

<div class="grid grid--main">
    <div class="stack" style="--gap:18px">
        {{-- Foto profil --}}
        <x-card title="Foto Profil" icon="camera" subtitle="JPG, PNG, atau WebP — maksimal 2 MB." :delay="0">
            <div class="cell-media profile-head" style="margin-bottom:16px">
                <span class="avatar avatar--xl {{ $user->hasPhoto() ? '' : 'avatar--plain' }}">
                    @if ($user->hasPhoto())
                        <img src="{{ $user->photoUrl() }}" alt="Foto {{ $user->name }}">
                    @else
                        {{ $user->initials() }}
                    @endif
                </span>
                <div class="cell-media__body">
                    <b>{{ $user->name }}</b>
                    <span>{{ $user->hasPhoto() ? 'Foto tersimpan. Unggah yang baru untuk mengganti.' : 'Belum ada foto — sistem memakai inisial.' }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data" class="stack" style="--gap:12px">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="f-photo">Pilih gambar</label>
                    <input type="file" id="f-photo" name="photo" accept="image/jpeg,image/png,image/webp"
                           class="input" style="padding:7px 10px;height:auto">
                    @error('photo')<p class="field__error">{{ $message }}</p>@enderror
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn btn--primary btn--sm">
                        <x-icon name="camera" /> Unggah Foto
                    </button>
                    @if ($user->hasPhoto())
                        <button type="submit" name="remove_photo" value="1" class="btn btn--ghost btn--sm"
                                formnovalidate>
                            <x-icon name="trash" /> Hapus Foto
                        </button>
                    @endif
                </div>
            </form>
        </x-card>

        {{-- Data akun (read-only) --}}
        <x-card title="Data Akun" subtitle="Data ini dikelola admin, hubungi administrator bila perlu diperbarui."
                icon="user" :delay="60">
            <x-slot:actions>
                <span class="badge tone-brand">{{ $user->roleLabel() }}</span>
            </x-slot:actions>

            <div class="cell-media" style="margin-bottom:14px">
                <span class="avatar avatar--lg">{{ $user->initials() }}</span>
                <span class="cell-media__body">
                    <b>{{ $user->name }}</b>
                    <span>{{ $user->email }}</span>
                </span>
            </div>
            <dl class="kv">
                <div class="kv__row">
                    <dt>Nama lengkap</dt>
                    <dd>{{ $user->name }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Email</dt>
                    <dd>{{ $user->email }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Organisasi</dt>
                    <dd>{{ $user->organization?->name ?? '— (platform-level)' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Hak akses</dt>
                    <dd>{{ $user->getRoleNames()->implode(', ') ?: '—' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Status akun</dt>
                    <dd><x-status :status="$user->status" /></dd>
                </div>
                <div class="kv__row">
                    <dt>Bergabung</dt>
                    <dd class="nowrap">{{ $user->created_at?->format('d M Y') ?? '—' }}</dd>
                </div>
            </dl>
        </x-card>

        {{-- Preferensi notifikasi --}}
        <x-card title="Preferensi Notifikasi" subtitle="Notifikasi di dalam aplikasi selalu aktif." icon="bell" :delay="60">
            <form method="POST" action="{{ route('profile.preferences') }}">
                @csrf
                @method('PUT')

                <label class="check" for="email_enabled">
                    <input type="checkbox" id="email_enabled" name="email_enabled" value="1"
                           {{ old('email_enabled', $user->wantsMail()) ? 'checked' : '' }}>
                    <span>
                        Kirim salinan notifikasi ke email saya
                        <small>Berlaku untuk persetujuan reservasi, pengingat tenggat, dan peringatan keterlambatan.</small>
                    </span>
                </label>

                <div class="inline-alert tone-muted" style="margin-top:14px">
                    <x-icon name="info" />
                    <div>Email hanya terkirim bila dicentang dan mail server sudah dikonfigurasi oleh administrator.</div>
                </div>

                <div class="card__foot">
                    <div class="btn-row btn-row--end">
                        <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan Preferensi</button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>

    {{-- Keamanan --}}
    <div class="stack" style="--gap:18px">
        {{-- Data kontak — bisa diubah sendiri --}}
        <x-card title="Kontak & Deskripsi" icon="edit" subtitle="Nomor telepon, alamat, dan catatan singkat." :delay="0">
            <form method="POST" action="{{ route('profile.identity') }}" class="form">
                @csrf
                @method('PUT')

                <x-field name="phone" label="Telepon">
                    <x-slot:control>
                        <input type="tel" id="f-phone" name="phone" value="{{ old('phone', $user->phone) }}"
                               placeholder="08xxxxxxxxxx" autocomplete="tel">
                    </x-slot:control>
                </x-field>

                <x-field name="address" label="Alamat">
                    <x-slot:control>
                        <input type="text" id="f-address" name="address" value="{{ old('address', $user->address) }}"
                               placeholder="Jl. Merdeka No 1, Kota">
                    </x-slot:control>
                </x-field>

                <x-field name="bio" label="Deskripsi" hint="Maksimal 500 karakter.">
                    <x-slot:control>
                        <textarea id="f-bio" name="bio" rows="3"
                                  placeholder="Ceritakan singkat tentang diri Anda.">{{ old('bio', $user->bio) }}</textarea>
                    </x-slot:control>
                </x-field>

                <button type="submit" class="btn btn--primary btn--sm btn--block">
                    <x-icon name="check" /> Simpan Data
                </button>
            </form>
        </x-card>

        <div class="sticky-panel">
            <x-card title="Ganti Password" icon="shield" tint>
                <x-slot:actions>
                    <span class="badge tone-muted">Minimal 8 karakter</span>
                </x-slot:actions>

                <form method="POST" action="{{ route('profile.password') }}" class="form">
                    @csrf
                    @method('PUT')

                    <x-field name="current_password" label="Password Saat Ini" required>
                        <x-slot:control>
                            <input type="password" id="f-current_password" name="current_password"
                                   autocomplete="current-password" required>
                        </x-slot:control>
                    </x-field>

                    <x-field name="password" label="Password Baru" required
                             hint="Minimal 8 karakter.">
                        <x-slot:control>
                            <input type="password" id="f-password" name="password"
                                   autocomplete="new-password" required minlength="8">
                        </x-slot:control>
                    </x-field>

                    <x-field name="password_confirmation" label="Konfirmasi Password Baru" required
                             hint="Ulangi password baru persis sama.">
                        <x-slot:control>
                            <input type="password" id="f-password_confirmation" name="password_confirmation"
                                   autocomplete="new-password" required minlength="8">
                        </x-slot:control>
                    </x-field>

                    <div class="card__foot">
                        <div class="btn-row btn-row--end">
                            <button type="submit" class="btn btn--primary btn--block">
                                <x-icon name="shield" /> Simpan Password
                            </button>
                        </div>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</div>
@endsection
