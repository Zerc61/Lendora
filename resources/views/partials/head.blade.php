{{-- resources/views/partials/head.blade.php — meta + aset dasar

     Cache busting memakai filemtime(), bukan nomor versi manual. Sebelumnya
     `config('app.asset_version')` di-hardcode "1", jadi setiap edit CSS/JS
     tetap dilayani browser dari cache dan patch baru tidak pernah terlihat
     sampai hard refresh. filemtime berubah sendiri begitu file disentuh.

     Fallback `?? '1'` hanya menjaga agar halaman tidak 500 bila file somehow
     hilang — bukan mekanisme cache busting. --}}
@php
    $cssV = @filemtime(public_path('assets/lendora.css')) ?: '1';
    $jsV  = @filemtime(public_path('assets/lendora.js')) ?: '1';
@endphp
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0B0E14">
<meta name="description" content="Lendora — sistem peminjaman aset & maintenance. Manage. Reserve. Maintain.">

<title>@yield('title', 'Dashboard') · Lendora</title>

<link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="{{ asset('assets/lendora.css') }}?v={{ $cssV }}">
<script defer src="{{ asset('assets/lendora.js') }}?v={{ $jsV }}"></script>
@stack('head')
