@extends('layouts.app')

@section('title', 'Episode Terbaru - NIPNIME')
@section('meta_description', 'Episode terbaru anime subtitle Indonesia di nipnime. Temukan rilis paling baru dan lanjutkan menonton dengan mudah.')
@section('canonical', request()->fullUrl())
@if($pagination->previousPageUrl())
    @section('prev_url', $pagination->previousPageUrl())
@endif
@if($pagination->nextPageUrl())
    @section('next_url', $pagination->nextPageUrl())
@endif
@section('og_image', asset('images/logo.png'))
@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'Episode Terbaru',
    'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
    'itemListElement' => collect($latestEpisodes)->values()->map(function ($anime, $index) {
        $episode = $anime->episodes->first();
        return [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => route('watch', $episode),
            'item' => [
                '@type' => 'Episode',
                'name' => $anime->title . ' Episode ' . $episode->episode_number,
                'partOfSeries' => $anime->title,
                'episodeNumber' => $episode->episode_number,
                'url' => route('watch', $episode),
                'image' => $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png'),
            ],
        ];
    }),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-b from-[#0f1115] via-[#0a0d13] to-black">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <!-- Header with Back Button -->
        <div class="flex items-center gap-3 sm:gap-4 mb-6 sm:mb-8">
            <a href="{{ route('home') }}" class="p-1.5 sm:p-2 hover:bg-white/10 rounded-lg transition-colors flex-shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="min-w-0">
                <h1 class="text-xl xs:text-2xl sm:text-3xl md:text-4xl font-black text-white uppercase tracking-tight truncate">Episode Terbaru</h1>
                <p class="text-gray-400 text-xs sm:text-sm mt-0.5 sm:mt-1 truncate">Semua episode terbaru dari anime favorit</p>
            </div>
        </div>

        <!-- Episodes Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4 md:gap-5 lg:gap-6 mb-8 sm:mb-12" id="episodesGrid">
            @forelse($latestEpisodes as $anime)
                @php $shouldBlurLatest = $anime->shouldBlurPoster(); @endphp
            <a href="{{ $shouldBlurLatest ? '#' : route('watch', $anime->episodes->first()) }}" class="group block" data-episode-id="{{ $anime->episodes->first()->id }}" @if($shouldBlurLatest) onclick="event.preventDefault(); alert('Konten 18+ - Anda harus login dan berusia minimal 18 tahun untuk mengakses.')" @endif>
                    <div class="relative bg-[#1a1d24] rounded-2xl overflow-hidden border border-white/10 group-hover:border-red-600/50 transition-all duration-300 shadow-lg">
                        <div class="relative aspect-[3/4] overflow-hidden">
                            <img src="{{ $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png') }}" 
                                 alt="{{ $anime->title }}"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110 bg-gray-800"
                                 style="{{ $shouldBlurLatest ? 'filter: blur(20px); transform: scale(1.1);' : '' }}">
                            
                            @if($shouldBlurLatest)
                            <!-- Adult Content Overlay -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center bg-black/50 text-white z-10">
                                <span class="text-3xl font-black text-red-500">18+</span>
                                <span class="text-xs mt-1">Konten Dewasa</span>
                            </div>
                            @endif
                            
                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            
                            <!-- Badges -->
                            <div class="absolute top-2 sm:top-3 left-2 sm:left-3 flex items-center gap-1 sm:gap-2">
                                <div class="bg-gradient-to-r from-red-600 to-red-700 text-[8px] sm:text-[10px] font-black px-2 sm:px-3 py-1 sm:py-1.5 rounded-md sm:rounded-lg shadow-lg text-white uppercase tracking-wider">
                                    EP {{ $anime->episodes->first()->episode_number }}
                                </div>
                                @if($anime->episodes->first()->updated_at > now()->subHours(24) || $anime->episodes->first()->created_at > now()->subHours(24))
                                    <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-[8px] sm:text-[10px] font-black px-1.5 sm:px-2.5 py-1 sm:py-1.5 rounded-md sm:rounded-lg shadow-lg text-white uppercase tracking-wider animate-pulse">
                                        <span class="hidden sm:inline">🆕 NEW</span>
                                        <span class="sm:hidden">NEW</span>
                                    </div>
                                @endif
                            </div>
                            <div class="absolute top-2 sm:top-3 right-2 sm:right-3 bg-black/60 backdrop-blur-md text-[8px] sm:text-[10px] font-bold px-2 sm:px-3 py-1 sm:py-1.5 rounded-md sm:rounded-lg border border-white/10 text-white">
                                {{ $anime->type }}
                            </div>

                            <!-- Play Button -->
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 group-active:opacity-100 transition-all duration-300 transform scale-75 group-hover:scale-100">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 lg:w-16 lg:h-16 bg-gradient-to-br from-red-600 to-red-700 rounded-full flex items-center justify-center shadow-xl shadow-red-600/50">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7 lg:w-8 lg:h-8 text-white ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="p-2 xs:p-2.5 sm:p-3 md:p-4 bg-gradient-to-b from-[#1a1d24] to-[#0f1115]">
                            <h3 class="text-white font-bold text-[11px] xs:text-xs sm:text-sm line-clamp-2 group-hover:text-red-500 transition-colors min-h-[2rem] sm:min-h-[2.5rem]">{{ $anime->title }}</h3>
                            <div class="flex items-center justify-between mt-1.5 sm:mt-2 md:mt-3 pt-1.5 sm:pt-2 md:pt-3 border-t border-white/10">
                                <span class="text-[8px] xs:text-[9px] sm:text-[10px] text-yellow-500 font-black">★ {{ $anime->rating }}</span>
                                <span class="text-[8px] xs:text-[9px] sm:text-[10px] text-gray-400 font-semibold">
                                    {{ $anime->type }}
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center py-12">
                    <svg class="w-12 h-12 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <p class="text-gray-400">Tidak ada episode terbaru</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($pagination->hasPages())
            <div class="flex justify-center mb-8 sm:mb-10 px-2">
                <div class="w-full max-w-5xl overflow-x-auto pagination-scroll px-1">
                    <nav class="flex flex-wrap items-center justify-center gap-1.5 sm:gap-2 text-xs sm:text-sm font-semibold min-w-max">
                    {{-- Previous Page Link --}}
                    @if ($pagination->onFirstPage())
                        <span class="px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-lg bg-[#111318] text-gray-600 cursor-not-allowed text-xs sm:text-sm">
                            <span class="hidden sm:inline">← Sebelumnya</span>
                            <span class="sm:hidden">←</span>
                        </span>
                    @else
                        <a href="{{ $pagination->previousPageUrl() }}" class="px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-lg bg-gray-900 text-white border border-red-500/50 hover:bg-red-600 hover:border-red-400 transition-colors text-xs sm:text-sm">
                            <span class="hidden sm:inline">← Sebelumnya</span>
                            <span class="sm:hidden">←</span>
                        </a>
                    @endif

                    {{-- Page Numbers - Show limited on mobile --}}
                    @php
                        $currentPage = $pagination->currentPage();
                        $lastPage = $pagination->lastPage();
                        $showPages = [];
                        
                        // Always show first page
                        $showPages[] = 1;
                        
                        // Pages around current
                        for ($i = max(2, $currentPage - 1); $i <= min($lastPage - 1, $currentPage + 1); $i++) {
                            $showPages[] = $i;
                        }
                        
                        // Always show last page
                        if ($lastPage > 1) $showPages[] = $lastPage;
                        
                        $showPages = array_unique($showPages);
                        sort($showPages);
                    @endphp

                    @foreach ($showPages as $index => $page)
                        @if ($index > 0 && $page - $showPages[$index - 1] > 1)
                            <span class="text-gray-500 px-1">...</span>
                        @endif
                        
                        @if ($page == $currentPage)
                            <span class="min-w-[32px] sm:min-w-[42px] text-center px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg bg-red-600 text-white shadow-lg shadow-red-600/30 text-xs sm:text-sm">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $pagination->url($page) }}" class="min-w-[32px] sm:min-w-[42px] text-center px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg bg-gray-900 text-gray-200 border border-white/10 hover:bg-gray-800 hover:text-white hover:border-red-500/40 transition-colors text-xs sm:text-sm">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($pagination->hasMorePages())
                        <a href="{{ $pagination->nextPageUrl() }}" class="px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-lg bg-red-600 text-white font-bold hover:bg-red-500 transition-colors text-xs sm:text-sm">
                            <span class="hidden sm:inline">Selanjutnya →</span>
                            <span class="sm:hidden">→</span>
                        </a>
                    @else
                        <span class="px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-lg bg-[#111318] text-gray-600 cursor-not-allowed text-xs sm:text-sm">
                            <span class="hidden sm:inline">Selanjutnya →</span>
                            <span class="sm:hidden">→</span>
                        </span>
                    @endif
                </nav>
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    .pagination-scroll {
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
    }

    .pagination-scroll::-webkit-scrollbar {
        height: 6px;
    }

    .pagination-scroll::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    .pagination-scroll nav {
        min-width: max-content;
    }
</style>

{{-- Realtime Episode Updates (only on first page) --}}
@if($pagination->currentPage() === 1)
@push('scripts')
<script>
(function() {
    var episodesGrid = document.getElementById('episodesGrid');
    if (!episodesGrid) return;
    
    var lastEpisodeIds = [];
    var pollingInterval = null;
    var POLL_INTERVAL = 5000;
    
    // Get initial episode IDs
    var cards = episodesGrid.querySelectorAll('.group[data-episode-id]');
    for (var i = 0; i < cards.length; i++) {
        var id = cards[i].getAttribute('data-episode-id');
        if (id) lastEpisodeIds.push(id);
    }

    function fetchAndUpdate() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '{{ route("episodes.latest") }}', true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.episode_ids && data.html) {
                        var hasNew = false;
                        for (var i = 0; i < data.episode_ids.length; i++) {
                            if (lastEpisodeIds.indexOf(String(data.episode_ids[i])) === -1) {
                                hasNew = true;
                                break;
                            }
                        }
                        
                        if (hasNew) {
                            updateGridSmooth(data.html, data.episode_ids);
                            showToast('Episode baru tersedia!');
                        }
                    }
                } catch (e) {}
            }
        };
        xhr.send();
    }
    
    function updateGridSmooth(html, newIds) {
        var temp = document.createElement('div');
        temp.innerHTML = html;
        var newItems = temp.querySelectorAll('.episode-card');
        
        // Fade out current
        episodesGrid.style.opacity = '0.5';
        episodesGrid.style.transition = 'opacity 0.2s';
        
        setTimeout(function() {
            episodesGrid.innerHTML = '';
            for (var i = 0; i < newItems.length; i++) {
                var clone = newItems[i].cloneNode(true);
                clone.className = clone.className.replace('episode-card', 'group block episode-card');
                clone.setAttribute('data-episode-id', newItems[i].getAttribute('data-episode-id'));
                clone.style.opacity = '0';
                clone.style.transform = 'translateY(10px)';
                episodesGrid.appendChild(clone);
            }
            
            episodesGrid.style.opacity = '1';
            
            // Animate in
            var items = episodesGrid.querySelectorAll('.episode-card');
            for (var j = 0; j < items.length; j++) {
                (function(item, delay) {
                    setTimeout(function() {
                        item.style.transition = 'all 0.3s ease';
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0)';
                    }, delay);
                })(items[j], j * 50);
            }
            
            // Update lastEpisodeIds
            lastEpisodeIds = [];
            for (var k = 0; k < newIds.length; k++) {
                lastEpisodeIds.push(String(newIds[k]));
            }
        }, 200);
    }
    
    function showToast(msg) {
        var old = document.querySelector('.realtime-toast');
        if (old) old.parentNode.removeChild(old);
        
        var t = document.createElement('div');
        t.className = 'realtime-toast';
        t.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:16px 24px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;font-weight:bold;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.3);z-index:9999;transform:translateX(120%);transition:transform 0.4s ease;';
        t.innerHTML = '<span style="margin-right:8px">📺</span>' + msg;
        document.body.appendChild(t);
        
        setTimeout(function() { t.style.transform = 'translateX(0)'; }, 10);
        setTimeout(function() {
            t.style.transform = 'translateX(120%)';
            setTimeout(function() { if(t.parentNode) t.parentNode.removeChild(t); }, 400);
        }, 4000);
    }
    
    function startPolling() {
        if (pollingInterval) return;
        pollingInterval = setInterval(fetchAndUpdate, POLL_INTERVAL);
    }
    
    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }
    
    // Start
    startPolling();
    
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else {
            startPolling();
            fetchAndUpdate();
        }
    });
    
    window.addEventListener('beforeunload', stopPolling);
})();
</script>
@endpush
@endif
@endsection

