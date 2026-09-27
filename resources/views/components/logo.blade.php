{{-- resources/views/components/logo.blade.php — mark Lendora (dari brand kit) --}}
@props(['size' => 32])
<svg viewBox="0 0 256 256" width="{{ $size }}" height="{{ $size }}" aria-hidden="true" focusable="false" {{ $attributes }}>
    <defs>
        <linearGradient id="lg-{{ $size }}" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#8C70FF"/>
            <stop offset="1" stop-color="#6246D8"/>
        </linearGradient>
    </defs>
    <rect width="256" height="256" rx="58" fill="url(#lg-{{ $size }})"/>
    <path d="M85 70V174H147" fill="none" stroke="#fff" stroke-width="34" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M135 146L184 174 135 202Z" fill="#22D3EE"/>
</svg>
