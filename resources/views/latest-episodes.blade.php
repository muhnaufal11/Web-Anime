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
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('home') }}" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl sm:text-4xl font-black text-white uppercase tracking-tight">Episode Terbaru</h1>
                <p class="text-gray-400 text-sm mt-1">Semua episode terbaru dari anime favorit</p>
            </div>
        </div>

        <!-- Episodes Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 mb-12">
            @forelse($latestEpisodes as $anime)
                @php $shouldBlurLatest = $anime->shouldBlurPoster(); @endphp
                <a href="{{ $shouldBlurLatest ? '#' : route('watch', $anime->episodes->first()) }}" class="group block" @if($shouldBlurLatest) onclick="event.preventDefault(); alert('Konten 18+ - Anda harus login dan berusia minimal 18 tahun untuk mengakses.')" @endif>
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
                            <div class="absolute top-3 left-3 flex items-center gap-2">
                                <div class="bg-gradient-to-r from-red-600 to-red-700 text-[10px] font-black px-3 py-1.5 rounded-lg shadow-lg text-white uppercase tracking-wider">
                                    EP {{ $anime->episodes->first()->episode_number }}
                                </div>
                                @if($anime->episodes->first()->updated_at > now()->subHours(24) || $anime->episodes->first()->created_at > now()->subHours(24))
                                    <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-[10px] font-black px-2.5 py-1.5 rounded-lg shadow-lg text-white uppercase tracking-wider animate-pulse">
                                        🆕 NEW
                                    </div>
                                @endif
                            </div>
                            <div class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-[10px] font-bold px-3 py-1.5 rounded-lg border border-white/10 text-white">
                                {{ $anime->type }}
                            </div>

                            <!-- Play Button -->
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-75 group-hover:scale-100">
                                <div class="w-16 h-16 bg-gradient-to-br from-red-600 to-red-700 rounded-full flex items-center justify-center shadow-xl shadow-red-600/50">
                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="p-3 sm:p-4 bg-gradient-to-b from-[#1a1d24] to-[#0f1115]">
                            <h3 class="text-white font-bold text-xs sm:text-sm line-clamp-2 group-hover:text-red-500 transition-colors min-h-[2.5rem]">{{ $anime->title }}</h3>
                            <div class="flex items-center justify-between mt-2 sm:mt-3 pt-2 sm:pt-3 border-t border-white/10">
                                <span class="text-[9px] sm:text-[10px] text-gray-500 font-semibold">{{ $anime->rating }}/10</span>
                                <span class="text-[9px] sm:text-[10px] text-gray-400 font-semibold">
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
            <div class="flex justify-center mb-10">
                <nav class="flex items-center gap-2 text-sm font-semibold">
                    {{-- Previous Page Link --}}
                    @if ($pagination->onFirstPage())
                        <span class="px-4 py-2 rounded-lg bg-[#111318] text-gray-600 cursor-not-allowed">
                            ← Sebelumnya
                        </span>
                    @else
                        <a href="{{ $pagination->previousPageUrl() }}" class="px-4 py-2 rounded-lg bg-gray-900 text-white border border-red-500/50 hover:bg-red-600 hover:border-red-400 transition-colors">
                            ← Sebelumnya
                        </a>
                    @endif

                    {{-- Page Numbers --}}
                    @foreach ($pagination->getUrlRange(1, $pagination->lastPage()) as $page => $url)
                        @if ($page == $pagination->currentPage())
                            <span class="min-w-[42px] text-center px-3 py-2 rounded-lg bg-red-600 text-white shadow-lg shadow-red-600/30">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="min-w-[42px] text-center px-3 py-2 rounded-lg bg-gray-900 text-gray-200 border border-white/10 hover:bg-gray-800 hover:text-white hover:border-red-500/40 transition-colors">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($pagination->hasMorePages())
                        <a href="{{ $pagination->nextPageUrl() }}" class="px-4 py-2 rounded-lg bg-red-600 text-white font-bold hover:bg-red-500 transition-colors">
                            Selanjutnya →
                        </a>
                    @else
                        <span class="px-4 py-2 rounded-lg bg-[#111318] text-gray-600 cursor-not-allowed">
                            Selanjutnya →
                        </span>
                    @endif
                </nav>
            </div>
        @endif
    </div>
</div>
@endsection
