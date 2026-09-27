{{-- resources/views/layouts/borrower.blade.php — Shell "App" (peminjam)
     Desktop : appbar simetris (brand | nav tengah | actions) tanpa burger/FAB.
     Mobile  : burger + drawer, nama user di tengah, bottom nav + FAB. --}}
@php
    $user = auth()->user();
    $nav = \App\Support\Navigation::for($user);
    $primary = \App\Support\Navigation::primary($user);
    $unread = $user->unreadNotifications()->count();

    // Navbar desktop: hanya section. Ajukan/Scan = tombol aksi;
    // Notifikasi & Profil tinggal di menu avatar.
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
            <button class="icon-btn appbar__burger only-mobile" type="button"
                    data-rail-open aria-label="Buka menu">
                <x-icon name="menu" />
            </button>

            <a class="appbar__brand" href="{{ route('dashboard') }}" aria-label="Lendora — beranda">
                <x-logo :size="30" />
                <span class="appbar__brandname only-desktop">Lendora</span>
            </a>

            <span class="appbar__body only-mobile">
                <b>{{ \Illuminate\Support\Str::limit($user->name, 20) }}</b>
                <span class="tiny">{{ $user->roleLabel() }}</span>
            </span>

            <nav class="appbar__nav only-desktop" aria-label="Menu utama">
                @foreach ($sections as $item)
                    <a href="{{ $item['url'] }}"
                       @class(['is-active' => request()->routeIs($item['match'])])
                       @if (request()->routeIs($item['match'])) aria-current="page" @endif>
                        <x-icon :name="$item['icon']" /> {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="appbar__actions">
                {{-- Scan sebelumnya hilang dari desktop — kembalikan sebagai aksi --}}
                @can('viewAny', \App\Models\Asset::class)
                    <a class="btn btn--ghost btn--sm only-desktop" href="{{ route('scan') }}">
                        <x-icon name="qr" /> Scan
                    </a>
                @endcan
                @can('reservation.create')
                    <a class="btn btn--primary btn--sm only-desktop" href="{{ route('my.reservations.create') }}">
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
    <a class="fab only-mobile" href="{{ route('my.reservations.create') }}"
       aria-label="Ajukan reservasi baru">
        <x-icon name="plus" />
    </a>
@endcan

@include('partials.mobile-rail', ['groups' => $nav, 'subtitle' => 'Manage. Reserve. Maintain.'])
@stack('scripts')
</body>
</html>
