{{-- resources/views/layouts/borrower.blade.php — Shell "App" (peminjam)
     Mobile-first: appbar, kartu besar, bottom nav, tanpa rail.                --}}
@php
    $user = auth()->user();
    $nav = \App\Support\Navigation::for($user);
    $primary = \App\Support\Navigation::primary($user);
    $unread = $user->unreadNotifications()->count();

    // Navbar desktop: hanya section. "Ajukan/Scan" jadi tombol aksi, sedangkan
    // Notifikasi & Profil sudah tersedia di menu avatar — memuatnya dua kali
    // hanya membuat appbar terasa padat.
    $sections = collect($nav)->flatMap(fn (array $g) => $g['items'])
        ->reject(fn (array $i) => in_array($i['route'], [
            'my.reservations.create', 'scan', 'notifications.index', 'profile.index',
        ], true))
        ->values();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="shell--borrower">
<div class="app app--stack">
    <div class="app__main">
        <header class="appbar">
            <button class="icon-btn appbar__burger" type="button" data-rail-open aria-label="Buka menu">
                <x-icon name="menu" />
            </button>

            <a class="appbar__brand" href="{{ route('dashboard') }}">
                <x-logo :size="30" />
            </a>

            <div class="appbar__body">
                <b>{{ \Illuminate\Support\Str::limit($user->name, 26) }}</b>
                <span>{{ $user->roleLabel() }}@if ($user->organization) · {{ $user->organization->name }}@endif</span>
            </div>

            <nav class="appbar__nav" aria-label="Menu utama">
                @foreach ($sections as $item)
                    <a href="{{ $item['url'] }}" @class(['is-active' => request()->routeIs($item['match'])])>
                        <x-icon :name="$item['icon']" /> {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="appbar__actions">
                @can('reservation.create')
                    <a class="btn btn--primary hide-sm" href="{{ route('my.reservations.create') }}">
                        <x-icon name="plus" /> Ajukan
                    </a>
                @endcan
                @include('partials.user-menu', ['id' => 'top', 'compact' => true])
            </div>
        </header>

        <main class="app__body">
            @include('partials.flash')
            @yield('content')
        </main>

        @include('partials.bottomnav')
    </div>
</div>

@can('reservation.create')
    <a class="fab" href="{{ route('my.reservations.create') }}" aria-label="Ajukan reservasi baru">
        <x-icon name="plus" />
    </a>
@endcan

@include('partials.mobile-rail', ['groups' => $nav, 'subtitle' => 'Manage. Reserve. Maintain.'])
@stack('scripts')
</body>
</html>
