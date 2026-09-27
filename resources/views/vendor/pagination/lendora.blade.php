{{-- resources/views/vendor/pagination/lendora.blade.php — pagination tanpa Tailwind --}}
@if ($paginator->hasPages())
    <nav class="btn-row" style="justify-content:space-between;margin-top:14px" role="navigation" aria-label="Navigasi halaman">
        <p class="tiny dim">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
        </p>

        <div class="pagination">
            @if ($paginator->onFirstPage())
                <span class="is-disabled" aria-hidden="true">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">‹</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="ellipsis">…</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">›</a>
            @else
                <span class="is-disabled" aria-hidden="true">›</span>
            @endif
        </div>
    </nav>
@endif
