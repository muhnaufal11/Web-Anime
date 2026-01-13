@extends('layouts.app')
@section('title', 'Request Anime')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 sm:py-8">
    {{-- Header --}}
    <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-4xl font-black text-white mb-1 sm:mb-2">Request Anime</h1>
        <p class="text-gray-400 text-sm sm:text-base">Request anime yang belum ada atau minta tambah episode</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 sm:mb-6 bg-green-500/20 border border-green-500/50 text-green-400 px-4 py-3 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 sm:mb-6 bg-red-500/20 border border-red-500/50 text-red-400 px-4 py-3 rounded-xl text-sm">
            {{ session('error') }}
        </div>
    @endif
    @if(session('info'))
        <div class="mb-4 sm:mb-6 bg-blue-500/20 border border-blue-500/50 text-blue-400 px-4 py-3 rounded-xl text-sm">
            {{ session('info') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
        {{-- Form Request --}}
        <div class="lg:col-span-1 order-2 lg:order-1">
            <div class="bg-[#1a1d24] rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-white/5 lg:sticky lg:top-20">
                <h2 class="text-lg sm:text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Kirim Request
                </h2>

                <form action="{{ route('request.store') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    {{-- Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tipe Request</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="type" value="new_anime" checked class="text-red-600 focus:ring-red-500" onchange="toggleAnimeSelect()">
                                <span class="text-gray-300">Anime Baru</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="type" value="add_episodes" class="text-red-600 focus:ring-red-500" onchange="toggleAnimeSelect()">
                                <span class="text-gray-300">Tambah Episode</span>
                            </label>
                        </div>
                    </div>

                    {{-- Anime Select (for add_episodes) --}}
                    <div id="anime-select-wrapper" class="hidden">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Pilih Anime</label>
                        <select name="anime_id" class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:border-red-500 focus:ring-1 focus:ring-red-500">
                            <option value="">-- Pilih Anime --</option>
                            @foreach($animes as $anime)
                                <option value="{{ $anime->id }}">{{ $anime->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Judul Anime <span class="text-red-500">*</span></label>
                        <input type="text" name="title" required placeholder="Contoh: Jujutsu Kaisen Season 2" 
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        <p class="text-xs text-gray-500 mt-1">Gunakan judul sesuai MyAnimeList</p>
                    </div>

                    {{-- MAL URL --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">URL MyAnimeList</label>
                        <input type="url" name="mal_url" placeholder="https://myanimelist.net/anime/..." 
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        <p class="text-xs text-gray-500 mt-1">Opsional, tapi membantu admin</p>
                    </div>

                    {{-- Reason --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Alasan / Catatan</label>
                        <textarea name="reason" rows="3" placeholder="Anime bagus, minta ditambahin dong..." 
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-red-500 focus:ring-1 focus:ring-red-500"></textarea>
                    </div>

                    <button type="submit" class="w-full py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all">
                        Kirim Request
                    </button>
                </form>
            </div>
        </div>

        {{-- List Requests --}}
        <div class="lg:col-span-2 order-1 lg:order-2">
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                <h2 class="text-lg sm:text-xl font-bold text-white">Daftar Request</h2>
                <span class="text-gray-400 text-xs sm:text-sm">{{ $requests->total() }} request</span>
            </div>

            <div class="space-y-3 sm:space-y-4">
                @forelse($requests as $req)
                    <div class="bg-[#1a1d24] rounded-xl p-3 sm:p-5 border border-white/5 hover:border-white/10 transition-all">
                        <div class="flex gap-3 sm:gap-4">
                            {{-- Upvote --}}
                            <div class="flex flex-col items-center">
                                <form action="{{ route('request.vote', $req) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="p-1.5 sm:p-2 rounded-lg transition-all {{ $req->hasVoted(auth()->user()) ? 'bg-red-600 text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                    </button>
                                </form>
                                <span class="text-sm sm:text-lg font-bold {{ $req->upvotes > 0 ? 'text-red-500' : 'text-gray-500' }}">{{ $req->upvotes }}</span>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div>
                                    <h3 class="text-sm sm:text-lg font-bold text-white line-clamp-2">{{ $req->title }}</h3>
                                    <div class="flex flex-wrap items-center gap-1 sm:gap-2 mt-1">
                                        <span class="px-1.5 sm:px-2 py-0.5 text-[10px] sm:text-xs font-medium rounded-full 
                                            {{ $req->type === 'new_anime' ? 'bg-blue-500/20 text-blue-400' : 'bg-purple-500/20 text-purple-400' }}">
                                            {{ $req->type_label }}
                                        </span>
                                        <span class="px-1.5 sm:px-2 py-0.5 text-[10px] sm:text-xs font-medium rounded-full 
                                            @if($req->status === 'pending') bg-yellow-500/20 text-yellow-400
                                            @elseif($req->status === 'approved') bg-blue-500/20 text-blue-400
                                            @elseif($req->status === 'completed') bg-green-500/20 text-green-400
                                            @else bg-red-500/20 text-red-400
                                            @endif">
                                            {{ ucfirst($req->status) }}
                                        </span>
                                        @if($req->mal_url)
                                            <a href="{{ $req->mal_url }}" target="_blank" class="text-[10px] sm:text-xs text-blue-400 hover:underline">MAL</a>
                                        @endif
                                    </div>
                                </div>

                                @if($req->reason)
                                    <p class="text-gray-400 text-xs sm:text-sm mt-2 line-clamp-2">{{ Str::limit($req->reason, 100) }}</p>
                                @endif

                                @if($req->anime)
                                    <p class="text-gray-500 text-[10px] sm:text-xs mt-2">Untuk: <span class="text-gray-300">{{ $req->anime->title }}</span></p>
                                @endif

                                <div class="flex items-center gap-2 sm:gap-4 mt-2 sm:mt-3 text-[10px] sm:text-xs text-gray-500">
                                    <span>{{ $req->user ? $req->user->name : 'Anon' }}</span>
                                    <span>{{ $req->created_at->diffForHumans() }}</span>
                                </div>

                                @if($req->admin_notes && $req->status !== 'pending')
                                    <div class="mt-2 sm:mt-3 p-2 sm:p-3 bg-white/5 rounded-lg border-l-2 border-red-500">
                                        <p class="text-[10px] sm:text-xs text-gray-400"><span class="font-medium text-gray-300">Admin:</span> {{ $req->admin_notes }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 sm:py-12 bg-white/5 rounded-xl border-2 border-dashed border-white/10">
                        <div class="text-3xl sm:text-4xl mb-2 sm:mb-3">📝</div>
                        <p class="text-gray-400 font-medium text-sm sm:text-base">Belum ada request</p>
                        <p class="text-gray-500 text-xs sm:text-sm mt-1">Jadilah yang pertama request anime!</p>
                    </div>
                @endforelse
            </div>

            @if($requests->hasPages())
                <div class="mt-4 sm:mt-6">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function toggleAnimeSelect() {
    const type = document.querySelector('input[name="type"]:checked').value;
    const wrapper = document.getElementById('anime-select-wrapper');
    wrapper.classList.toggle('hidden', type !== 'add_episodes');
}
</script>
@endsection
