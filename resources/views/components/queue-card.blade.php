{{-- resources/views/components/queue-card.blade.php — kartu antrean besar (staff) --}}
@props(['label', 'value' => 0, 'icon' => 'box', 'tone' => 'muted', 'href' => null, 'hint' => null, 'delay' => 0])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'deck__card tone-' . $tone]) }} style="--d:{{ $delay }}ms">
    <span class="deck__icon"><x-icon :name="$icon" /></span>
    <span class="deck__meta">
        <b>{{ $label }}</b>
        <strong>{{ number_format((float) $value, 0, ',', '.') }}</strong>
        @if ($hint)<span>{{ $hint }}</span>@endif
    </span>
    <x-icon name="chevron-right" class="icon" />
</a>
