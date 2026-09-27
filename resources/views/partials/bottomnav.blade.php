{{-- resources/views/partials/bottomnav.blade.php — navigasi bawah (mobile) dari Navigation::primary() --}}
@php $primary = \App\Support\Navigation::primary(auth()->user()); @endphp
<nav class="bottomnav" aria-label="Navigasi cepat">
    @foreach ($primary as $item)
        <a href="{{ $item['url'] }}"
           class="bottomnav__item {{ request()->routeIs($item['match']) ? 'is-active' : '' }}"
           @if (request()->routeIs($item['match'])) aria-current="page" @endif>
            <x-icon :name="$item['icon']" />
            <span class="truncate">{{ \Illuminate\Support\Str::limit($item['label'], 10) }}</span>
            @if ($item['count'])
                <span class="nav-item__count {{ $item['tone'] ? 'nav-item__count--' . $item['tone'] : '' }}">{{ $item['count'] }}</span>
            @endif
        </a>
    @endforeach
</nav>
