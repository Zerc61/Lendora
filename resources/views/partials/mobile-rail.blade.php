{{-- resources/views/partials/mobile-rail.blade.php — drawer menu untuk layar kecil
     @props groups daftar grup menu (App\Support\Navigation::for)               --}}
@php
    $user = auth()->user();
    $groups = $groups ?? [];
    $title = $title ?? 'Menu';
@endphp
<div class="scrim" data-scrim hidden></div>
<nav class="mobile-rail" data-mobile-rail aria-label="{{ $title }}">
    <div class="rail__brand" style="justify-content:space-between">
        <a href="{{ route('dashboard') }}" class="btn-row" style="gap:10px">
            <x-logo :size="32" />
            <span class="rail__wordmark"><b>Lendora</b><span>{{ $subtitle ?? 'Manage. Reserve. Maintain.' }}</span></span>
        </a>
        <button class="icon-btn" type="button" data-rail-close aria-label="Tutup menu"><x-icon name="x" /></button>
    </div>

    @include('partials.rail-nav', ['groups' => $groups])

    <div class="rail__foot">
        <div class="user-chip" style="cursor:default">
            <x-avatar :user="$user" />
            <span class="user-chip__meta">
                <b>{{ $user->name }}</b>
                <span>{{ $user->roleLabel() }}</span>
            </span>
        </div>
        <div class="btn-row" style="margin-top:8px">
            <a class="btn btn--ghost btn--sm" href="{{ route('profile.index') }}" data-rail-close>
                <x-icon name="user" /> Profil
            </a>
            <form method="POST" action="{{ route('logout') }}" style="flex:1">
                @csrf
                <button type="submit" class="btn btn--danger btn--sm btn--block"><x-icon name="logout" /> Keluar</button>
            </form>
        </div>
    </div>
</nav>
