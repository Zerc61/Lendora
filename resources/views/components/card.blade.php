{{-- resources/views/components/card.blade.php — kartu konten seragam
     @slot actions  tombol/aksi di kanan header                                     --}}
@props(['title' => null, 'subtitle' => null, 'icon' => null, 'flush' => false, 'delay' => null, 'tint' => false, 'hover' => false])
<section {{ $attributes->merge(['class' => 'card' . ($flush ? ' card--flush' : '') . ($tint ? ' card--tint' : '') . ($hover ? ' card--hover' : '')]) }}
         @if ($delay !== null) style="--d:{{ $delay }}ms" @endif>
    @if ($title || $subtitle || isset($actions))
        <header class="card__head" @if ($flush) style="padding:18px 18px 0;margin-bottom:12px" @endif>
            <div>
                @if ($title)
                    <h2>
                        @if ($icon) <x-icon :name="$icon" style="width:17px;height:17px;color:var(--muted)" /> @endif
                        {{ $title }}
                    </h2>
                @endif
                @if ($subtitle)<p>{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="btn-row">{{ $actions }}</div>@endisset
        </header>
    @endif

    {{ $slot }}
</section>
