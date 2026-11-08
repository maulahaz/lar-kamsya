@if ($paginator->total() > 0)
    <div class="pagination-wrap">
        <span class="pagination-info">
            Menampilkan <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
            dari <strong>{{ $paginator->total() }}</strong> data
        </span>

        @if ($paginator->hasPages())
            <nav class="pagination" aria-label="Navigasi halaman">
                @if ($paginator->onFirstPage())
                    <span class="page-btn" aria-disabled="true"><x-icon name="chevron-left" :size="15" /></span>
                @else
                    <a class="page-btn" href="{{ $paginator->previousPageUrl() }}" aria-label="Halaman sebelumnya">
                        <x-icon name="chevron-left" :size="15" />
                    </a>
                @endif

                @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span class="page-btn is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="page-btn" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a class="page-btn" href="{{ $paginator->nextPageUrl() }}" aria-label="Halaman berikutnya">
                        <x-icon name="chevron-right" :size="15" />
                    </a>
                @else
                    <span class="page-btn" aria-disabled="true"><x-icon name="chevron-right" :size="15" /></span>
                @endif
            </nav>
        @endif
    </div>
@endif
