@if ($paginator->hasPages())
    <nav class="winter-pagination" role="navigation" aria-label="Pagination Navigation">
        <div class="wp-info">
            Showing <strong>{{ $paginator->firstItem() }}</strong>&ndash;<strong>{{ $paginator->lastItem() }}</strong>
            of <strong>{{ $paginator->total() }}</strong> results
        </div>

        <div class="wp-links">
            @if ($paginator->onFirstPage())
                <span class="wp-btn wp-disabled" aria-disabled="true">&laquo; Prev</span>
            @else
                <a class="wp-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo; Prev</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="wp-dots">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="wp-btn wp-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="wp-btn" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="wp-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &raquo;</a>
            @else
                <span class="wp-btn wp-disabled" aria-disabled="true">Next &raquo;</span>
            @endif
        </div>
    </nav>
@endif