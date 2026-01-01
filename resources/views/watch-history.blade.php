@extends('layouts.app')

@section('title', 'History Nonton - NIPNIME')

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
                <h1 class="text-3xl sm:text-4xl font-black text-white uppercase tracking-tight">History Nonton</h1>
                <p class="text-gray-400 text-sm mt-1">Semua episode yang sudah kamu tonton</p>
            </div>
        </div>

        <!-- Watch History Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 mb-12">
            @forelse($watchHistory as $history)
                @php
                    $anime = $history->anime;
                    $episode = $history->episode;
                    $duration = $history->duration ?? 1440;
                    $progressPercent = $history->progress > 0 ? min(100, ($history->progress / $duration) * 100) : 0;
                @endphp
                <a href="{{ route('watch', $episode) }}" class="group block">
                    <div class="relative bg-[#1a1d24] rounded-2xl overflow-hidden border border-white/10 group-hover:border-purple-600/50 transition-all duration-300 shadow-lg">
                        <div class="relative aspect-[3/4] overflow-hidden">
                            <img src="{{ $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png') }}" 
                                 alt="{{ $anime->title }}"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110 bg-gray-800">
                            
                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            
                            <!-- Episode Badge -->
                            <div class="absolute top-3 left-3">
                                <div class="bg-gradient-to-r from-purple-600 to-purple-700 text-[10px] font-black px-3 py-1.5 rounded-lg shadow-lg text-white uppercase tracking-wider">
                                    EP {{ $episode->episode_number }}
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            @if($progressPercent > 0)
                            <div class="absolute bottom-0 left-0 right-0 h-1.5 bg-black/60">
                                <div class="h-full bg-gradient-to-r from-purple-600 to-purple-700 transition-all" 
                                     style="width: {{ $progressPercent }}%"></div>
                            </div>
                            @endif

                            <!-- Play Button -->
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-75 group-hover:scale-100">
                                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-purple-700 rounded-full flex items-center justify-center shadow-xl shadow-purple-600/50">
                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Info -->
                        <div class="p-3 sm:p-4 bg-gradient-to-b from-[#1a1d24] to-[#0f1115]">
                            <h3 class="text-white font-bold text-xs sm:text-sm line-clamp-2 group-hover:text-purple-500 transition-colors min-h-[2.5rem]">{{ $anime->title }}</h3>
                            <div class="flex items-center justify-between mt-2 sm:mt-3 pt-2 sm:pt-3 border-t border-white/10">
                                <span class="text-[9px] sm:text-[10px] text-gray-500 font-semibold">
                                    @if($history->completed)
                                        ✓ Selesai
                                    @else
                                        {{ number_format($progressPercent, 0) }}% ditonton
                                    @endif
                                </span>
                                <span class="text-[9px] sm:text-[10px] text-gray-400 font-semibold">{{ $history->last_watched_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center py-12">
                    <svg class="w-12 h-12 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-400">Belum ada history nonton</p>
                    <a href="{{ route('home') }}" class="text-purple-500 hover:text-purple-400 mt-4 inline-block">
                        Mulai nonton sekarang →
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($watchHistory->hasPages())
            <div class="flex justify-center mb-8">
                <div class="flex gap-2 flex-wrap justify-center">
                    {{-- Previous Page Link --}}
                    @if ($watchHistory->onFirstPage())
                        <span class="px-4 py-2 text-gray-600 bg-gray-800/50 rounded-lg text-sm font-semibold cursor-not-allowed">
                            ← Sebelumnya
                        </span>
                    @else
                        <a href="{{ $watchHistory->previousPageUrl() }}" class="px-4 py-2 text-white bg-purple-600 hover:bg-purple-700 rounded-lg text-sm font-semibold transition-colors">
                            ← Sebelumnya
                        </a>
                    @endif

                    {{-- Page Numbers --}}
                    @foreach ($watchHistory->getUrlRange(1, $watchHistory->lastPage()) as $page => $url)
                        @if ($page == $watchHistory->currentPage())
                            <span class="px-4 py-2 text-white bg-purple-600 rounded-lg text-sm font-semibold">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="px-4 py-2 text-gray-300 bg-gray-800/50 hover:bg-gray-800 rounded-lg text-sm font-semibold transition-colors">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($watchHistory->hasMorePages())
                        <a href="{{ $watchHistory->nextPageUrl() }}" class="px-4 py-2 text-white bg-purple-600 hover:bg-purple-700 rounded-lg text-sm font-semibold transition-colors">
                            Selanjutnya →
                        </a>
                    @else
                        <span class="px-4 py-2 text-gray-600 bg-gray-800/50 rounded-lg text-sm font-semibold cursor-not-allowed">
                            Selanjutnya →
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
