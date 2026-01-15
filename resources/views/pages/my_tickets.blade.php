@extends('layouts.app')
@section('title', 'Tiket Saya')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-[#1a1d24] to-[#0f1115] rounded-2xl p-6 border border-white/10 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z"/>
                    </svg>
                    Tiket Saya
                </h1>
                <p class="text-gray-400 mt-1">{{ $email }}</p>
            </div>
            <a href="{{ route('contact') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition-all text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Buat Tiket Baru
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-[#1a1d24] rounded-xl p-4 border border-white/10 text-center">
            <div class="text-2xl font-black text-white">{{ $tickets->count() }}</div>
            <p class="text-gray-400 text-sm">Total Tiket</p>
        </div>
        <div class="bg-[#1a1d24] rounded-xl p-4 border border-white/10 text-center">
            <div class="text-2xl font-black text-green-500">{{ $tickets->where('is_closed', false)->count() }}</div>
            <p class="text-gray-400 text-sm">Aktif</p>
        </div>
        <div class="bg-[#1a1d24] rounded-xl p-4 border border-white/10 text-center">
            <div class="text-2xl font-black text-gray-500">{{ $tickets->where('is_closed', true)->count() }}</div>
            <p class="text-gray-400 text-sm">Selesai</p>
        </div>
    </div>

    <!-- Ticket List -->
    @if($tickets->count() > 0)
        <div class="space-y-4">
            @foreach($tickets as $ticket)
                @if($ticket->view_token)
                <a href="{{ route('contact.status', $ticket->view_token) }}" 
                   class="block bg-[#1a1d24] rounded-xl p-5 border border-white/10 hover:border-red-600/50 transition-all group">
                @else
                <div class="block bg-[#1a1d24] rounded-xl p-5 border border-white/10 opacity-70">
                @endif
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-bold text-white group-hover:text-red-500 transition truncate">
                                    {{ $ticket->subject }}
                                </h3>
                                @if($ticket->is_closed)
                                    <span class="px-2 py-0.5 bg-gray-600 text-white text-xs font-bold rounded-full flex-shrink-0">
                                        Ditutup
                                    </span>
                                @elseif($ticket->reply || $ticket->replies->count() > 0)
                                    <span class="px-2 py-0.5 bg-green-600 text-white text-xs font-bold rounded-full flex-shrink-0">
                                        Dibalas
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 bg-yellow-600 text-white text-xs font-bold rounded-full flex-shrink-0">
                                        Menunggu
                                    </span>
                                @endif
                            </div>
                            <p class="text-gray-400 text-sm line-clamp-2 mb-3">{{ $ticket->message }}</p>
                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $ticket->created_at->format('d M Y H:i') }}
                                </span>
                                @php
                                    $replyCount = $ticket->replies->count() + ($ticket->reply ? 1 : 0);
                                @endphp
                                @if($replyCount > 0)
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $replyCount }} balasan
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if($ticket->view_token)
                        <div class="text-gray-500 group-hover:text-red-500 transition flex-shrink-0">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        @endif
                    </div>
                @if($ticket->view_token)
                </a>
                @else
                </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="bg-[#1a1d24] rounded-xl p-12 border border-white/10 text-center">
            <div class="text-5xl mb-4">📭</div>
            <h3 class="text-xl font-bold text-white mb-2">Belum ada tiket</h3>
            <p class="text-gray-400 mb-6">Anda belum pernah mengirim pesan ke kami</p>
            <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Buat Tiket Pertama
            </a>
        </div>
    @endif

    <!-- Back Link -->
    <div class="mt-8 text-center">
        <a href="{{ route('contact') }}" class="text-gray-400 hover:text-red-500 transition inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
            </svg>
            Kembali ke halaman kontak
        </a>
    </div>
</div>
@endsection
