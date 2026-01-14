@extends('layouts.app')
@section('title', 'Chat Admin - ' . $message->subject)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-[#1a1d24] to-[#0f1115] rounded-2xl p-6 border border-white/10 mb-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white mb-2">{{ $message->subject }}</h1>
                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-400">
                    <span>👤 {{ $message->name }}</span>
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
                    <form action="{{ route('admin.contact-messages.close', $message->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            Tutup Chat
                        </button>
                    </form>
                @endif
            </div>
        </div>
        
        <!-- Public Link -->
        <div class="mt-4 p-3 bg-white/5 rounded-lg">
            <p class="text-xs text-gray-400 mb-1">Link chat untuk user:</p>
            <a href="{{ route('contact.status', $message->view_token) }}" target="_blank" class="text-blue-400 hover:underline text-sm break-all">
                {{ route('contact.status', $message->view_token) }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-500/20 border border-green-500/50 text-green-400 px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <!-- Chat Container -->
    <div class="bg-[#1a1d24] rounded-2xl border border-white/10 overflow-hidden">
        <!-- Chat Messages -->
        <div id="chat-messages" class="h-[500px] overflow-y-auto p-6 space-y-4">
            <!-- Initial Message from User -->
            <div class="flex justify-start">
                <div class="max-w-[80%]">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                            {{ strtoupper(substr($message->name, 0, 1)) }}
                        </div>
                        <span class="text-sm font-medium text-red-400">{{ $message->name }}</span>
                        <span class="text-xs text-gray-500">{{ $message->email }}</span>
                    </div>
                    <div class="bg-white/10 text-gray-100 rounded-2xl rounded-tl-sm px-4 py-3 border border-white/10">
                        <p class="whitespace-pre-wrap">{{ $message->message }}</p>
                    </div>
                    <div class="text-left mt-1">
                        <span class="text-xs text-gray-500">{{ $message->created_at->format('d M Y, H:i') }}</span>
                    </div>
                </div>
            </div>

            @if($message->replies->count() > 0)
                @foreach($message->replies as $reply)
                    @if($reply->is_admin)
                        <!-- Admin Message -->
                        <div class="flex justify-end">
                            <div class="max-w-[80%]">
                                <div class="flex items-center justify-end gap-2 mb-1">
                                    <span class="text-sm font-medium text-blue-400">Admin</span>
                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a2 2 0 11-4 0 2 2 0 014 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-2xl rounded-tr-sm px-4 py-3 shadow-lg">
                                    <p class="whitespace-pre-wrap">{{ $reply->message }}</p>
                                </div>
                                <div class="text-right mt-1">
                                    <span class="text-xs text-gray-500">{{ $reply->created_at->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- User Message -->
                        <div class="flex justify-start">
                            <div class="max-w-[80%]">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                        {{ strtoupper(substr($message->name, 0, 1)) }}
                                    </div>
                                    <span class="text-sm font-medium text-red-400">{{ $message->name }}</span>
                                </div>
                                <div class="bg-white/10 text-gray-100 rounded-2xl rounded-tl-sm px-4 py-3 border border-white/10">
                                    <p class="whitespace-pre-wrap">{{ $reply->message }}</p>
                                </div>
                                <div class="text-left mt-1">
                                    <span class="text-xs text-gray-500">{{ $reply->created_at->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            @elseif($message->reply)
                <!-- Legacy single reply -->
                <div class="flex justify-end">
                    <div class="max-w-[80%]">
                        <div class="flex items-center justify-end gap-2 mb-1">
                            <span class="text-sm font-medium text-blue-400">Admin</span>
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a2 2 0 11-4 0 2 2 0 014 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-2xl rounded-tr-sm px-4 py-3 shadow-lg">
                            <p class="whitespace-pre-wrap">{{ $message->reply }}</p>
                        </div>
                        <div class="text-right mt-1">
                            <span class="text-xs text-gray-500">{{ $message->replied_at ? $message->replied_at->format('d M Y, H:i') : '' }}</span>
                        </div>
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
                <form action="{{ route('admin.contact-messages.reply', $message->id) }}" method="POST" id="admin-chat-form">
                    @csrf
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <textarea name="message" 
                                      id="admin-chat-input"
                                      rows="2" 
                                      class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 resize-none"
                                      placeholder="Ketik balasan Anda..."
                                      required
                                      maxlength="2000"></textarea>
                        </div>
                        <button type="submit" 
                                id="admin-send-btn"
                                class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all flex items-center gap-2 self-end">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5.951-1.429 5.951 1.429a1 1 0 001.169-1.409l-7-14z"/>
                            </svg>
                            <span class="hidden sm:inline">Kirim</span>
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="border-t border-white/10 p-4 text-center">
                <p class="text-gray-500">Percakapan ini sudah ditutup.</p>
            </div>
        @endif
    </div>

    <!-- Back Link -->
    <div class="mt-6">
        <a href="{{ route('admin.contact-messages.index') }}" class="text-gray-400 hover:text-blue-500 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
            </svg>
            Kembali ke daftar pesan
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
    document.getElementById('admin-chat-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const btn = document.getElementById('admin-send-btn');
        const input = document.getElementById('admin-chat-input');
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
                            <div class="flex items-center justify-end gap-2 mb-1">
                                <span class="text-sm font-medium text-blue-400">Admin</span>
                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a2 2 0 11-4 0 2 2 0 014 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-2xl rounded-tr-sm px-4 py-3 shadow-lg">
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
