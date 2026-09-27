{{-- resources/views/components/page-head.blade.php --}}
@props(['title', 'subtitle' => null, 'crumbs' => [], 'back' => null])
<div class="page-head">
    <div class="page-head__text">
        @if ($back)
            <a href="{{ $back }}" class="crumbs">
                <x-icon name="arrow-left" /> Kembali
            </a>
        @elseif (count($crumbs))
            <nav class="crumbs" aria-label="Breadcrumb">
                @foreach ($crumbs as $label => $url)
                    @if ($url && ! $loop->last)
                        <a href="{{ $url }}">{{ $label }}</a>
                        <x-icon name="chevron-right" />
                    @else
                        <span class="strong" style="color:var(--muted)">{{ $label }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if (! $slot->isEmpty())
        <div class="page-head__actions">{{ $slot }}</div>
    @endif
</div>
