{{-- resources/views/layouts/staff.blade.php — Shell "Antrean Kerja" (staff)
     Rail ringkas (antrean duluan) + workbar berisi antrean langsung.          --}}
@php
    $user = auth()->user();
    $nav = \App\Support\Navigation::for($user);
    $queue = collect($nav)->flatMap(fn ($g) => $g['items'])->keyBy('route');
    $out = $queue['admin.checkout.index'] ?? null;
    $in = $queue['admin.checkin.index'] ?? null;
    $res = $queue['admin.reservations.index'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="shell--staff">
<div class="app">
    <aside class="rail">
        <a class="rail__brand" href="{{ route('dashboard') }}">
            <x-logo :size="32" />
            <span class="rail__wordmark"><b>Lendora</b><span>Front Desk</span></span>
        </a>

        @include('partials.rail-nav', ['groups' => $nav])

        <div class="rail__foot">
            @include('partials.user-menu', ['id' => 'rail'])
        </div>
    </aside>

    <div class="app__main">
        <header class="topbar">
            <button class="icon-btn rail-toggle" type="button" data-rail-open aria-label="Buka menu">
                <x-icon name="menu" />
            </button>

            <div class="topbar__title">
                <b>@yield('chrome', 'Antrean Kerja')</b>
                <span>Shift {{ now()->format('H:i') }} · {{ $user->name }}</span>
            </div>

            <div class="topbar__spacer"></div>

            @can('viewAny', \App\Models\Asset::class)
                <a class="btn btn--accent hide-xs" href="{{ route('scan') }}"><x-icon name="scan" /> Scan QR</a>
            @endcan

            @include('partials.notification-btn', ['id' => 'top'])
            @include('partials.user-menu', ['id' => 'top', 'compact' => true])
        </header>

        <nav class="workbar no-print" aria-label="Antrean kerja">
            <span class="workbar__label">Antrean</span>
            @if ($out)
                <a href="{{ $out['url'] }}" class="workchip {{ request()->routeIs($out['match']) ? 'is-active' : '' }}">
                    <x-icon name="out" /> {{ $out['label'] }} <b>{{ $out['count'] ?: 0 }}</b>
                </a>
            @endif
            @if ($in)
                <a href="{{ $in['url'] }}" class="workchip {{ request()->routeIs($in['match']) ? 'is-active' : '' }}">
                    <x-icon name="in" /> {{ $in['label'] }} <b>{{ $in['count'] ?: 0 }}</b>
                </a>
            @endif
            @if ($res)
                <a href="{{ $res['url'] }}" class="workchip {{ request()->routeIs($res['match']) ? 'is-active' : '' }} {{ $res['count'] ? 'workchip--hot' : '' }}">
                    <x-icon name="calendar" /> {{ $res['label'] }} <b>{{ $res['count'] ?: 0 }}</b>
                </a>
            @endif
            @can('issue.view')
                <a href="{{ route('admin.issues.index') }}" class="workchip {{ request()->routeIs('admin.issues.*') ? 'is-active' : '' }}">
                    <x-icon name="alert" /> Issue
                </a>
            @endcan
            <span class="topbar__spacer"></span>
            <a href="{{ route('dashboard') }}" class="workchip {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                <x-icon name="grid" /> Ringkasan
            </a>
        </nav>

        <main class="app__body">
            @include('partials.flash')
            @yield('content')
        </main>

        @include('partials.bottomnav')
    </div>
</div>

@include('partials.mobile-rail', ['groups' => $nav, 'subtitle' => 'Front Desk'])
@stack('scripts')
</body>
</html>
