@extends('layouts.app')
@section('title', 'Cari Anime - Filter Gratis')
@section('meta_description', 'Cari dan filter anime dari ribuan koleksi nipnime. Gratis, subtitle Indonesia, streaming berkualitas tinggi.')
@section('canonical', route('search'))

@section('content')
<div class="min-h-screen bg-gradient-to-b from-[#0f1115] via-[#0f1115] to-[#1a1d24]">
    <!-- Header Section -->
    <div class="max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-8">
        <div class="mb-6 sm:mb-10">
            <h1 class="text-2xl sm:text-4xl md:text-5xl font-black text-white mb-1 sm:mb-2 uppercase tracking-tighter">
                {{ __('app.search.title') }}
            </h1>
            <p class="text-gray-400 text-sm sm:text-lg">{{ __('app.search.subtitle') }}</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-3 sm:px-4 pb-12 sm:pb-20">
        <div class="flex flex-col lg:flex-row gap-4 sm:gap-6 lg:gap-8">
            <!-- Filter Sidebar -->
            <aside class="w-full lg:w-80 flex-shrink-0">
                <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] rounded-2xl sm:rounded-3xl p-4 sm:p-6 lg:p-8 border border-white/10 lg:sticky lg:top-28 backdrop-blur-xl shadow-2xl shadow-black/50">
                    <div class="flex items-center justify-between mb-4 sm:mb-6 lg:mb-8">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="w-1 h-6 sm:h-8 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                            <h3 class="text-lg sm:text-xl lg:text-2xl font-black text-white uppercase tracking-tight">{{ __('app.search.filter') }}</h3>
                        </div>
                        @php
                            $activeFilters = collect(['search', 'genre', 'status', 'type', 'year'])->filter(fn($f) => request()->filled($f))->count();
                        @endphp
                        @if($activeFilters > 0)
                            <span class="bg-red-600 text-white text-xs font-black px-2.5 py-1 rounded-full">{{ $activeFilters }}</span>
                        @endif
                    </div>

                    <form action="{{ route('search') }}" method="GET" class="space-y-6">
                        <!-- Search Input -->
                        <div class="group">
                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3 block group-focus-within:text-red-500 transition">🔍 {{ __('app.search.anime_title') }}</label>
                            <div class="relative">
                                <input type="text" id="searchInput" name="search" value="{{ request('search') }}" 
                                    placeholder="{{ __('app.nav.search') }}" 
                                    class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all placeholder-gray-600 autocomplete-input"
                                    autocomplete="off">
                                
                                <!-- Autocomplete dropdown -->
                                <div id="searchSuggestions" class="absolute top-full left-0 right-0 mt-2 bg-[#1a1d24] border-2 border-white/10 rounded-xl shadow-xl z-50 hidden max-h-96 overflow-y-auto">
                                    <!-- Suggestions akan diisi via JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Genre Select -->
                        <div class="group">
                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3 block group-focus-within:text-red-500 transition">🎭 {{ __('app.search.genre') }}</label>
                            <select name="genre" class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;utf8,<svg fill=\"none\" stroke=\"%23888888\" viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 14l-7 7m0 0l-7-7m7 7V3\"></path></svg>'); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;">
                                <option value="" class="bg-[#1a1d24]">{{ __('app.search.all_genre') }}</option>
                                @foreach($genres as $genre)
                                    <option value="{{ $genre->id }}" class="bg-[#1a1d24]" @selected(request('genre') == $genre->id)>{{ $genre->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Select -->
                        <div class="group">
                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3 block group-focus-within:text-red-500 transition">📊 {{ __('app.search.status') }}</label>
                            <select name="status" class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;utf8,<svg fill=\"none\" stroke=\"%23888888\" viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 14l-7 7m0 0l-7-7m7 7V3\"></path></svg>'); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;">
                                <option value="" class="bg-[#1a1d24]">{{ __('app.search.all_status') }}</option>
                                <option value="Ongoing" class="bg-[#1a1d24]" @selected(request('status') == 'Ongoing')>Ongoing</option>
                                <option value="Completed" class="bg-[#1a1d24]" @selected(request('status') == 'Completed')>Completed</option>
                            </select>
                        </div>

                        <!-- Type Select -->
                        <div class="group">
                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3 block group-focus-within:text-red-500 transition">📺 {{ __('app.search.type') }}</label>
                            <select name="type" class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;utf8,<svg fill=\"none\" stroke=\"%23888888\" viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 14l-7 7m0 0l-7-7m7 7V3\"></path></svg>'); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;">
                                <option value="" class="bg-[#1a1d24]">{{ __('app.search.all_type') }}</option>
                                <option value="TV" class="bg-[#1a1d24]" @selected(request('type') == 'TV')>TV</option>
                                <option value="Movie" class="bg-[#1a1d24]" @selected(request('type') == 'Movie')>Movie</option>
                                <option value="ONA" class="bg-[#1a1d24]" @selected(request('type') == 'ONA')>ONA</option>
                            </select>
                        </div>

                        <!-- Year Select -->
                        <div class="group">
                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3 block group-focus-within:text-red-500 transition">📅 {{ __('app.search.year') }}</label>
                            <select name="year" class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;utf8,<svg fill=\"none\" stroke=\"%23888888\" viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 14l-7 7m0 0l-7-7m7 7V3\"></path></svg>'); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;">
                                <option value="" class="bg-[#1a1d24]">{{ __('app.search.all_year') }}</option>
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" class="bg-[#1a1d24]" @selected(request('year') == $year)>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black text-sm rounded-xl transition-all duration-300 shadow-lg shadow-red-600/30 hover:shadow-xl hover:shadow-red-600/40 uppercase tracking-wider transform hover:scale-[1.02] active:scale-95">
                            ✓ {{ __('app.search.apply_filter') }}
                        </button>

                        <!-- Clear Filter -->
                        @if(request()->anyFilled(['search', 'genre', 'status', 'type', 'year', 'season']))
                            <a href="{{ route('search') }}" class="block w-full text-center py-3 border-2 border-gray-600 text-gray-400 hover:text-white hover:border-white font-bold text-xs uppercase tracking-widest rounded-xl transition-all duration-300">
                                ✕ {{ __('app.search.clear_filter') }}
                            </a>
                        @endif
                    </form>

                    <!-- Info Box -->
                    <div class="mt-8 p-4 bg-white/5 border border-white/10 rounded-xl">
                        <p class="text-xs text-gray-400 text-center">
                            <span class="text-red-500 font-bold">{{ $animes->total() ?? 0 }}</span> anime ditemukan
                        </p>
                    </div>
                </div>
            </aside>

            <!-- Results Grid -->
            <div class="flex-1">
                <div class="mb-8">
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">{{ __('app.search.results') }}</h2>
                    @if(request('search'))
                        <p class="text-gray-400 mt-2">{{ __('app.search.search_for') }}: <span class="text-red-500 font-bold">{{ request('search') }}</span></p>
                        <p class="text-xs text-gray-500 mt-1">💡 {{ __('app.search.typo_support') }}</p>
                    @endif
                </div>

                {{-- "Apakah maksud Anda..." suggestion --}}
                @if(isset($didYouMean) && $didYouMean && request('search'))
                    <div class="mb-6 p-4 bg-gradient-to-r from-yellow-500/10 to-orange-500/10 border border-yellow-500/30 rounded-xl">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-yellow-200">
                                    <span class="font-medium">{{ __('app.search.did_you_mean') }}:</span>
                                    <a href="{{ route('search', ['search' => $didYouMean->title]) }}" 
                                       class="ml-2 text-yellow-400 hover:text-yellow-300 font-bold underline underline-offset-2 decoration-yellow-400/50 hover:decoration-yellow-300 transition-colors">
                                        {{ $didYouMean->title }}
                                    </a>
                                    <span class="text-yellow-200/60 ml-1">?</span>
                                </p>
                            </div>
                            <a href="{{ route('detail', $didYouMean) }}" 
                               class="flex-shrink-0 px-4 py-2 bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-400 text-xs font-bold rounded-lg transition-colors border border-yellow-500/30">
                                {{ __('app.common.details') }}
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Info jika menggunakan fuzzy search --}}
                @if(isset($usedFuzzySearch) && $usedFuzzySearch && $animes->count() > 0)
                    <div class="mb-6 p-3 bg-blue-500/10 border border-blue-500/30 rounded-xl">
                        <p class="text-sm text-blue-300 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Menampilkan hasil pencarian mirip untuk "<strong>{{ request('search') }}</strong>"
                        </p>
                    </div>
                @endif
                
                @if($animes->count() > 0)
                    <div class="grid grid-cols-2 xs:grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-2 xs:gap-3 sm:gap-4 lg:gap-5 xl:gap-6 mb-8 sm:mb-12">
                        @foreach($animes as $anime)
                            @php $shouldBlurSearch = $anime->shouldBlurPoster(); @endphp
                            <a href="{{ $shouldBlurSearch ? '#' : route('detail', $anime) }}" class="group" @if($shouldBlurSearch) onclick="event.preventDefault(); alert('Konten 18+ - Anda harus login dan berusia minimal 18 tahun untuk mengakses.')" @endif>
                                <div class="relative overflow-hidden rounded-xl sm:rounded-2xl bg-[#1a1d24] border border-white/10 group-hover:border-red-600/50 group-active:border-red-600/50 transition-all duration-300">
                                    <!-- Image Container -->
                                    <div class="aspect-[3/4] overflow-hidden relative">
                                        <img src="{{ $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png') }}" 
                                             alt="{{ $anime->title }}"
                                             class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500 bg-gray-800"
                                             style="{{ $shouldBlurSearch ? 'filter: blur(20px); transform: scale(1.1);' : '' }}">
                                        
                                        @if($shouldBlurSearch)
                                        <!-- Adult Content Overlay -->
                                        <div class="absolute inset-0 flex flex-col items-center justify-center bg-black/50 text-white z-10">
                                            <span class="text-3xl font-black text-red-500">18+</span>
                                            <span class="text-xs mt-1">Konten Dewasa</span>
                                        </div>
                                        @endif
                                        
                                        <!-- Overlay Gradient -->
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                        
                                        <!-- Type Badge -->
                                        <div class="absolute top-2 sm:top-3 left-2 sm:left-3 bg-gradient-to-r from-red-600 to-red-700 text-[8px] sm:text-[10px] font-black px-2 sm:px-3 py-1 sm:py-1.5 rounded-md sm:rounded-lg shadow-lg text-white uppercase tracking-wider">
                                            {{ $anime->type }}
                                        </div>

                                        <!-- Play Icon -->
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 group-active:opacity-100 transition-all duration-300 transform scale-75 group-hover:scale-100">
                                            <div class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 lg:w-16 lg:h-16 bg-red-600 rounded-full flex items-center justify-center shadow-xl shadow-red-600/50">
                                                <svg class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7 lg:w-8 lg:h-8 text-white ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Info Section -->
                                    <div class="p-2 xs:p-2.5 sm:p-3 lg:p-4 bg-gradient-to-b from-[#1a1d24] to-[#0f1115]">
                                        <h3 class="text-[11px] xs:text-xs sm:text-sm font-bold text-white group-hover:text-red-500 transition-colors duration-300 line-clamp-2 min-h-[2rem] sm:min-h-[2.5rem]">
                                            {{ $anime->title }}
                                        </h3>
                                        <div class="flex items-center justify-between mt-1.5 sm:mt-2 lg:mt-3 pt-1.5 sm:pt-2 lg:pt-3 border-t border-white/10">
                                            <span class="text-[8px] xs:text-[9px] sm:text-[10px] text-gray-500 font-semibold">{{ $anime->release_year }}</span>
                                            <span class="text-[8px] xs:text-[9px] sm:text-[10px] text-yellow-500 font-black">★ {{ number_format($anime->rating, 1) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="flex justify-center py-6 sm:py-8 lg:py-12">
                        {{ $animes->links() }}
                    </div>
                @else
                    <!-- Empty State with suggestions -->
                    <div class="text-center py-20">
                        <div class="inline-block mb-6">
                            <div class="w-24 h-24 bg-gradient-to-br from-red-600/20 to-red-700/20 rounded-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-black text-white mb-2">Anime tidak ditemukan untuk "{{ request('search') }}"</h3>
                        <p class="text-gray-400 mb-6 max-w-md mx-auto">
                            💡 Sistem pencarian kami mendukung typo dan ejaan yang salah. 
                            Coba dengan judul berbeda atau gunakan filter kategori.
                        </p>

                        @if(isset($suggestions) && $suggestions->count() > 0)
                            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 max-w-3xl mx-auto mb-6">
                                <p class="text-sm text-gray-300 font-semibold mb-4">🎯 Mungkin yang kamu maksud:</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($suggestions as $suggestion)
                                        <a href="{{ route('detail', $suggestion) }}" class="group flex items-center gap-3 p-3 rounded-xl bg-[#0f1115] border border-white/10 hover:border-red-600/60 transition-all">
                                            <div class="w-14 h-20 rounded-lg overflow-hidden bg-gray-800 flex-shrink-0">
                                                <img src="{{ $suggestion->poster_image ? asset('storage/' . $suggestion->poster_image) : asset('images/placeholder.png') }}" alt="{{ $suggestion->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                            </div>
                                            <div class="text-left min-w-0">
                                                <p class="text-white font-bold text-sm leading-tight line-clamp-2 group-hover:text-red-500 transition-colors">{{ $suggestion->title }}</p>
                                                <div class="flex items-center gap-2 text-[11px] text-gray-400 mt-1">
                                                    <span>{{ $suggestion->release_year }}</span>
                                                    <span class="px-2 py-0.5 rounded-full bg-white/5 border border-white/10 uppercase font-black">{{ $suggestion->type }}</span>
                                                    <span class="text-yellow-400 font-black">★ {{ number_format($suggestion->rating, 1) }}</span>
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <a href="{{ route('search') }}" class="inline-block px-8 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition-all shadow-lg">
                            Lihat Semua Anime
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .grid > a {
        animation: fadeInUp 0.5s ease-out;
    }

    .grid > a:nth-child(n) {
        animation-delay: calc(0.05s * var(--index, 0));
    }

    select {
        background-size: 1.5em 1.5em;
        background-position: right 0.5rem center;
    }

    /* Autocomplete styles */
    #searchSuggestions {
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
    }

    #searchSuggestions::-webkit-scrollbar {
        width: 6px;
    }

    #searchSuggestions::-webkit-scrollbar-track {
        background: transparent;
    }

    #searchSuggestions::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    .suggestion-item {
        transition: all 0.2s;
    }

    .suggestion-item:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }

    .suggestion-item.active {
        background-color: rgba(239, 68, 68, 0.2);
        border-left: 3px solid #ef4444;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const suggestionsContainer = document.getElementById('searchSuggestions');
    let debounceTimer = null;
    let selectedIndex = -1;
    let suggestions = [];

    // Fetch suggestions from API
    function fetchSuggestions(query) {
        if (query.length < 2) {
            suggestionsContainer.classList.add('hidden');
            return;
        }

        const apiUrl = `{{ route('search.suggestions') }}?q=${encodeURIComponent(query)}`;
        console.log('Fetching suggestions from:', apiUrl);
        
        fetch(apiUrl)
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Suggestions received:', data);
                suggestions = data.suggestions;
                renderSuggestions(suggestions, query);
            })
            .catch(error => {
                console.error('Error fetching suggestions:', error);
                suggestionsContainer.classList.add('hidden');
            });
    }

    // Render suggestions dropdown
    function renderSuggestions(items, query) {
        suggestionsContainer.innerHTML = '';

        if (items.length === 0) {
            const emptyState = document.createElement('div');
            emptyState.className = 'p-4 text-center text-gray-400 text-sm';
            emptyState.innerHTML = '❌ Tidak ada hasil untuk "<strong>' + escapeHtml(query) + '</strong>"<br><span class="text-xs text-gray-500 mt-2 block">Coba dengan judul berbeda atau gunakan filter</span>';
            suggestionsContainer.appendChild(emptyState);
            suggestionsContainer.classList.remove('hidden');
            return;
        }

        items.forEach((anime, index) => {
            const suggestion = document.createElement('a');
            suggestion.href = anime.url;
            suggestion.className = 'suggestion-item flex items-center gap-3 p-3 border-b border-white/10 hover:bg-white/5 cursor-pointer block last:border-b-0 transition-all';
            suggestion.dataset.index = index;
            
            suggestion.innerHTML = `
                <div class="w-12 h-16 rounded-lg overflow-hidden bg-gray-800 flex-shrink-0">
                    <img src="${anime.poster_image}" alt="${escapeHtml(anime.title)}" class="w-full h-full object-cover">
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white font-bold text-sm leading-tight line-clamp-2 truncate">${escapeHtml(anime.title)}</p>
                    <div class="flex items-center gap-2 text-[11px] text-gray-400 mt-1">
                        <span>${anime.release_year}</span>
                        <span class="px-1.5 py-0.5 rounded bg-white/10 border border-white/20 uppercase font-black text-xs">${anime.type}</span>
                        <span class="text-yellow-400 font-black">★ ${anime.rating}</span>
                    </div>
                </div>
            `;

            suggestion.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = anime.url;
            });

            suggestionsContainer.appendChild(suggestion);
        });

        suggestionsContainer.classList.remove('hidden');
        selectedIndex = -1;
    }

    // Handle input with debounce
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        debounceTimer = setTimeout(() => {
            fetchSuggestions(query);
        }, 300);
    });

    // Keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        const items = suggestionsContainer.querySelectorAll('.suggestion-item');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSelection(items);
        } else if (e.key === 'Enter') {
            if (selectedIndex >= 0 && selectedIndex < items.length) {
                e.preventDefault();
                items[selectedIndex].click();
            }
        } else if (e.key === 'Escape') {
            suggestionsContainer.classList.add('hidden');
        }
    });

    function updateSelection(items) {
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.classList.add('hidden');
        }
    });

    // Show suggestions on focus if input has value
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            suggestionsContainer.classList.remove('hidden');
        }
    });

    // Helper function to escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
});
</script>
@endsection