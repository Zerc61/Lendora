{{-- resources/views/partials/rail-nav.blade.php — daftar menu dari App\Support\Navigation
     @props groups  array grup menu, items  array menu (satu grup, tanpa label) --}}
@php
    $groups = $groups ?? [];
    $flat = $groups === [] ? [['label' => null, 'items' => $items ?? []]] : $groups;
@endphp
<nav class="rail__scroll" aria-label="Menu utama">
    @foreach ($flat as $group)
        @if (! empty($group['items']))
            <div class="nav-group">
                @if (! empty($group['label']))
                    <div class="nav-group__label">{{ $group['label'] }}</div>
                @endif
                @foreach ($group['items'] as $item)
                    <a href="{{ $item['url'] }}"
                       class="nav-item {{ request()->routeIs($item['match']) ? 'is-active' : '' }}"
                       @if (request()->routeIs($item['match'])) aria-current="page" @endif>
                        <x-icon :name="$item['icon']" />
                        <span class="nav-item__label">{{ $item['label'] }}</span>
                        @if ($item['count'])
                            <span class="nav-item__count {{ $item['tone'] ? 'nav-item__count--' . $item['tone'] : '' }}">{{ $item['count'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    @endforeach
</nav>
