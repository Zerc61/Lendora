{{-- resources/views/layouts/admin.blade.php — Shell "Konsol" (admin / super-admin)
     Rail penuh dengan grup menu, topbar dengan pencarian global & menu aksi cepat. --}}
@php
    $user = auth()->user();
    $nav = \App\Support\Navigation::for($user);
    // Hitung sekali, pakai ulang untuk seluruh partial di bawah. undistinct:
    // user-menu di-include 2x dan notification-btn sekali — sebelumnya
    // ketiganya menghitung unread sendiri (3 query identik per halaman).
    $unread = \App\Support\Navigation::badges($user)['unread'];
    $recentNotifications = $user->notifications()->latest()->limit(5)->get();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="shell--admin">
<div class="app">
    <aside class="rail">
        <a class="rail__brand" href="{{ route('dashboard') }}">
            <x-logo :size="32" />
            <span class="rail__wordmark"><b>Lendora</b><span>Ops Console</span></span>
        </a>

        @include('partials.rail-nav', ['groups' => $nav])

        <div class="rail__foot">
            @include('partials.user-menu', ['id' => 'rail', 'user' => $user, 'unread' => $unread])
        </div>
    </aside>

    <div class="app__main">
        <header class="topbar">
            <button class="icon-btn rail-toggle" type="button" data-rail-open aria-label="Buka menu">
                <x-icon name="menu" />
            </button>

            <div class="topbar__title">
                <b>@yield('chrome', 'Dashboard')</b>
                <span>{{ $user->organization?->name ?? 'Lendora Asset Operations' }}</span>
            </div>

            <div class="topbar__spacer"></div>

            @can('viewAny', \App\Models\Asset::class)
                <form class="search hide-sm" method="GET" action="{{ route('admin.assets.index') }}" role="search">
                    <x-icon name="search" />
                    <input name="search" value="{{ request('search') }}" placeholder="Cari kode, serial, tipe…" data-search-input aria-label="Cari aset" autocomplete="off">
                    <kbd>/</kbd>
                </form>
            @endcan

            @php $canCreate = auth()->user()->can('create', \App\Models\Asset::class) || auth()->user()->can('maintenance.create') || auth()->user()->can('issue.create'); @endphp
            @if ($canCreate)
                <div class="menu-anchor">
                    <button class="btn btn--primary" type="button" data-menu="quick-create">
                        <x-icon name="plus" /> <span class="hide-xs">Buat</span>
                    </button>
                    <div class="menu menu--right" id="quick-create" hidden style="min-width:236px">
                        <div class="menu__head"><b>Aksi cepat</b><span>Tambah data baru</span></div>
                        @can('create', \App\Models\Asset::class)
                            <a class="menu__item" href="{{ route('admin.assets.create') }}"><x-icon name="box" /> Aset baru</a>
                        @endcan
                        @can('maintenance.create')
                            <a class="menu__item" href="{{ route('admin.tickets.create') }}"><x-icon name="wrench" /> Tiket maintenance</a>
                        @endcan
                        @can('issue.create')
                            <a class="menu__item" href="{{ route('admin.issues.create') }}"><x-icon name="alert" /> Lapor kerusakan</a>
                        @endcan
                        @can('reservation.create')
                            <a class="menu__item" href="{{ route('my.reservations.create') }}"><x-icon name="calendar" /> Reservasi</a>
                        @endcan
                    </div>
                </div>
            @endif

            @include('partials.notification-btn', ['id' => 'top', 'unread' => $unread, 'recent' => $recentNotifications])
            @include('partials.user-menu', ['id' => 'top', 'compact' => true, 'user' => $user, 'unread' => $unread])
        </header>

        <div class="ops-strip no-print">
            <span class="ops-strip__dot"></span>
            <span>Sistem aktif · <b>{{ now()->translatedFormat('l, d F Y') }}</b></span>
            <span class="hide-sm">· {{ $user->name }} ({{ $user->roleLabel() }})</span>
            <div class="ops-strip__links">
                <a class="btn btn--ghost btn--sm" href="{{ route('admin.reports.index') }}"><x-icon name="chart" /> Laporan</a>
                <a class="btn btn--ghost btn--sm" href="{{ route('admin.audit-logs.index') }}"><x-icon name="scroll" /> Audit</a>
            </div>
        </div>

        <main class="app__body">
            @include('partials.flash')
            @yield('content')
        </main>

        @include('partials.bottomnav')
    </div>
</div>

@include('partials.mobile-rail', ['groups' => $nav, 'subtitle' => 'Ops Console'])
@stack('scripts')
</body>
</html>
