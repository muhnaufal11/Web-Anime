@extends('layouts.app')
@section('title', 'Chat - ' . $message->subject)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-[#1a1d24] to-[#0f1115] rounded-2xl p-6 border border-white/10 mb-6">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white mb-2">{{ $message->subject }}</h1>
                <div class="flex items-center gap-4 text-sm text-gray-400">
                    <span>📧 {{ $message->email }}</span>
                    <span>📅 {{ $message->created_at->format('d M Y, H:i') }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($message->is_closed)
                    <span class="px-3 py-1 bg-gray-600/50 text-gray-300 rounded-full text-sm font-medium">
                        🔒 Ditutup
                    </span>
                @else
                    <span class="px-3 py-1 bg-green-600/50 text-green-300 rounded-full text-sm font-medium">
                        🟢 Aktif
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Chat Container -->
    <div class="bg-[#1a1d24] rounded-2xl border border-white/10 overflow-hidden">
        <!-- Chat Messages -->
        <div id="chat-messages" class="h-[500px] overflow-y-auto p-6 space-y-4">
            <!-- Initial Message from User -->
            <div class="flex justify-end">
                <div class="max-w-[80%]">
                    <div class="bg-gradient-to-r from-red-600 to-red-700 text-white rounded-2xl rounded-tr-sm px-4 py-3 shadow-lg">
                        <p class="whitespace-pre-wrap">{{ $message->message }}</p>
                    </div>
                    <div class="text-right mt-1">
                        <span class="text-xs text-gray-500">{{ $message->created_at->format('d M Y, H:i') }}</span>
                    </div>
                </div>
            </div>

            @if($message->replies->count() > 0)
                @foreach($message->replies as $reply)
                    @if($reply->is_admin)
                        <!-- Admin Message -->
                        <div class="flex justify-start">
                            <div class="max-w-[80%]">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a2 2 0 11-4 0 2 2 0 014 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-blue-400">Admin nipnime</span>
                                </div>
                                <div class="bg-white/10 text-gray-100 rounded-2xl rounded-tl-sm px-4 py-3 border border-white/10">
                                    <p class="whitespace-pre-wrap">{{ $reply->message }}</p>
                                </div>
                                <div class="text-left mt-1">
                                    <span class="text-xs text-gray-500">{{ $reply->created_at->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- User Message -->
                        <div class="flex justify-end">
                            <div class="max-w-[80%]">
                                <div class="bg-gradient-to-r from-red-600 to-red-700 text-white rounded-2xl rounded-tr-sm px-4 py-3 shadow-lg">
                                    <p class="whitespace-pre-wrap">{{ $reply->message }}</p>
                                </div>
                                <div class="text-right mt-1">
                                    <span class="text-xs text-gray-500">{{ $reply->created_at->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            @elseif($message->reply)
                <!-- Legacy single reply (for backward compatibility) -->
                <div class="flex justify-start">
                    <div class="max-w-[80%]">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a2 2 0 11-4 0 2 2 0 014 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-blue-400">Admin nipnime</span>
                        </div>
                        <div class="bg-white/10 text-gray-100 rounded-2xl rounded-tl-sm px-4 py-3 border border-white/10">
                            <p class="whitespace-pre-wrap">{{ $message->reply }}</p>
                        </div>
                        <div class="text-left mt-1">
                            <span class="text-xs text-gray-500">{{ $message->replied_at ? $message->replied_at->format('d M Y, H:i') : '' }}</span>
                        </div>
                    </div>
                </div>
            @else
                <!-- Waiting for reply -->
                <div class="flex justify-center py-8">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-yellow-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-gray-400 font-medium">Menunggu balasan dari Admin</p>
                        <p class="text-gray-500 text-sm mt-1">Biasanya kami membalas dalam 1-2 hari kerja</p>
                    </div>
                </div>
            @endif

            @if($message->is_closed)
                <!-- Closed notice -->
                <div class="flex justify-center py-4">
                    <div class="bg-gray-700/50 text-gray-400 px-4 py-2 rounded-full text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        Percakapan ditutup pada {{ $message->closed_at ? $message->closed_at->format('d M Y, H:i') : '' }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Chat Input -->
        @if(!$message->is_closed)
            <div class="border-t border-white/10 p-4">
                <form action="{{ route('contact.reply', $message->view_token) }}" method="POST" id="chat-form">
                    @csrf
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <textarea name="message" 
                                      id="chat-input"
                                      rows="2" 
                                      class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-red-500 focus:ring-1 focus:ring-red-500 resize-none"
                                      placeholder="Ketik pesan Anda..."
                                      required
                                      maxlength="2000"></textarea>
                        </div>
                        <button type="submit" 
                                id="send-btn"
                                class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all flex items-center gap-2 self-end">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5.951-1.429 5.951 1.429a1 1 0 001.169-1.409l-7-14z"/>
                            </svg>
                            <span class="hidden sm:inline">Kirim</span>
                        </button>
                    </div>
                </form>
                <p class="text-xs text-gray-500 mt-2">Tekan Enter untuk baris baru, klik Kirim untuk mengirim pesan</p>
            </div>
        @else
            <div class="border-t border-white/10 p-4 text-center">
                <p class="text-gray-500">Percakapan ini sudah ditutup. <a href="{{ route('contact') }}" class="text-red-500 hover:underline">Buat pesan baru</a> jika ada pertanyaan lain.</p>
            </div>
        @endif
    </div>

    <!-- Info Box -->
    <div class="mt-6 bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm">
                <p class="text-blue-300 font-medium mb-1">Simpan link ini!</p>
                <p class="text-gray-400">Bookmark halaman ini untuk mengakses percakapan kapan saja. Link ini bersifat pribadi dan hanya Anda yang bisa mengaksesnya.</p>
            </div>
        </div>
    </div>

    <!-- Back Link -->
    <div class="mt-6">
        <a href="{{ route('contact') }}" class="text-gray-400 hover:text-red-500 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
            </svg>
            Kembali ke halaman kontak
        </a>
    </div>
</div>

<script>
    // Auto scroll to bottom
    document.addEventListener('DOMContentLoaded', function() {
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
    
    // AJAX form submit
    document.getElementById('chat-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const btn = document.getElementById('send-btn');
        const input = document.getElementById('chat-input');
        const chatMessages = document.getElementById('chat-messages');
        
        const message = input.value.trim();
        if (!message) return;
        
        // Disable button
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        
        // Send via AJAX
        fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new URLSearchParams(new FormData(form))
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add message to chat
                const now = new Date();
                const timeStr = now.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                
                const msgHtml = `
                    <div class="flex justify-end">
                        <div class="max-w-[80%]">
                            <div class="bg-gradient-to-r from-red-600 to-red-700 text-white rounded-2xl rounded-tr-sm px-4 py-3 shadow-lg">
                                <p class="whitespace-pre-wrap">${escapeHtml(message)}</p>
                            </div>
                            <div class="text-right mt-1">
                                <span class="text-xs text-gray-500">${timeStr}</span>
                            </div>
                        </div>
                    </div>
                `;
                chatMessages.insertAdjacentHTML('beforeend', msgHtml);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Clear input
                input.value = '';
            } else {
                alert(data.message || 'Gagal mengirim pesan');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan. Silakan coba lagi.');
        })
        .finally(() => {
            // Re-enable button
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5.951-1.429 5.951 1.429a1 1 0 001.169-1.409l-7-14z"/></svg><span class="hidden sm:inline">Kirim</span>';
        });
    });
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
@endsection
