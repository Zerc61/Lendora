{{-- resources/views/layouts/technician.blade.php — Shell "Workbench" (teknisi)
     Rail ringkas (ikon + label) untuk section, workbar berisi status tiket. --}}
@php
    $user = auth()->user();
    $nav = \App\Support\Navigation::for($user);
    $statusCounts = \App\Support\Navigation::ticketStatusCounts($user);
    $ticketsUrl = route('admin.tickets.index');
    $ticketsRoute = 'admin.tickets.*';
    $statuses = [
        'open'           => ['label' => 'Menunggu', 'icon' => 'alert', 'hot' => true],
        'assigned'       => ['label' => 'Ditugaskan', 'icon' => 'clipboard', 'hot' => false],
        'in_progress'    => ['label' => 'Dikerjakan', 'icon' => 'play', 'hot' => false],
        'waiting_parts'  => ['label' => 'Menunggu Part', 'icon' => 'box', 'hot' => false],
        'completed'      => ['label' => 'Selesai', 'icon' => 'check-circle', 'hot' => false],
    ];
    $activeStatus = request('status');
    // Strip status hanya relevan di Beranda & seksi Tiket; di Aset/Issue strip
    // ini hanya menambah derau sehingga tinggi chrome ikut menyusut.
    $showStatusStrip = request()->routeIs('dashboard', 'admin.tickets.*') && $statusCounts;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="shell--technician">
<div class="app">
    <aside class="rail rail--slim">
        <a class="rail__brand" href="{{ route('dashboard') }}" aria-label="Lendora — beranda">
            <x-logo :size="32" />
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

        @if ($showStatusStrip)
            <nav class="workbar no-print" aria-label="Status pekerjaan">
                <span class="workbar__label">Status</span>
                <a href="{{ $ticketsUrl }}" class="workchip {{ request()->routeIs($ticketsRoute) && ! $activeStatus ? 'is-active' : '' }}">
                    <x-icon name="wrench" /> Semua <b>{{ array_sum($statusCounts) }}</b>
                </a>
                @foreach ($statuses as $key => $meta)
                    @php
                        $chipClass = 'workchip';
                        if ($activeStatus === $key) {
                            $chipClass .= ' is-active';
                        } elseif ($meta['hot'] && ($statusCounts[$key] ?? 0) > 0) {
                            $chipClass .= ' workchip--hot';
                        }
                    @endphp
                    <a href="{{ route('admin.tickets.index', ['status' => $key]) }}" class="{{ $chipClass }}">
                        <x-icon :name="$meta['icon']" /> {{ $meta['label'] }} <b>{{ $statusCounts[$key] ?? 0 }}</b>
                    </a>
                @endforeach
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
