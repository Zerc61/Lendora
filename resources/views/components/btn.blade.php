{{-- resources/views/components/btn.blade.php — tombol seragam (link, form, atau elemen) --}}
@props([
    'href' => null,
    'variant' => 'default',   // default|primary|accent|soft|ghost|danger|ok
    'size' => null,           // sm|lg
    'icon' => null,
    'iconRight' => null,
    'block' => false,
    'confirm' => null,        // konfirmasi sebelum submit/navigasi
])
@php
    $classes = trim('btn btn--' . $variant . ($size ? ' btn--' . $size : '') . ($block ? ' btn--block' : ''));
    $tag = $href ? 'a' : 'button';
    $type = $href ? null : 'button';
@endphp
<{{ $tag }} @if($type) type="{{ $type }}" @endif @if($href) href="{{ $href }}" @endif
    @if($confirm) data-confirm="{{ $confirm }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}>
    @if ($icon)<x-icon :name="$icon" />@endif
    {{ $slot }}
    @if ($iconRight)<x-icon :name="$iconRight" />@endif
</{{ $tag }}>
