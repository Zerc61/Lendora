{{-- resources/views/profile/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Profil')
@section('content')
<h1>Profil Saya</h1>
<div class="card" style="max-width:520px">
    <table>
        <tr><th style="width:35%">Nama</th><td>{{ $user->name }}</td></tr>
        <tr><th>Email</th><td>{{ $user->email }}</td></tr>
        <tr><th>Organisasi</th><td>{{ $user->organization?->name ?? '— (platform-level)' }}</td></tr>
        <tr><th>Role</th><td>{{ $user->getRoleNames()->implode(', ') ?: '—' }}</td></tr>
        <tr><th>Status</th><td>{{ $user->status->value }}</td></tr>
    </table>
</div>

<div class="card" style="max-width:520px">
    <h3 style="margin-top:0">🔑 Ganti Password</h3>
    <form method="POST" action="{{ route('profile.password') }}">
        @csrf @method('PUT')
        <label>Password Saat Ini</label>
        <input type="password" name="current_password" required>
        <label>Password Baru (min. 8 karakter)</label>
        <input type="password" name="password" required minlength="8">
        <label>Konfirmasi Password Baru</label>
        <input type="password" name="password_confirmation" required minlength="8">
        <button style="margin-top:8px">Simpan Password</button>
    </form>
</div>

<div class="card" style="max-width:520px">
    <h3 style="margin-top:0">🔔 Preferensi Notifikasi <span class="muted" style="font-size:13px">(PDF 4N: per user)</span></h3>
    <form method="POST" action="{{ route('profile.preferences') }}">
        @csrf @method('PUT')
        <label style="display:flex;gap:8px;align-items:center">
            <input type="checkbox" name="email_enabled" value="1" style="width:auto"
                {{ old('email_enabled', $user->wantsMail()) ? 'checked' : '' }}>
            Kirim salinan notifikasi ke email saya
        </label>
        <p class="muted" style="font-size:13px">Notifikasi in-app selalu aktif. Email hanya jika dicentang (dan mail server dikonfigurasi).</p>
        <button>Simpan Preferensi</button>
    </form>
</div>
@endsection
