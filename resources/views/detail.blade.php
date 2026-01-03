@extends('layouts.app')

@section('title', $anime->title)
@php
    $detailDescription = \Illuminate\Support\Str::limit(strip_tags($anime->synopsis), 150);
    $poster = $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png');
@endphp
@section('meta_description', $detailDescription)
@section('canonical', route('detail', $anime))
@section('og_type', 'video.tv_show')
@section('og_image', $poster)
@push('structured-data')
<script type="application/ld+json">
@php
$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'TVSeries',
    'name' => $anime->title,
    'description' => $detailDescription,
    'image' => $poster,
    'genre' => $anime->genres->pluck('name')->values(),
    'numberOfEpisodes' => $anime->episodes->count(),
    'url' => route('detail', $anime),
];

if ($anime->episodes->first()) {
    $schemaData['potentialAction'] = [
        '@type' => 'WatchAction',
        'target' => route('watch', $anime->episodes->first()),
    ];
}
@endphp
{!! json_encode($schemaData, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
    <!-- Hero Section -->
    <div class="relative min-h-screen bg-gradient-to-b from-[#1a1d24] to-[#0f1115] overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <img src="{{ $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png') }}" 
                 alt="{{ $anime->title }}"
                 class="w-full h-full object-cover bg-gray-800">
            <div class="absolute inset-0 bg-gradient-to-r from-[#0f1115] via-[#0f1115]/50 to-transparent"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 py-8 sm:py-16">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 sm:gap-12 items-start">
                <!-- Poster -->
                <div class="md:col-span-1">
                    <div class="md:sticky md:top-28 flex flex-col items-center md:items-stretch">
                        <img src="{{ $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png') }}" 
                             alt="{{ $anime->title }}"
                             class="w-48 sm:w-64 md:w-full rounded-2xl shadow-2xl shadow-black/50 border-2 border-white/10 hover:border-red-600/50 transition-all bg-gray-800">
                        @if($anime->episodes->count() > 0)
                            <a href="{{ route('watch', $anime->episodes->first()) }}" 
                               class="mt-4 sm:mt-6 w-full flex items-center justify-center px-4 sm:px-6 py-3 sm:py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-red-600/30 uppercase tracking-wide text-sm sm:text-base">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                                Tonton
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Info -->
                <div class="md:col-span-3">
                    <!-- Breadcrumb -->
                    <div class="flex items-center gap-2 mb-4 sm:mb-6 text-xs sm:text-sm text-gray-400 overflow-x-auto whitespace-nowrap pb-2">
                        <a href="{{ route('home') }}" class="hover:text-red-500 transition">Home</a>
                        <span>/</span>
                        <a href="{{ route('search') }}" class="hover:text-red-500 transition">Anime</a>
                        <span>/</span>
                        <span class="text-white font-semibold truncate max-w-[150px] sm:max-w-none">{{ $anime->title }}</span>
                    </div>

                    <!-- Title & Status -->
                    <h1 class="text-2xl sm:text-4xl md:text-5xl lg:text-6xl font-black text-white mb-4 sm:mb-6 leading-tight uppercase tracking-tight">
                        {{ $anime->title }}
                    </h1>

                    <!-- Stats Badges -->
                    <div class="flex flex-wrap gap-2 sm:gap-3 mb-6 sm:mb-8">
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
                    <div class="mb-6 sm:mb-8">
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
                    <div class="mb-8 bg-white/5 border border-white/10 rounded-2xl p-6 backdrop-blur-sm">
                        <p class="text-gray-400 text-xs font-black uppercase tracking-widest mb-4">📖 Sinopsis</p>
                        <p class="text-gray-200 leading-relaxed text-lg">{{ $anime->synopsis }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Episodes Section -->
    <div class="max-w-7xl mx-auto px-4 py-20">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
            <!-- Episodes List -->
            <div class="lg:col-span-3">
                <div class="mb-10">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-1.5 h-10 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                        <div>
                            <h2 class="text-3xl font-black text-white uppercase tracking-tight">Daftar Episode</h2>
                            <p class="text-gray-400 text-sm mt-1">{{ $anime->episodes->count() }} episode tersedia</p>
                        </div>
                    </div>
                </div>

                @if($anime->episodes->count() > 0)
                    <div class="space-y-3">
                        @foreach($anime->episodes as $episode)
                            <a href="{{ route('watch', $episode) }}" 
                               class="group flex items-center p-5 bg-gradient-to-r from-[#1a1d24] to-[#0f1115] border border-white/10 hover:border-red-600/50 rounded-2xl transition-all duration-300 hover:shadow-xl hover:shadow-red-600/20">
                                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center font-black text-white mr-5 group-hover:scale-110 transition-transform">
                                    {{ $episode->episode_number }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white font-bold text-lg group-hover:text-red-500 transition-colors">
                                        {{ $episode->title ?: "Episode " . $episode->episode_number }}
                                    </p>
                                    @if($episode->description)
                                        <p class="text-gray-400 text-sm line-clamp-1 mt-1">{{ $episode->description }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2 text-gray-400 text-sm ml-4 flex-shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                    </svg>
                                    <span class="font-semibold">{{ $episode->videoServers->count() }}</span>
                                </div>
                                <div class="ml-4 text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5.951-1.429 5.951 1.429a1 1 0 001.169-1.409l-7-14z"/>
                                    </svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16 bg-white/5 border-2 border-dashed border-white/10 rounded-2xl">
                        <div class="text-4xl mb-4">🎬</div>
                        <p class="text-gray-400 font-semibold text-lg">Episode belum tersedia</p>
                        <p class="text-gray-500 text-sm mt-2">Cek kembali nanti untuk update episode terbaru</p>

                        @auth
                            <form action="{{ route('request.store') }}" method="POST" class="mt-6 inline-flex">
                                @csrf
                                <input type="hidden" name="type" value="add_episodes">
                                <input type="hidden" name="anime_id" value="{{ $anime->id }}">
                                <input type="hidden" name="title" value="{{ $anime->title }}">
                                <input type="hidden" name="mal_url" value="">
                                <input type="hidden" name="reason" value="Tambah episode untuk {{ $anime->title }} (auto from detail)">
                                <button type="submit" class="px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-lg shadow-red-600/30">
                                    Request Tambah Episode
                                </button>
                            </form>
                        @else
                            <div class="mt-6">
                                <a href="{{ route('auth.login') }}" class="inline-flex px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-lg shadow-red-600/30">
                                    Login untuk request
                                </a>
                            </div>
                        @endauth
                    </div>
                @endif
            </div>

            <!-- Related Animes Sidebar -->
            <aside>
                <div class="sticky top-28 bg-gradient-to-br from-[#1a1d24] to-[#0f1115] border-2 border-white/10 rounded-2xl p-6 hover:border-red-600/50 transition-all">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="w-1.5 h-6 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                        <h3 class="text-xl font-black text-white uppercase tracking-tight">Anime Serupa</h3>
                    </div>
                    <div class="space-y-4">
                        @if($relatedAnimes->count() > 0)
                            @foreach($relatedAnimes as $related)
                                <a href="{{ route('detail', $related) }}" 
                                   class="block group">
                                    <div class="relative h-40 bg-[#0f1115] rounded-xl overflow-hidden mb-3 border border-white/10 group-hover:border-red-600/50 transition-all shadow-lg">
                                        <img src="{{ asset('storage/' . $related->poster_image) }}" 
                                             alt="{{ $related->title }}"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <div class="absolute top-2 left-2 bg-red-600 text-[10px] font-black px-2 py-1 rounded text-white">
                                            {{ $related->type }}
                                        </div>
                                    </div>
                                    <p class="text-white font-bold text-sm group-hover:text-red-500 transition line-clamp-2">{{ $related->title }}</p>
                                    <p class="text-yellow-500 text-sm font-black mt-1">⭐ {{ number_format($related->rating, 1) }}</p>
                                </a>
                            @endforeach
                        @else
                            <p class="text-gray-400 text-sm text-center py-8">Tidak ada anime serupa</p>
                        @endif
                    </div>
                </div>
            </aside>

            <!-- Comments Section -->
            <div class="lg:col-span-3 mt-16">
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-1.5 h-10 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                        <div>
                            <h2 class="text-3xl font-black text-white uppercase tracking-tight">Komentar</h2>
                            <p class="text-gray-400 text-sm mt-1">{{ $comments->total() }} komentar</p>
                        </div>
                    </div>
                </div>

                @auth
                    <!-- Comment Form -->
                    <div class="mb-8 bg-gradient-to-br from-[#1a1d24] to-[#0f1115] border border-white/10 rounded-2xl p-6">
                        <form action="{{ route('comments.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="anime_id" value="{{ $anime->id }}">
                            <div>
                                <textarea name="comment" 
                                          rows="4" 
                                          placeholder="Tulis komentar kamu di sini..." 
                                          class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-red-600 focus:ring-2 focus:ring-red-600/50 focus:outline-none transition-all"
                                          required
                                          maxlength="1000"></textarea>
                                <p class="text-gray-500 text-xs mt-2">Maksimal 1000 karakter</p>
                            </div>
                            @error('comment')
                                <p class="text-red-500 text-sm">{{ $message }}</p>
                            @enderror
                            <button type="submit" 
                                    class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-red-600/30">
                                Kirim Komentar
                            </button>
                        </form>
                    </div>
                @else
                    <div class="mb-8 bg-gradient-to-br from-yellow-500/10 to-orange-500/10 border border-yellow-500/30 rounded-2xl p-6">
                        <p class="text-yellow-400 font-semibold text-center">
                            @if(Route::has('auth.login'))
                                <a href="{{ route('auth.login') }}" class="underline hover:text-yellow-300 transition">Login</a>
                            @elseif(Route::has('filament.auth.login'))
                                <a href="{{ route('filament.auth.login') }}" class="underline hover:text-yellow-300 transition">Login</a>
                            @else
                                <a href="{{ url('/admin/login') }}" class="underline hover:text-yellow-300 transition">Login</a>
                            @endif
                            untuk memberikan komentar
                        </p>
                    </div>
                @endauth

                <!-- Comments List -->
                <div class="space-y-6">
                    @forelse($comments as $comment)
                        <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] border border-white/10 rounded-2xl p-6">
                            <!-- Parent Comment -->
                            <div class="flex gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center font-black text-white text-lg">
                                        {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="font-bold text-white">{{ $comment->user->name }}</span>
                                        <span class="text-gray-500 text-sm">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-gray-200 leading-relaxed">{{ $comment->comment }}</p>
                                    
                                    <div class="flex items-center gap-4 mt-3">
                                        @auth
                                            <button onclick="toggleReplyForm({{ $comment->id }})" 
                                                    class="text-red-500 hover:text-red-400 text-sm font-semibold transition">
                                                Balas
                                            </button>
                                            @if($comment->user_id === auth()->id())
                                                <form action="{{ route('comments.destroy', $comment) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            onclick="return confirm('Hapus komentar ini?')"
                                                            class="text-gray-500 hover:text-red-500 text-sm font-semibold transition">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        @endauth
                                    </div>

                                    <!-- Reply Form (Hidden by Default) -->
                                    @auth
                                        <div id="reply-form-{{ $comment->id }}" class="hidden mt-4">
                                            <form action="{{ route('comments.store') }}" method="POST" class="space-y-3">
                                                @csrf
                                                <input type="hidden" name="anime_id" value="{{ $anime->id }}">
                                                <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                                <textarea name="comment" 
                                                          rows="3" 
                                                          placeholder="Tulis balasan..." 
                                                          class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-red-600 focus:ring-2 focus:ring-red-600/50 focus:outline-none transition-all"
                                                          required
                                                          maxlength="1000"></textarea>
                                                <div class="flex gap-2">
                                                    <button type="submit" 
                                                            class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-semibold rounded-lg transition-all text-sm">
                                                        Kirim Balasan
                                                    </button>
                                                    <button type="button" 
                                                            onclick="toggleReplyForm({{ $comment->id }})"
                                                            class="px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white font-semibold rounded-lg transition-all text-sm">
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
                                <div class="mt-6 ml-16 space-y-4 border-l-2 border-red-600/30 pl-6">
                                    @foreach($comment->replies as $reply)
                                        <div class="flex gap-4">
                                            <div class="flex-shrink-0">
                                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-600 to-gray-700 flex items-center justify-center font-bold text-white text-sm">
                                                    {{ strtoupper(substr($reply->user->name, 0, 1)) }}
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <span class="font-bold text-white text-sm">{{ $reply->user->name }}</span>
                                                    <span class="text-gray-500 text-xs">{{ $reply->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="text-gray-300 leading-relaxed text-sm">{{ $reply->comment }}</p>
                                                
                                                @auth
                                                    @if($reply->user_id === auth()->id())
                                                        <form action="{{ route('comments.destroy', $reply) }}" method="POST" class="inline mt-2">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    onclick="return confirm('Hapus balasan ini?')"
                                                                    class="text-gray-500 hover:text-red-500 text-xs font-semibold transition">
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
                        <div class="text-center py-16 bg-white/5 border-2 border-dashed border-white/10 rounded-2xl">
                            <div class="text-4xl mb-4">💬</div>
                            <p class="text-gray-400 font-semibold text-lg">Belum ada komentar</p>
                            <p class="text-gray-500 text-sm mt-2">Jadilah yang pertama berkomentar!</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($comments->hasPages())
                    <div class="mt-8">
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
    </style>
@endsection
