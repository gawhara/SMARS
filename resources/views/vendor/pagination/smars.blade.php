@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="pagination-link pagination-nav disabled" aria-disabled="true">&lsaquo;</span>
        @else
            <a class="pagination-link pagination-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">&lsaquo;</a>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination-link dots">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination-link active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pagination-link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a class="pagination-link pagination-nav" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">&rsaquo;</a>
        @else
            <span class="pagination-link pagination-nav disabled" aria-disabled="true">&rsaquo;</span>
        @endif
    </nav>
@endif
