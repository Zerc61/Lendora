{{-- resources/views/components/status.blade.php — badge status seragam dari enum --}}
@props(['status', 'label' => null, 'dot' => true, 'icon' => null])
@php
    $enum = $status instanceof \BackedEnum ? $status : null;
    $tone = method_exists($enum, 'tone') ? $enum->tone() : 'muted';
    $text = $label ?? (method_exists($enum, 'label') ? $enum->label() : (is_string($status) ? ucfirst(str_replace('_', ' ', $status)) : '—'));
@endphp
<span {{ $attributes->merge(['class' => 'badge badge--dot']) }} @unless ($dot) class="badge" @endunless>
    @if ($icon)<x-icon :name="$icon" />@endif
    {{ $text }}
</span>
