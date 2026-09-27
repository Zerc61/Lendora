{{-- resources/views/auth/login.blade.php — Formulir masuk ke Lendora
     Satu kartu tunggal: di phone brand & form menyatu agar tidak perlu
     menggulir melewati dua kartu besar. --}}
@extends('layouts.auth')
@section('title', 'Masuk')

@section('content')
<div class="auth-card">

    <header class="auth-card__head">
        <x-logo :size="40" class="auth-card__logo" />
        <h1 class="auth-card__title">Masuk ke Lendora</h1>
        <p class="auth-card__sub">Gunakan email dan password terdaftar.</p>
    </header>

    <form method="POST" action="{{ route('login.attempt') }}" class="auth-form">
        @csrf

        <div class="field">
            <label for="f-email">Email <span class="req">*</span></label>
            <input type="email" id="f-email" name="email" value="{{ old('email') }}"
                   placeholder="nama@organisasi.test" required autofocus autocomplete="username"
                   @error('email') aria-invalid="true" @enderror>
            @error('email')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="f-password">Password <span class="req">*</span></label>
            <input type="password" id="f-password" name="password" required autocomplete="current-password">
            @error('password')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        <label class="check" for="remember">
            <input type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            <span>
                Ingat saya
                <small class="hide-xs">Jangan centang di perangkat bersama.</small>
            </span>
        </label>

        <button type="submit" class="btn btn--primary btn--lg btn--block">
            <x-icon name="logout" /> Masuk
        </button>
    </form>

    <p class="auth-card__legal">&copy; {{ date('Y') }} Lendora · Sistem Peminjaman Aset</p>
</div>
@endsection
