{{-- resources/views/layouts/technician.blade.php — Shell "Workbench" (teknisi)
     Tanpa rail: workbar horizontal berisi status tiket, konten dua kolom.      --}}
@php
    $user = auth()->user();
    $nav = \App\Support\Navigation::for($user);
    $mine = collect($nav)->flatMap(fn ($g) => $g['items'])->firstWhere('route', 'admin.tickets.index');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="shell--technician">
<div class="app app--stack">
    <div class="app__main">
        <header class="topbar">
            <button class="icon-btn rail-toggle" type="button" data-rail-open aria-label="Buka menu">
                <x-icon name="menu" />
            </button>

            <a class="appbar__brand hide-sm" href="{{ route('dashboard') }}">
                <x-logo :size="30" />
                <span class="rail__wordmark"><b>Lendora</b><span>Workbench</span></span>
            </a>

            <div class="topbar__title">
                <b>@yield('chrome', 'Pekerjaan Saya')</b>
                <span>{{ $user->name }} · {{ $user->roleLabel() }}</span>
            </div>

            <div class="topbar__spacer"></div>

            @can('viewAny', \App\Models\Asset::class)
                <a class="btn btn--soft hide-xs" href="{{ route('scan') }}"><x-icon name="scan" /> Scan</a>
            @endcan
            @can('maintenance.create')
                <a class="btn btn--primary" href="{{ route('admin.tickets.create') }}"><x-icon name="plus" /> <span class="hide-xs">Tiket</span></a>
            @endcan

            @include('partials.notification-btn', ['id' => 'top'])
            @include('partials.user-menu', ['id' => 'top', 'compact' => true])
        </header>

        @if ($mine)
            <nav class="workbar no-print" aria-label="Status pekerjaan">
                <span class="workbar__label">Pekerjaan</span>
                <a href="{{ $mine['url'] }}" class="workchip {{ request()->routeIs($mine['match']) && ! request('status') ? 'is-active' : '' }}">
                    <x-icon name="wrench" /> Semua <b>{{ $mine['count'] ?: 0 }}</b>
                </a>
                <a href="{{ route('admin.tickets.index', ['status' => 'open']) }}"
                   class="workchip {{ request('status') === 'open' ? 'is-active' : '' }} workchip--hot">
                    <x-icon name="alert" /> Menunggu
                </a>
                <a href="{{ route('admin.tickets.index', ['status' => 'assigned']) }}"
                   class="workchip {{ request('status') === 'assigned' ? 'is-active' : '' }}">
                    <x-icon name="clipboard" /> Ditugaskan
                </a>
                <a href="{{ route('admin.tickets.index', ['status' => 'in_progress']) }}"
                   class="workchip {{ request('status') === 'in_progress' ? 'is-active' : '' }}">
                    <x-icon name="play" /> Dikerjakan
                </a>
                <a href="{{ route('admin.tickets.index', ['status' => 'waiting_parts']) }}"
                   class="workchip {{ request('status') === 'waiting_parts' ? 'is-active' : '' }}">
                    <x-icon name="box" /> Menunggu Part
                </a>
                <a href="{{ route('admin.tickets.index', ['status' => 'completed']) }}"
                   class="workchip {{ request('status') === 'completed' ? 'is-active' : '' }} workchip--ok">
                    <x-icon name="check-circle" /> Selesai
                </a>
            </nav>
        @endif

        <main class="app__body">
            @include('partials.flash')
            @yield('content')
        </main>

        @include('partials.bottomnav')
    </div>
</div>

@include('partials.mobile-rail', ['groups' => $nav, 'subtitle' => 'Workbench'])
@stack('scripts')
</body>
</html>
