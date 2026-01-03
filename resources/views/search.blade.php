@extends('layouts.app')
@section('title', 'Cari Anime')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-[#0f1115] via-[#0f1115] to-[#1a1d24]">
    <!-- Header Section -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-10">
            <h1 class="text-4xl md:text-5xl font-black text-white mb-2 uppercase tracking-tighter">
                Temukan Anime Favoritmu
            </h1>
            <p class="text-gray-400 text-lg">Jelajahi ribuan anime dengan filter yang fleksibel</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 pb-20">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filter Sidebar -->
            <aside class="w-full lg:w-80 flex-shrink-0">
                <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] rounded-3xl p-8 border border-white/10 sticky top-28 backdrop-blur-xl shadow-2xl shadow-black/50">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="w-1 h-8 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                            <h3 class="text-2xl font-black text-white uppercase tracking-tight">Filter</h3>
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
                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3 block group-focus-within:text-red-500 transition">🔍 Judul Anime</label>
                            <div class="relative">
                                <input type="text" name="search" value="{{ request('search') }}" 
                                    placeholder="Cari judul..." 
                                    class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all placeholder-gray-600">
                            </div>
                        </div>

                        <!-- Genre Select -->
                        <div class="group">
                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3 block group-focus-within:text-red-500 transition">🎭 Genre</label>
                            <select name="genre" class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;utf8,<svg fill=\"none\" stroke=\"%23888888\" viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 14l-7 7m0 0l-7-7m7 7V3\"></path></svg>'); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;">
                                <option value="" class="bg-[#1a1d24]">Semua Genre</option>
                                @foreach($genres as $genre)
                                    <option value="{{ $genre->id }}" class="bg-[#1a1d24]" @selected(request('genre') == $genre->id)>{{ $genre->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Select -->
                        <div class="group">
                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3 block group-focus-within:text-red-500 transition">📊 Status</label>
                            <select name="status" class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;utf8,<svg fill=\"none\" stroke=\"%23888888\" viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 14l-7 7m0 0l-7-7m7 7V3\"></path></svg>'); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;">
                                <option value="" class="bg-[#1a1d24]">Semua Status</option>
                                <option value="Ongoing" class="bg-[#1a1d24]" @selected(request('status') == 'Ongoing')>Ongoing</option>
                                <option value="Completed" class="bg-[#1a1d24]" @selected(request('status') == 'Completed')>Completed</option>
                            </select>
                        </div>

                        <!-- Type Select -->
                        <div class="group">
                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3 block group-focus-within:text-red-500 transition">📺 Tipe</label>
                            <select name="type" class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;utf8,<svg fill=\"none\" stroke=\"%23888888\" viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 14l-7 7m0 0l-7-7m7 7V3\"></path></svg>'); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;">
                                <option value="" class="bg-[#1a1d24]">Semua Tipe</option>
                                <option value="TV" class="bg-[#1a1d24]" @selected(request('type') == 'TV')>TV</option>
                                <option value="Movie" class="bg-[#1a1d24]" @selected(request('type') == 'Movie')>Movie</option>
                                <option value="ONA" class="bg-[#1a1d24]" @selected(request('type') == 'ONA')>ONA</option>
                            </select>
                        </div>

                        <!-- Year Select -->
                        <div class="group">
                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3 block group-focus-within:text-red-500 transition">📅 Tahun</label>
                            <select name="year" class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20 transition-all appearance-none cursor-pointer" style="background-image: url('data:image/svg+xml;utf8,<svg fill=\"none\" stroke=\"%23888888\" viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 14l-7 7m0 0l-7-7m7 7V3\"></path></svg>'); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;">
                                <option value="" class="bg-[#1a1d24]">Semua Tahun</option>
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" class="bg-[#1a1d24]" @selected(request('year') == $year)>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black text-sm rounded-xl transition-all duration-300 shadow-lg shadow-red-600/30 hover:shadow-xl hover:shadow-red-600/40 uppercase tracking-wider transform hover:scale-[1.02] active:scale-95">
                            ✓ Terapkan Filter
                        </button>

                        <!-- Clear Filter -->
                        @if(request()->anyFilled(['search', 'genre', 'status', 'type', 'year', 'season']))
                            <a href="{{ route('search') }}" class="block w-full text-center py-3 border-2 border-gray-600 text-gray-400 hover:text-white hover:border-white font-bold text-xs uppercase tracking-widest rounded-xl transition-all duration-300">
                                ✕ Hapus Semua Filter
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
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">Hasil Pencarian</h2>
                    @if(request('search'))
                        <p class="text-gray-400 mt-2">Pencarian: <span class="text-red-500 font-bold">{{ request('search') }}</span></p>
                    @endif
                </div>
                
                @if($animes->count() > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6 mb-12">
                        @foreach($animes as $anime)
                            <a href="{{ route('detail', $anime) }}" class="group">
                                <div class="relative overflow-hidden rounded-2xl bg-[#1a1d24] border border-white/10 group-hover:border-red-600/50 transition-all duration-300">
                                    <!-- Image Container -->
                                    <div class="aspect-[3/4] overflow-hidden">
                                        <img src="{{ $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png') }}" 
                                             alt="{{ $anime->title }}"
                                             class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500 bg-gray-800">
                                        
                                        <!-- Overlay Gradient -->
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                        
                                        <!-- Type Badge -->
                                        <div class="absolute top-3 left-3 bg-gradient-to-r from-red-600 to-red-700 text-[10px] font-black px-3 py-1.5 rounded-lg shadow-lg text-white uppercase tracking-wider">
                                            {{ $anime->type }}
                                        </div>

                                        <!-- Play Icon -->
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-75 group-hover:scale-100">
                                            <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center shadow-xl shadow-red-600/50">
                                                <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Info Section -->
                                    <div class="p-4 bg-gradient-to-b from-[#1a1d24] to-[#0f1115]">
                                        <h3 class="text-sm font-bold text-white group-hover:text-red-500 transition-colors duration-300 line-clamp-2 min-h-[2.5rem]">
                                            {{ $anime->title }}
                                        </h3>
                                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-white/10">
                                            <span class="text-[10px] text-gray-500 font-semibold">{{ $anime->release_year }}</span>
                                            <span class="text-[10px] text-yellow-500 font-black">★ {{ number_format($anime->rating, 1) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="flex justify-center py-12">
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
                        <h3 class="text-2xl font-black text-white mb-2">Anime tidak ditemukan</h3>
                        <p class="text-gray-400 mb-6 max-w-md mx-auto">Coba cek ejaan judul atau pilih dari rekomendasi berikut.</p>

                        @if(isset($suggestions) && $suggestions->count() > 0)
                            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 max-w-3xl mx-auto mb-6">
                                <p class="text-sm text-gray-300 font-semibold mb-4">Mungkin yang kamu maksud:</p>
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
</style>
@endsection