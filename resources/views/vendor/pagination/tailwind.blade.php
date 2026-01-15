@if ($paginator->hasPages())
    <div class="flex flex-col items-center gap-3 pt-6 select-none">
        <div class="text-xs text-gray-400 tracking-tight">
            Showing <span class="text-white font-semibold">{{ $paginator->firstItem() }}</span>
            to <span class="text-white font-semibold">{{ $paginator->lastItem() }}</span>
            of <span class="text-white font-semibold">{{ $paginator->total() }}</span> results
        </div>

        <nav role="navigation" aria-label="Pagination" class="flex items-center gap-1.5 bg-[#0f1115] border border-white/5 rounded-2xl px-2 py-2 shadow-lg shadow-black/40">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="flex items-center justify-center h-10 w-10 rounded-xl bg-white/5 text-gray-500 border border-white/5 cursor-not-allowed">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M12.707 15.707a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 111.414 1.414L8.414 10l4.293 4.293a1 1 0 010 1.414z" />
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="flex items-center justify-center h-10 w-10 rounded-xl bg-white/10 text-gray-200 border border-white/5 hover:border-red-500/60 hover:text-white hover:bg-red-600/80 transition-colors duration-150">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M12.707 15.707a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 111.414 1.414L8.414 10l4.293 4.293a1 1 0 010 1.414z" />
                    </svg>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- Ellipsis Separator --}}
                @if (is_string($element))
                    <span class="min-w-[2.75rem] h-10 px-3 flex items-center justify-center rounded-xl bg-white/5 text-gray-500 border border-white/5">
                        {{ $element }}
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="min-w-[2.75rem] h-10 px-3 flex items-center justify-center rounded-xl bg-gradient-to-b from-red-600 to-red-700 text-white font-semibold border border-red-500/60 shadow-lg shadow-red-600/30">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="min-w-[2.75rem] h-10 px-3 flex items-center justify-center rounded-xl bg-white/10 text-gray-200 border border-white/5 hover:border-red-500/60 hover:text-white hover:bg-red-600/80 transition-colors duration-150">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="flex items-center justify-center h-10 w-10 rounded-xl bg-white/10 text-gray-200 border border-white/5 hover:border-red-500/60 hover:text-white hover:bg-red-600/80 transition-colors duration-150">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M7.293 4.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L11.586 10 7.293 5.707a1 1 0 010-1.414z" />
                    </svg>
                </a>
            @else
                <span class="flex items-center justify-center h-10 w-10 rounded-xl bg-white/5 text-gray-500 border border-white/5 cursor-not-allowed">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M7.293 4.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L11.586 10 7.293 5.707a1 1 0 010-1.414z" />
                    </svg>
                </span>
            @endif
        </nav>
    </div>
@endif
