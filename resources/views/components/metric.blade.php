{{-- resources/views/components/metric.blade.php — bar meter ringkas --}}
@props(['label', 'value' => 0, 'max' => 0, 'suffix' => '', 'tone' => 'brand', 'delay' => 0])
@php
    $max = (float) max((float) $max, 1);
    $pct = max(0, min(100, round((float) $value / $max * 100)));
@endphp
<div class="bar-row" style="--d:{{ $delay }}ms">
    <span class="bar-row__label">{{ $label }}</span>
    <span class="meter meter--{{ $tone === 'brand' ? 'accent' : $tone }}">
        <span class="meter__fill" style="--w:{{ $pct }}%;--d:{{ $delay }}ms"></span>
    </span>
    <span class="bar-row__value">{{ number_format((float) $value, 0, ',', '.') }}{{ $suffix }}</span>
</div>
