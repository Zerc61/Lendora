{{-- resources/views/auth/login.blade.php — Masuk ke Lendora
     Desktop : kartu ter-center (tampilan sebelumnya)
     Phone   : layout imersif full-screen — orb ungu, input pill, tombol pill
     Logika tombol show/hide password TIDAK inline di sini melainkan di
     public/assets/lendora.js (defer, sudah dimuat di layout auth) supaya
     form ganti-password di /profile ikut dapat fitur yang sama. --}}
@extends('layouts.auth')
@section('title', 'Masuk')

@section('content')
<div class="auth-card">
    <div class="auth-glow" aria-hidden="true"></div>

    <header class="auth-card__head">
        <x-logo :size="44" class="auth-card__logo" />
        {{-- Logo brand kit untuk phone. Desktop memakai SVG <x-logo> di atas
             (tipis, tajam di semua DPI), jadi PNG 1024px tidak perlu diunduh
             di sana. `display:none` TIDAK cukup untuk itu — browser tetap
             mengambil <img src> walau disembunyikan. Karena itu <picture>:
             di bawah 640px yang dimuat PNG, di atas itu hanya GIF transparan
             1x1 (~50 byte) sehingga desktop tidak membayar 26KB. --}}
        <picture class="auth-mark">
            <source media="(max-width: 640px)" srcset="{{ asset('assets/lendora-mark.png') }}">
            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                 alt="" width="256" height="256" decoding="async">
        </picture>
        <h1 class="auth-card__title">Masuk<span class="auth-title-ext"> ke Lendora</span></h1>
        <p class="auth-card__sub">Gunakan email dan password terdaftar.</p>
    </header>

    <form method="POST" action="{{ route('login.attempt') }}" class="auth-form">
        @csrf

        <div class="field">
            <label for="f-email">Email <span class="req">*</span></label>
            <input type="email" id="f-email" name="email" value="{{ old('email') }}"
                   placeholder="nama@organisasi.test" required autofocus
                   autocomplete="username" autocapitalize="none" spellcheck="false"
                   @error('email') aria-invalid="true" @enderror>
            @error('email')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="f-password">Password <span class="req">*</span></label>
            <div class="field__password">
                <input type="password" id="f-password" name="password" required
                       autocomplete="current-password"
                       @error('password') aria-invalid="true" @enderror>
                <button type="button" class="pw-toggle" data-pw-toggle="f-password"
                        aria-label="Tampilkan password" aria-pressed="false">
                    {{-- SVG inline: ikon ini tidak ada di set ikon Lendora, dan
                         bentuknya harus persis mata / mata-slash. --}}
                    <svg class="pw-toggle__eye" viewBox="0 0 24 24" width="18" height="18"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="pw-toggle__eye-off" viewBox="0 0 24 24" width="18" height="18"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 8 10 8a13.16 13.16 0 0 1-1.67 2.68"/>
                        <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3.5 8 10 8a9.74 9.74 0 0 0 5.39-1.61"/>
                        <line x1="2" y1="2" x2="22" y2="22"/>
                    </svg>
                </button>
            </div>
            @error('password')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        <label class="check check--card" for="remember">
            <input type="checkbox" id="remember" name="remember" value="1"
                   {{ old('remember') ? 'checked' : '' }}>
            <span class="check__box" aria-hidden="true"></span>
            <span class="check__text">
                <b>Ingat saya</b>
                <small>Jangan centang di perangkat bersama.</small>
            </span>
        </label>

        <button type="submit" class="btn btn--primary btn--lg btn--block">
            <x-icon name="logout" /> Masuk
        </button>
    </form>

    <p class="auth-card__legal">&copy; {{ date('Y') }} Lendora &middot; Sistem Peminjaman Aset</p>
</div>
@endsection
