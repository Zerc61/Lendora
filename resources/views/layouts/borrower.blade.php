{{-- resources/views/layouts/borrower.blade.php — Shell "App" (peminjam)
     Mobile-first: appbar, kartu besar, bottom nav, tanpa rail.                --}}
@php
    $user = auth()->user();
    $nav = \App\Support\Navigation::for($user);
    $primary = \App\Support\Navigation::primary($user);
    $unread = $user->unreadNotifications()->count();
    $menuId = 'borrower-menu';
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
            <button class="icon-btn" type="button" data-rail-open aria-label="Buka menu">
                <x-icon name="menu" />
            </button>

            <a class="appbar__brand" href="{{ route('dashboard') }}">
                <x-logo :size="30" />
            </a>

            <div class="appbar__body">
                <b>{{ \Illuminate\Support\Str::limit($user->name, 26) }}</b>
                <span>{{ $user->roleLabel() }}@if ($user->organization) · {{ $user->organization->name }}@endif</span>
            </div>

            <div class="menu-anchor">
                <button class="icon-btn" type="button" data-menu="{{ $menuId }}" aria-label="Menu lengkap">
                    <x-icon name="grid" />
                </button>
                <div class="menu menu--right" id="{{ $menuId }}" hidden style="min-width:246px">
                    <div class="menu__head"><b>Menu</b><span>Semua fitur Lendora</span></div>
                    @foreach ($nav as $group)
                        @foreach ($group['items'] as $item)
                            <a class="menu__item" href="{{ $item['url'] }}">
                                <x-icon :name="$item['icon']" /> {{ $item['label'] }}
                                @if ($item['count'])<span class="count-badge badge tone-brand">{{ $item['count'] }}</span>@endif
                            </a>
                        @endforeach
                    @endforeach
                    <div class="menu__sep"></div>
                    <a class="menu__item" href="{{ route('profile.index') }}"><x-icon name="user" /> Profil Saya</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="menu__item menu__item--danger"><x-icon name="logout" /> Keluar</button>
                    </form>
                </div>
            </div>

            <a class="icon-btn hide-sm" href="{{ route('notifications.index') }}" aria-label="Notifikasi">
                <x-icon name="bell" />
                @if ($unread)<span class="icon-btn__dot"></span>@endif
            </a>
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
