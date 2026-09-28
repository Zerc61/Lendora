{{-- resources/views/partials/user-menu.blade.php — dropdown akun pengguna

     Layout admin me-include partial ini DUA kali (rail + topbar); $unread
     dihitung sekali di layout lalu diteruskan. --}}
@php
    $user = $user ?? auth()->user();
    $unread = $unread ?? \App\Support\Navigation::badges($user)['unread'];
    $menuId = 'user-menu-' . ($id ?? 'top');
@endphp
<div class="menu-anchor">
    <button type="button" class="{{ $compact ?? false ? 'icon-btn' : 'user-chip' }}" data-menu="{{ $menuId }}" aria-label="Menu akun">
        <x-avatar :user="$user" />
        @unless ($compact ?? false)
            <span class="user-chip__meta">
                <b>{{ \Illuminate\Support\Str::limit($user->name, 22) }}</b>
                <span>{{ $user->roleLabel() }}</span>
            </span>
            <x-icon name="chevron-down" style="width:14px;height:14px;color:var(--dim)" />
        @endunless
    </button>

    <div class="menu menu--right" id="{{ $menuId }}" hidden>
        <div class="menu__head">
            <b>{{ $user->name }}</b>
            <span>{{ $user->email }}</span>
        </div>
        <a class="menu__item" href="{{ route('profile.index') }}"><x-icon name="user" /> Profil Saya</a>
        <a class="menu__item" href="{{ route('notifications.index') }}">
            <x-icon name="bell" /> Notifikasi
            @if ($unread)<span class="count-badge badge tone-brand">{{ $unread }}</span>@endif
        </a>
        @can('viewAny', \App\Models\Asset::class)
            <a class="menu__item" href="{{ route('scan') }}"><x-icon name="scan" /> Scan QR</a>
        @endcan
        <div class="menu__sep"></div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="menu__item menu__item--danger"><x-icon name="logout" /> Keluar</button>
        </form>
    </div>
</div>
