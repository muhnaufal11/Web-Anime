@extends('layouts.app')
@section('title', 'Chat Pesan - ' . $message->subject)
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-[#1a1d24] to-[#0f1115] rounded-2xl p-6 border border-white/10 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-white">{{ $message->subject }}</h1>
            <div class="flex items-center gap-2">
                <span id="chat-status-indicator" class="w-2 h-2 bg-green-500 rounded-full animate-pulse" title="Real-time aktif"></span>
                @if($message->is_closed)
                    <span id="status-badge" class="px-3 py-1 bg-gray-600 text-white text-sm font-bold rounded-full">Ditutup</span>
                @else
                    <span id="status-badge" class="px-3 py-1 bg-green-600 text-white text-sm font-bold rounded-full">Aktif</span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-4 text-sm text-gray-400">
            <div><span class="text-gray-500">Nama:</span> {{ $message->name }}</div>
            <div><span class="text-gray-500">Email:</span> <span class="text-cyan-400">{{ $message->email }}</span></div>
            <div><span class="text-gray-500">Tanggal:</span> <span class="text-yellow-400">{{ $message->created_at->format('d M Y H:i') }}</span></div>
        </div>
    </div>

    <!-- Chat Messages -->
    <div class="bg-[#1a1d24] rounded-2xl border border-white/10 overflow-hidden mb-6">
        <div class="p-4 border-b border-white/10 bg-white/5 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                </svg>
                Percakapan
            </h2>
            <span id="typing-indicator" class="text-xs text-gray-500 hidden">
                <span class="animate-pulse">Admin sedang mengetik...</span>
            </span>
        </div>
        
        <div class="p-4 space-y-4 max-h-[500px] overflow-y-auto" id="chat-messages" data-token="{{ $message->view_token }}" data-last-count="{{ $message->replies->count() }}">
            <!-- Pesan Awal User -->
            <div class="flex justify-end">
                <div class="max-w-[80%]">
                    <div class="bg-red-600 text-white rounded-2xl rounded-br-md px-4 py-3">
                        <p class="whitespace-pre-wrap">{{ $message->message }}</p>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 text-right">{{ $message->created_at->format('d M Y H:i') }} • Anda</p>
                </div>
            </div>

            <!-- Dynamic messages will be loaded here -->
            <div id="dynamic-messages"></div>
        </div>

        <!-- Form Balas (jika belum ditutup) -->
        <div id="reply-form-container">
            @if(!$message->is_closed)
                <div class="p-4 border-t border-white/10 bg-white/5">
                    <form id="chat-form" class="flex gap-3">
                        @csrf
                        <div class="flex-1">
                            <textarea 
                                id="message-input"
                                name="message" 
                                rows="2" 
                                placeholder="Ketik balasan Anda..." 
                                class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-red-600 focus:ring-2 focus:ring-red-600/50 focus:outline-none transition-all resize-none"
                                required
                                maxlength="2000"></textarea>
                        </div>
                        <button type="submit" id="send-btn" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all self-end disabled:opacity-50">
                            <svg id="send-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429a1 1 0 00.894-.894V9a1 1 0 012 0v6.639a1 1 0 00.894.894l5 1.429a1 1 0 001.169-1.409l-7-14z"/>
                            </svg>
                            <svg id="send-spinner" class="w-5 h-5 animate-spin hidden" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="p-4 border-t border-white/10 bg-gray-800/50 text-center">
                    <p class="text-gray-400">
                        <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        Percakapan ini telah ditutup
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Back Link -->
    <div class="text-center">
        <a href="{{ route('contact') }}" class="text-gray-400 hover:text-red-500 transition inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
            </svg>
            Kembali ke halaman kontak
        </a>
    </div>

    <!-- Bookmark Reminder -->
    <div class="mt-8 p-4 bg-blue-900/20 border border-blue-500/30 rounded-xl text-center">
        <p class="text-blue-400 text-sm">
            <svg class="w-5 h-5 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
            </svg>
            Simpan link halaman ini untuk mengecek balasan dari admin
        </p>
        <p class="text-gray-500 text-xs mt-2 break-all">{{ url()->current() }}</p>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

<script>
const chatToken = '{{ $message->view_token }}';
const csrfToken = '{{ csrf_token() }}';
const chatMessagesContainer = document.getElementById('chat-messages');
const dynamicMessages = document.getElementById('dynamic-messages');
const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message-input');
const sendBtn = document.getElementById('send-btn');
const sendIcon = document.getElementById('send-icon');
const sendSpinner = document.getElementById('send-spinner');
let lastMessageCount = {{ $message->replies->count() }};
let isClosed = {{ $message->is_closed ? 'true' : 'false' }};
let pollingInterval;

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Create message bubble HTML
function createMessageBubble(reply) {
    const isAdmin = reply.is_admin;
    const adminName = reply.admin ? reply.admin.name : 'Admin';
    
    return `
        <div class="flex ${isAdmin ? 'justify-start' : 'justify-end'} animate-fade-in">
            <div class="max-w-[80%]">
                <div class="${isAdmin ? 'bg-white/10' : 'bg-red-600'} text-white rounded-2xl ${isAdmin ? 'rounded-bl-md' : 'rounded-br-md'} px-4 py-3">
                    <p class="whitespace-pre-wrap">${escapeHtml(reply.message)}</p>
                </div>
                <p class="text-xs text-gray-500 mt-1 ${isAdmin ? '' : 'text-right'}">
                    ${formatDate(reply.created_at)} • ${isAdmin ? 'Admin (' + adminName + ')' : 'Anda'}
                </p>
            </div>
        </div>
    `;
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `animate-slide-in-right max-w-md`;
    toast.innerHTML = `
        <div class="bg-gradient-to-r ${type === 'success' ? 'from-green-600 to-green-700' : 'from-red-600 to-red-700'} text-white px-6 py-4 rounded-xl shadow-2xl">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    ${type === 'success' 
                        ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>'
                        : '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'}
                </svg>
                <p class="font-bold text-sm">${message}</p>
            </div>
        </div>
    `;
    document.getElementById('toast-container').appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// Scroll to bottom
function scrollToBottom() {
    chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
}

// Fetch new messages
async function fetchMessages() {
    try {
        const response = await fetch(`/api/contact/${chatToken}/messages`);
        const data = await response.json();
        
        if (data.success) {
            // Check if closed status changed
            if (data.is_closed && !isClosed) {
                isClosed = true;
                document.getElementById('status-badge').className = 'px-3 py-1 bg-gray-600 text-white text-sm font-bold rounded-full';
                document.getElementById('status-badge').textContent = 'Ditutup';
                document.getElementById('reply-form-container').innerHTML = `
                    <div class="p-4 border-t border-white/10 bg-gray-800/50 text-center">
                        <p class="text-gray-400">
                            <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            Percakapan ini telah ditutup
                        </p>
                    </div>
                `;
                showToast('Percakapan telah ditutup oleh admin', 'info');
            }
            
            // Check for new messages
            if (data.replies.length > lastMessageCount) {
                const newReplies = data.replies.slice(lastMessageCount);
                newReplies.forEach(reply => {
                    dynamicMessages.insertAdjacentHTML('beforeend', createMessageBubble(reply));
                    if (reply.is_admin) {
                        showToast('Pesan baru dari Admin!');
                        // Play notification sound (optional)
                        try {
                            const audio = new Audio('/sounds/notification.mp3');
                            audio.volume = 0.3;
                            audio.play().catch(() => {});
                        } catch(e) {}
                    }
                });
                lastMessageCount = data.replies.length;
                scrollToBottom();
            }
        }
    } catch (error) {
        console.error('Error fetching messages:', error);
    }
}

// Send message via AJAX
if (chatForm) {
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message) return;
        
        // Disable form
        sendBtn.disabled = true;
        sendIcon.classList.add('hidden');
        sendSpinner.classList.remove('hidden');
        
        try {
            const response = await fetch(`/contact/${chatToken}/reply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: message })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Add message to chat immediately
                dynamicMessages.insertAdjacentHTML('beforeend', createMessageBubble(data.reply));
                lastMessageCount++;
                messageInput.value = '';
                scrollToBottom();
                showToast('Pesan terkirim!');
            } else {
                showToast(data.message || 'Gagal mengirim pesan', 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan', 'error');
        } finally {
            sendBtn.disabled = false;
            sendIcon.classList.remove('hidden');
            sendSpinner.classList.add('hidden');
        }
    });
}

// Start polling
function startPolling() {
    fetchMessages(); // Initial fetch
    pollingInterval = setInterval(fetchMessages, 3000); // Poll every 3 seconds
}

// Stop polling when page is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        clearInterval(pollingInterval);
    } else {
        startPolling();
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Load existing messages
    @foreach($message->replies as $reply)
    dynamicMessages.insertAdjacentHTML('beforeend', createMessageBubble({
        message: {!! json_encode($reply->message) !!},
        is_admin: {{ $reply->is_admin ? 'true' : 'false' }},
        admin: {!! $reply->admin ? json_encode(['name' => $reply->admin->name]) : 'null' !!},
        created_at: '{{ $reply->created_at->toISOString() }}'
    }));
    @endforeach
    
    scrollToBottom();
    
    if (!isClosed) {
        startPolling();
    }
});

// CSS for animations
const style = document.createElement('style');
style.textContent = `
    .animate-fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;
document.head.appendChild(style);
</script>
@endsection