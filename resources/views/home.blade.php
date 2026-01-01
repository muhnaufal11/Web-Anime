@extends('layouts.app')

@section('title', 'nipnime - Streaming Anime Sub Indo')

@section('content')
<div class="bg-gradient-to-b from-[#0f1115] via-[#0f1115] to-[#1a1d24] min-h-screen text-gray-200 font-sans">
    
    @if($featuredAnimes->count() > 0)
    <div class="relative h-[300px] sm:h-[400px] md:h-[500px] w-full overflow-hidden group">
        <div class="absolute inset-0">
            <img src="{{ $featuredAnimes[0]->poster_image ? asset('storage/' . $featuredAnimes[0]->poster_image) : asset('images/placeholder.png') }}" 
                 alt="{{ $featuredAnimes[0]->title }}"
                 class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700 bg-gray-800">
            <div class="absolute inset-0 bg-gradient-to-r from-[#0f1115] via-[#0f1115]/70 to-[#0f1115]/30"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-[#0f1115] via-transparent to-transparent"></div>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-4 h-full flex items-center">
            <div class="max-w-3xl animate-fadeInUp">
                <div class="flex items-center gap-2 sm:gap-3 mb-3 sm:mb-6">
                    <div class="w-1 sm:w-1.5 h-6 sm:h-8 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                    <span class="px-2 sm:px-4 py-1 sm:py-2 bg-gradient-to-r from-red-600 to-red-700 text-[10px] sm:text-xs font-black rounded-full tracking-widest uppercase shadow-lg shadow-red-600/30">⭐ Spotlight</span>
                </div>
                <h1 class="text-2xl sm:text-4xl md:text-6xl lg:text-7xl font-black text-white mb-2 sm:mb-6 leading-tight drop-shadow-2xl uppercase tracking-tight line-clamp-2">{{ $featuredAnimes[0]->title }}</h1>
                <p class="text-gray-300 line-clamp-2 mb-4 sm:mb-8 text-sm sm:text-lg leading-relaxed max-w-2xl hidden sm:block">{{ $featuredAnimes[0]->synopsis }}</p>
                <div class="flex gap-2 sm:gap-4 flex-wrap">
                    <a href="{{ route('detail', $featuredAnimes[0]) }}" class="px-4 sm:px-8 py-2 sm:py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black rounded-lg sm:rounded-xl transition-all transform hover:scale-105 hover:shadow-xl hover:shadow-red-600/40 flex items-center gap-2 uppercase tracking-wide shadow-lg text-xs sm:text-base">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        <span class="hidden sm:inline">Tonton Sekarang</span>
                        <span class="sm:hidden">Tonton</span>
                    </a>
                    <a href="{{ route('detail', $featuredAnimes[0]) }}" class="px-4 sm:px-8 py-2 sm:py-4 bg-white/10 hover:bg-white/20 border border-white/30 sm:border-2 text-white font-bold rounded-lg sm:rounded-xl transition-all backdrop-blur-sm uppercase tracking-wide text-xs sm:text-base">
                        Detail
                    </a>
                </div>
                <div class="flex items-center gap-4 sm:gap-6 mt-4 sm:mt-8 pt-4 sm:pt-8 border-t border-white/10 hidden sm:flex">
                    <div>
                        <span class="text-xl sm:text-3xl font-black text-red-500">★</span>
                        <p class="text-xs sm:text-sm text-gray-400">{{ number_format($featuredAnimes[0]->rating, 1) }}/10</p>
                    </div>
                    <div>
                        <span class="text-lg sm:text-2xl font-black text-white">{{ $featuredAnimes[0]->release_year }}</span>
                        <p class="text-xs sm:text-sm text-gray-400">Tahun Rilis</p>
                    </div>
                    <div>
                        <span class="text-lg sm:text-2xl font-black text-white">{{ $featuredAnimes[0]->episodes->count() }}</span>
                        <p class="text-xs sm:text-sm text-gray-400">Episode</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 py-8 sm:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 sm:gap-8">
            
            <!-- Main Content -->
            <div class="lg:col-span-3">
                @auth
                    @if(isset($continueWatching) && $continueWatching->count() > 0)
                    <!-- Continue Watching Section -->
                    <div class="mb-10 sm:mb-16">
                        <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-6">
                            <div class="w-1 sm:w-1.5 h-8 sm:h-10 bg-gradient-to-b from-purple-600 to-purple-700 rounded-full"></div>
                            <div>
                                <h2 class="text-2xl sm:text-4xl font-black text-white uppercase tracking-tight">Lanjutkan Tonton</h2>
                                <p class="text-gray-400 text-xs sm:text-sm mt-1">Melanjutkan dari terakhir kali kamu nonton</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-6">
                            @foreach($continueWatching as $history)
                                @php
                                    $anime = $history->anime;
                                    $episode = $history->episode;
                                    // Calculate progress percentage accurately using duration
                                    $duration = $history->duration ?? 1440; // Default to 24 minutes if not set
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
                                        <div class="p-4 bg-gradient-to-b from-[#1a1d24] to-[#0f1115]">
                                            <h3 class="text-white font-bold text-sm line-clamp-2 group-hover:text-purple-500 transition-colors min-h-[2.5rem]">{{ $anime->title }}</h3>
                                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-white/10">
                                                <span class="text-[10px] text-gray-500 font-semibold">
                                                    @if($history->completed)
                                                        ✓ Selesai
                                                    @else
                                                        {{ number_format($progressPercent, 0) }}% ditonton
                                                    @endif
                                                </span>
                                                <span class="text-[10px] text-gray-400 font-semibold">{{ $history->last_watched_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endauth

                <!-- Section Header -->
                <div class="mb-10">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-1.5 h-10 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                        <div>
                            <h2 class="text-4xl font-black text-white uppercase tracking-tight">Episode Terbaru</h2>
                            <p class="text-gray-400 text-sm mt-1">Koleksi episode terbaru dari anime favoritmu</p>
                        </div>
                    </div>
                </div>

                <!-- Episodes Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-6">
                    @php $order = 0; @endphp
                    @foreach($latestEpisodes as $anime)
                        @foreach($anime->episodes as $episode)
                        <!-- Debug Order: {{ ++$order }}. {{ $anime->title }} -->
                        <a href="{{ route('watch', $episode) }}" class="group block">
                            <div class="relative bg-[#1a1d24] rounded-2xl overflow-hidden border border-white/10 group-hover:border-red-600/50 transition-all duration-300 shadow-lg">
                                <div class="relative aspect-[3/4] overflow-hidden">
                                    <img src="{{ $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png') }}" 
                                         alt="{{ $anime->title }}"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110 bg-gray-800">
                                    
                                    <!-- Overlay -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    
                                    <!-- Badges -->
                                    <div class="absolute top-3 left-3 flex items-center gap-2">
                                        <div class="bg-gradient-to-r from-red-600 to-red-700 text-[10px] font-black px-3 py-1.5 rounded-lg shadow-lg text-white uppercase tracking-wider">
                                            EP {{ $episode->episode_number }}
                                        </div>
                                        @if($episode->updated_at > now()->subHours(24) || $episode->created_at > now()->subHours(24))
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
                                <div class="p-4 bg-gradient-to-b from-[#1a1d24] to-[#0f1115]">
                                    <h3 class="text-white font-bold text-sm line-clamp-2 group-hover:text-red-500 transition-colors min-h-[2.5rem]">{{ $anime->title }}</h3>
                                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-white/10">
                                        <span class="text-[10px] text-gray-500 font-semibold italic">Sub Indo</span>
                                        <span class="text-[10px] text-yellow-500 font-black">★ {{ number_format($anime->rating, 1) }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="space-y-8">
                <!-- Discord Box -->
                <div class="bg-gradient-to-br from-[#5865F2]/20 to-[#4752C4]/20 border-2 border-[#5865F2]/50 rounded-3xl p-8 backdrop-blur-xl relative overflow-hidden group hover:border-[#5865F2] transition-all">
                    <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-[#5865F2]/10 rounded-full blur-3xl group-hover:scale-150 transition-transform"></div>
                    <div class="relative z-10">
                        <div class="text-4xl mb-4">💬</div>
                        <h3 class="text-2xl font-black text-white mb-3">JOIN DISCORD</h3>
                        <p class="text-gray-300 text-sm mb-6 leading-relaxed">Dapatkan update tercepat, share review, dan request anime favoritmu langsung dengan komunitas!</p>
                        <a href="#" class="inline-block w-full py-3 bg-gradient-to-r from-[#5865F2] to-[#4752C4] text-white font-bold rounded-xl hover:shadow-lg hover:shadow-[#5865F2]/30 transition-all uppercase tracking-wide text-center font-black text-sm">
                            Gabung Komunitas
                        </a>
                    </div>
                </div>

                <!-- Trending Box -->
                <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] rounded-3xl p-8 border-2 border-white/10 hover:border-red-600/50 transition-all">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-1.5 h-8 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                        <h3 class="text-2xl font-black text-white uppercase tracking-tight">Sedang Trending</h3>
                    </div>
                    <div class="space-y-4">
                        @foreach($popularAnimes as $index => $anime)
                        <a href="{{ route('detail', $anime) }}" class="flex items-center gap-4 group p-3 rounded-xl hover:bg-white/5 transition-all">
                            <div class="relative flex-shrink-0">
                                <img src="{{ $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png') }}" 
                                     alt="{{ $anime->title }}"
                                     class="w-16 h-24 object-cover rounded-lg shadow-lg group-hover:shadow-xl group-hover:shadow-red-600/20 transition-all bg-gray-700">
                                <div class="absolute -top-3 -left-3 w-8 h-8 bg-gradient-to-br from-red-600 to-red-700 text-white text-xs font-black rounded-full flex items-center justify-center border-3 border-[#1a1d24] shadow-lg">
                                    {{ $index + 1 }}
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-sm font-bold text-white group-hover:text-red-500 transition-colors line-clamp-2 leading-snug">{{ $anime->title }}</h4>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-yellow-500 font-black text-sm">★ {{ number_format($anime->rating, 1) }}</span>
                                    <span class="text-[10px] text-gray-500 uppercase font-bold">{{ $anime->type }}</span>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection