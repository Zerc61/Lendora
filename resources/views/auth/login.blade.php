{{-- resources/views/auth/login.blade.php — Formulir masuk ke Lendora --}}
@extends('layouts.auth')
@section('title', 'Masuk')

@section('content')
<div class="card" style="text-align:center">
    <div class="btn-row" style="justify-content:center;margin-bottom:14px">
        <x-logo :size="40" />
    </div>
    <p class="eyebrow">Manage. Reserve. Maintain.</p>
    <h1 style="margin-top:6px">Masuk ke Lendora</h1>
    <p class="small muted" style="margin-top:6px">Gunakan email dan password yang terdaftar di sistem peminjaman aset.</p>
</div>

<form method="POST" action="{{ route('login.attempt') }}" class="card">
    @csrf

    <div class="form">
        <x-field name="email" label="Email" required>
            <x-slot:control>
                <input type="email" id="f-email" name="email" value="{{ old('email') }}"
                       placeholder="nama@organisasi.test" required autofocus autocomplete="username"
                       @error('email') aria-invalid="true" @enderror>
            </x-slot:control>
        </x-field>

        <x-field name="password" label="Password" required>
            <x-slot:control>
                <input type="password" id="f-password" name="password" required autocomplete="current-password">
            </x-slot:control>
        </x-field>

        <label class="check" for="remember">
            <input type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            <span>
                Ingat saya
                <small>Jangan centang di perangkat bersama. Sesi akan bertahan setelah browser ditutup.</small>
            </span>
        </label>

        <button type="submit" class="btn btn--primary btn--lg btn--block">
            <x-icon name="logout" /> Masuk
        </button>
    </div>
</form>

<p class="tiny dim" style="text-align:center;margin-top:14px">
    &copy; {{ date('Y') }} Lendora · Sistem Peminjaman Aset
</p>
@endsection
