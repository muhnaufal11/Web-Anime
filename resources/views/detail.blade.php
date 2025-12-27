@extends('layouts.app')

@section('title', $anime->title)

@section('content')
    <!-- Hero Section -->
    <div class="relative min-h-screen bg-gradient-to-b from-[#1a1d24] to-[#0f1115] overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <img src="{{ $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png') }}" 
                 alt="{{ $anime->title }}"
                 class="w-full h-full object-cover bg-gray-800">
            <div class="absolute inset-0 bg-gradient-to-r from-[#0f1115] via-[#0f1115]/50 to-transparent"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-2 sm:px-4 py-6 sm:py-10 md:py-16">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 sm:gap-8 md:gap-12 items-start">
                <!-- Poster -->
                <div class="md:col-span-1 w-full">
                    <div class="md:sticky md:top-28 flex flex-col items-center md:items-stretch">
                        <img src="{{ $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png') }}" 
                             alt="{{ $anime->title }}"
                             class="w-32 xs:w-40 sm:w-56 md:w-full max-w-xs rounded-2xl shadow-2xl shadow-black/50 border-2 border-white/10 hover:border-red-600/50 transition-all bg-gray-800 object-cover aspect-[3/4]">
                        @if($anime->episodes->count() > 0)
                            <a href="{{ route('watch', $anime->episodes->first()) }}" 
                               class="mt-3 sm:mt-6 w-full flex items-center justify-center px-3 sm:px-6 py-2.5 sm:py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-red-600/30 uppercase tracking-wide text-xs xs:text-sm sm:text-base">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                                Tonton
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Info -->
                <div class="md:col-span-3 w-full">
                    <!-- Breadcrumb -->
                    <div class="flex items-center gap-1 sm:gap-2 mb-3 sm:mb-6 text-xs sm:text-sm text-gray-400 overflow-x-auto whitespace-nowrap pb-2">
                        <a href="{{ route('home') }}" class="hover:text-red-500 transition">Home</a>
                        <span>/</span>
                        <a href="{{ route('search') }}" class="hover:text-red-500 transition">Anime</a>
                        <span>/</span>
                        <span class="text-white font-semibold truncate max-w-[150px] sm:max-w-none">{{ $anime->title }}</span>
                    </div>

                    <!-- Title & Status -->
                    <h1 class="text-xl xs:text-2xl sm:text-4xl md:text-5xl lg:text-6xl font-black text-white mb-3 sm:mb-6 leading-tight uppercase tracking-tight break-words">
                        {{ $anime->title }}
                    </h1>

                    <!-- Stats Badges -->
                    <div class="flex flex-wrap gap-2 sm:gap-3 mb-5 sm:mb-8">
                        <div class="px-3 sm:px-5 py-2 sm:py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-black rounded-lg sm:rounded-xl shadow-lg shadow-red-600/30 text-xs sm:text-sm uppercase tracking-wider">
                            {{ $anime->type }}
                        </div>
                        <div class="px-3 sm:px-5 py-2 sm:py-3 bg-white/10 border border-white/20 text-white font-semibold rounded-lg sm:rounded-xl backdrop-blur-sm text-xs sm:text-sm uppercase tracking-wider">
                            {{ $anime->status }}
                        </div>
                        <div class="px-3 sm:px-5 py-2 sm:py-3 bg-yellow-500/20 border border-yellow-500/50 text-yellow-400 font-black rounded-lg sm:rounded-xl text-xs sm:text-sm uppercase tracking-wider">
                            ⭐ {{ number_format($anime->rating, 1) }}
                        </div>
                        @if($anime->release_year)
                            <div class="px-3 sm:px-5 py-2 sm:py-3 bg-white/10 border border-white/20 text-gray-300 font-semibold rounded-lg sm:rounded-xl backdrop-blur-sm text-xs sm:text-sm">
                                📅 {{ $anime->release_year }}
                            </div>
                        @endif
                        @if($anime->episodes->count() > 0)
                            <div class="px-3 sm:px-5 py-2 sm:py-3 bg-white/10 border border-white/20 text-gray-300 font-semibold rounded-lg sm:rounded-xl backdrop-blur-sm text-xs sm:text-sm">
                                📺 {{ $anime->episodes->count() }} Eps
                            </div>
                        @endif
                    </div>

                    <!-- Genres -->
                    <div class="mb-5 sm:mb-8">
                        <p class="text-gray-400 text-xs font-black uppercase tracking-widest mb-2 sm:mb-3">🎭 Genre</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($anime->genres as $genre)
                                <a href="{{ route('search', ['genre' => $genre->id]) }}" 
                                   class="px-3 sm:px-4 py-1.5 sm:py-2 bg-gradient-to-r from-red-600/20 to-red-700/20 hover:from-red-600 hover:to-red-700 text-red-400 hover:text-white border border-red-600/30 hover:border-red-600 rounded-lg transition-all text-xs sm:text-sm font-bold uppercase tracking-wider">
                                    {{ $genre->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Synopsis -->
                    <div class="mb-8 bg-white/5 border border-white/10 rounded-2xl p-4 sm:p-6 backdrop-blur-sm">
                        <p class="text-gray-400 text-xs font-black uppercase tracking-widest mb-4">📖 Sinopsis</p>
                        <p class="text-gray-200 leading-relaxed text-base sm:text-lg">{{ $anime->synopsis }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Episodes Section -->
    <div class="max-w-7xl mx-auto px-2 sm:px-4 py-10 sm:py-20">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 md:gap-10">
            <!-- Episodes List -->
            <div class="lg:col-span-3 order-2 lg:order-1">
                <div class="mb-10">
                    <div class="flex items-center gap-2 sm:gap-3 mb-4">
                        <div class="w-1.5 h-10 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                        <div>
                            <h2 class="text-2xl sm:text-3xl font-black text-white uppercase tracking-tight">Daftar Episode</h2>
                            <p class="text-gray-400 text-xs sm:text-sm mt-1">{{ $anime->episodes->count() }} episode tersedia</p>
                        </div>
                    </div>
                </div>

                @if($anime->episodes->count() > 0)
                    <div class="space-y-2 sm:space-y-3">
                        @foreach($anime->episodes as $episode)
                                     <a href="{{ route('watch', $episode) }}" 
                                         class="group flex flex-col xs:flex-row items-start xs:items-center p-3 sm:p-5 bg-gradient-to-r from-[#1a1d24] to-[#0f1115] border border-white/10 hover:border-red-600/50 rounded-2xl transition-all duration-300 hover:shadow-xl hover:shadow-red-600/20">
                                          <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-lg bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center font-black text-white mb-2 xs:mb-0 xs:mr-4 sm:mr-5 group-hover:scale-110 transition-transform">
                                    {{ $episode->episode_number }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white font-bold text-base sm:text-lg group-hover:text-red-500 transition-colors">
                                        {{ $episode->title ?: "Episode " . $episode->episode_number }}
                                    </p>
                                    @if($episode->description)
                                        <p class="text-gray-400 text-xs sm:text-sm line-clamp-1 mt-1">{{ $episode->description }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2 text-gray-400 text-xs sm:text-sm ml-0 xs:ml-4 flex-shrink-0 mt-2 xs:mt-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                    </svg>
                                    <span class="font-semibold">{{ $episode->videoServers->count() }}</span>
                                </div>
                                <div class="ml-0 xs:ml-4 text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5.951-1.429 5.951 1.429a1 1 0 001.169-1.409l-7-14z"/>
                                    </svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10 sm:py-16 bg-white/5 border-2 border-dashed border-white/10 rounded-2xl">
                        <div class="text-4xl mb-4">🎬</div>
                        <p class="text-gray-400 font-semibold text-lg">Episode belum tersedia</p>
                        <p class="text-gray-500 text-sm mt-2">Cek kembali nanti untuk update episode terbaru</p>
                    </div>
                @endif
            </div>

            <!-- Related Animes Sidebar -->
            <aside class="order-1 lg:order-2 mb-10 lg:mb-0">
                <div class="lg:sticky lg:top-28 bg-gradient-to-br from-[#1a1d24] to-[#0f1115] border-2 border-white/10 rounded-2xl p-4 sm:p-6 hover:border-red-600/50 transition-all">
                    <div class="flex items-center gap-2 mb-4 sm:mb-6">
                        <div class="w-1.5 h-6 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                        <h3 class="text-xl font-black text-white uppercase tracking-tight">Anime Serupa</h3>
                    </div>
                    <div class="space-y-3 sm:space-y-4">
                        @if($relatedAnimes->count() > 0)
                            @foreach($relatedAnimes as $related)
                                <a href="{{ route('detail', $related) }}" 
                                   class="block group">
                                    <div class="relative h-32 xs:h-36 sm:h-40 bg-[#0f1115] rounded-xl overflow-hidden mb-2 sm:mb-3 border border-white/10 group-hover:border-red-600/50 transition-all shadow-lg">
                                        <img src="{{ asset('storage/' . $related->poster_image) }}" 
                                             alt="{{ $related->title }}"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <div class="absolute top-2 left-2 bg-red-600 text-[10px] font-black px-2 py-1 rounded text-white">
                                            {{ $related->type }}
                                        </div>
                                    </div>
                                    <p class="text-white font-bold text-xs sm:text-sm group-hover:text-red-500 transition line-clamp-2">{{ $related->title }}</p>
                                    <p class="text-yellow-500 text-xs sm:text-sm font-black mt-1">⭐ {{ number_format($related->rating, 1) }}</p>
                                </a>
                            @endforeach
                        @else
                            <p class="text-gray-400 text-xs sm:text-sm text-center py-6 sm:py-8">Tidak ada anime serupa</p>
                        @endif
                    </div>
                </div>
            </aside>

            <!-- Comments Section -->
            <div class="lg:col-span-3 mt-10 sm:mt-16">
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-1.5 h-10 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                        <div>
                            <h2 class="text-2xl sm:text-3xl font-black text-white uppercase tracking-tight">Komentar</h2>
                            <p class="text-gray-400 text-xs sm:text-sm mt-1">{{ $comments->total() }} komentar</p>
                        </div>
                    </div>
                </div>

                @auth
                    <!-- Comment Form -->
                    <div class="mb-8 bg-gradient-to-br from-[#1a1d24] to-[#0f1115] border border-white/10 rounded-2xl p-4 sm:p-6">
                        <form action="{{ route('comments.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="anime_id" value="{{ $anime->id }}">
                            <div>
                                <textarea name="comment" 
                                          rows="4" 
                                          placeholder="Tulis komentar kamu di sini..." 
                                          class="w-full px-3 py-2 sm:px-4 sm:py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-red-600 focus:ring-2 focus:ring-red-600/50 focus:outline-none transition-all text-xs sm:text-base"
                                          required
                                          maxlength="1000"></textarea>
                                <p class="text-gray-500 text-xs mt-2">Maksimal 1000 karakter</p>
                            </div>
                            @error('comment')
                                <p class="text-red-500 text-sm">{{ $message }}</p>
                            @enderror
                            <button type="submit" 
                                    class="px-4 sm:px-6 py-2.5 sm:py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-red-600/30 text-xs sm:text-base">
                                Kirim Komentar
                            </button>
                        </form>
                    </div>
                @else
                    @php
                        $loginUrl = \Illuminate\Support\Facades\Route::has('login')
                            ? route('login')
                            : (\Illuminate\Support\Facades\Route::has('filament.auth.login')
                                ? route('filament.auth.login')
                                : url('/admin/login'));
                    @endphp
                    <div class="mb-8 bg-gradient-to-br from-yellow-500/10 to-orange-500/10 border border-yellow-500/30 rounded-2xl p-4 sm:p-6">
                        <p class="text-yellow-400 font-semibold text-center text-xs sm:text-base">
                            <a href="{{ $loginUrl }}" class="underline hover:text-yellow-300 transition">Login</a> untuk memberikan komentar
                        </p>
                    </div>
                @endauth

                <!-- Comments List -->
                <div class="space-y-4 sm:space-y-6">
                    @forelse($comments as $comment)
                        <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] border border-white/10 rounded-2xl p-4 sm:p-6">
                            <!-- Parent Comment -->
                            <div class="flex gap-3 sm:gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center font-black text-white text-base sm:text-lg">
                                        {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-1 sm:gap-2 mb-1 sm:mb-2">
                                        <span class="font-bold text-white text-xs sm:text-base">{{ $comment->user->name }}</span>
                                        <span class="text-gray-500 text-xs sm:text-sm">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-gray-200 leading-relaxed text-xs sm:text-base">{{ $comment->comment }}</p>
                                    
                                    <div class="flex items-center gap-3 sm:gap-4 mt-2 sm:mt-3">
                                        @auth
                                            <button onclick="toggleReplyForm({{ $comment->id }})" 
                                                    class="text-red-500 hover:text-red-400 text-xs sm:text-sm font-semibold transition">
                                                Balas
                                            </button>
                                            @if($comment->user_id === auth()->id())
                                                <form action="{{ route('comments.destroy', $comment) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            onclick="return confirm('Hapus komentar ini?')"
                                                            class="text-gray-500 hover:text-red-500 text-xs sm:text-sm font-semibold transition">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        @endauth
                                    </div>

                                    <!-- Reply Form (Hidden by Default) -->
                                    @auth
                                        <div id="reply-form-{{ $comment->id }}" class="hidden mt-3 sm:mt-4">
                                            <form action="{{ route('comments.store') }}" method="POST" class="space-y-2 sm:space-y-3">
                                                @csrf
                                                <input type="hidden" name="anime_id" value="{{ $anime->id }}">
                                                <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                                <textarea name="comment" 
                                                          rows="3" 
                                                          placeholder="Tulis balasan..." 
                                                          class="w-full px-3 py-2 sm:px-4 sm:py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-red-600 focus:ring-2 focus:ring-red-600/50 focus:outline-none transition-all text-xs sm:text-base"
                                                          required
                                                          maxlength="1000"></textarea>
                                                <div class="flex gap-2">
                                                    <button type="submit" 
                                                            class="px-3 sm:px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-semibold rounded-lg transition-all text-xs sm:text-sm">
                                                        Kirim Balasan
                                                    </button>
                                                    <button type="button" 
                                                            onclick="toggleReplyForm({{ $comment->id }})"
                                                            class="px-3 sm:px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white font-semibold rounded-lg transition-all text-xs sm:text-sm">
                                                        Batal
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @endauth
                                </div>
                            </div>

                            <!-- Replies -->
                            @if($comment->replies->count() > 0)
                                <div class="mt-4 sm:mt-6 ml-6 sm:ml-16 space-y-3 sm:space-y-4 border-l-2 border-red-600/30 pl-3 sm:pl-6">
                                    @foreach($comment->replies as $reply)
                                        <div class="flex gap-3 sm:gap-4">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-gray-600 to-gray-700 flex items-center justify-center font-bold text-white text-xs sm:text-sm">
                                                    {{ strtoupper(substr($reply->user->name, 0, 1)) }}
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-center gap-1 sm:gap-2 mb-1 sm:mb-2">
                                                    <span class="font-bold text-white text-xs sm:text-sm">{{ $reply->user->name }}</span>
                                                    <span class="text-gray-500 text-[10px] sm:text-xs">{{ $reply->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="text-gray-300 leading-relaxed text-xs sm:text-sm">{{ $reply->comment }}</p>
                                                
                                                @auth
                                                    @if($reply->user_id === auth()->id())
                                                        <form action="{{ route('comments.destroy', $reply) }}" method="POST" class="inline mt-2">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    onclick="return confirm('Hapus balasan ini?')"
                                                                    class="text-gray-500 hover:text-red-500 text-[10px] sm:text-xs font-semibold transition">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endauth
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-10 sm:py-16 bg-white/5 border-2 border-dashed border-white/10 rounded-2xl">
                            <div class="text-3xl sm:text-4xl mb-3 sm:mb-4">💬</div>
                            <p class="text-gray-400 font-semibold text-base sm:text-lg">Belum ada komentar</p>
                            <p class="text-gray-500 text-xs sm:text-sm mt-2">Jadilah yang pertama berkomentar!</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($comments->hasPages())
                    <div class="mt-6 sm:mt-8">
                        {{ $comments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function toggleReplyForm(commentId) {
            const form = document.getElementById('reply-form-' + commentId);
            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
            } else {
                form.classList.add('hidden');
            }
        }
    </script>

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

        h1, h2 {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Extra responsive tweaks */
        @media (max-width: 480px) {
            .xs\:w-40 { width: 10rem !important; }
            .xs\:h-36 { height: 9rem !important; }
            .xs\:text-sm { font-size: 0.875rem !important; }
            .xs\:text-xs { font-size: 0.75rem !important; }
            .xs\:ml-4 { margin-left: 1rem !important; }
            .xs\:mr-4 { margin-right: 1rem !important; }
            .xs\:mb-0 { margin-bottom: 0 !important; }
            .xs\:mb-2 { margin-bottom: 0.5rem !important; }
            .xs\:flex-row { flex-direction: row !important; }
            .xs\:items-center { align-items: center !important; }
        }
    </style>
@endsection
