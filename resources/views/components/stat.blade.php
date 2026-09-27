{{-- resources/views/components/stat.blade.php — kartu statistik dengan counter animasi --}}
@props(['label', 'value' => 0, 'hint' => null, 'tone' => 'brand', 'icon' => null, 'href' => null, 'delay' => 0])
@php
    $color = $tone === 'brand' ? 'var(--primary-2)' : "var(--{$tone})";
    $numeric = is_numeric($value);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'stat']) }} style="--tone:{{ $color }};--d:{{ $delay }}ms">
@else
    <div {{ $attributes->merge(['class' => 'stat']) }} style="--tone:{{ $color }};--d:{{ $delay }}ms">
@endif
    <span class="stat__label">{{ $label }}</span>
    <span class="stat__value" @if ($numeric) data-count="{{ $value }}" @endif>
        {{ $numeric ? number_format((float) $value, 0, ',', '.') : $value }}
    </span>
    @if ($hint)<span class="stat__hint">{!! $hint !!}</span>@endif
    @if ($icon)
        <span class="stat__icon"><x-icon :name="$icon" /></span>
    @endif
@if ($href)
    </a>
@else
    </div>
@endif
