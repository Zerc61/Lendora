{{-- resources/views/partials/head.blade.php — meta + aset dasar --}}
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
<link rel="stylesheet" href="{{ asset('assets/lendora.css') }}?v={{ config('app.asset_version', '1') }}">
<script defer src="{{ asset('assets/lendora.js') }}?v={{ config('app.asset_version', '1') }}"></script>
@stack('head')
