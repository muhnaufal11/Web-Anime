<x-filament::page>
    <style>
        .chat-box { max-height: 500px; overflow-y: auto; }
        .msg-user { background: #374151; border-radius: 12px; padding: 12px 16px; margin-bottom: 12px; max-width: 80%; }
        .msg-admin { background: #3b82f6; border-radius: 12px; padding: 12px 16px; margin-bottom: 12px; max-width: 80%; margin-left: auto; }
        .msg-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
        .msg-time { font-size: 11px; opacity: 0.7; margin-left: 8px; }
        .msg-text { white-space: pre-wrap; }
        .quick-reply-box { display: flex; gap: 12px; padding: 16px; border-top: 1px solid #374151; }
        .quick-reply-box textarea { flex: 1; background: #1f2937; border: 1px solid #4b5563; border-radius: 8px; padding: 12px; color: white; resize: none; }
        .quick-reply-box button { background: #3b82f6; color: white; padding: 12px 24px; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 8px; border: none; cursor: pointer; }
        .quick-reply-box button:hover { background: #2563eb; }
    </style>

    <div class="space-y-6">
        {{-- Info Ticket --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">Dari</span>
                    <p class="font-semibold text-gray-900 dark:text-white mt-1">{{ $record->name }}</p>
                    <p class="text-xs text-gray-500">{{ $record->email }}</p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">Subjek</span>
                    <p class="font-semibold text-gray-900 dark:text-white mt-1">{{ $record->subject }}</p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">Tanggal</span>
                    <p class="font-semibold text-gray-900 dark:text-white mt-1">{{ $record->created_at->format('d M Y H:i') }}</p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">Status</span>
                    <div class="mt-1">
                        @if($record->is_closed)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-500/20 text-green-400">
                                ✓ Selesai
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500/20 text-yellow-400">
                                ● Aktif
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Chat Area --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50">
                <h3 class="font-semibold text-gray-900 dark:text-white text-lg">💬 Percakapan</h3>
                <div class="flex items-center gap-3">
                    <span id="realtime-status" class="text-xs text-green-400 flex items-center gap-1.5 bg-green-500/10 px-2 py-1 rounded-full">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        Real-time aktif
                    </span>
                    <button type="button" onclick="refreshMessages()" class="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/10 rounded-lg transition" title="Refresh">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>

            <div id="chat-container" class="chat-box p-4" style="min-height: 400px;">
                {{-- Pesan Awal dari User --}}
                <div class="msg-user">
                    <div class="flex items-center">
                        <span class="msg-name text-white">{{ $record->name }}</span>
                        <span class="msg-time text-gray-400">{{ $record->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <p class="msg-text text-gray-200 mt-1">{{ $record->message }}</p>
                </div>

                {{-- Balasan-balasan --}}
                <div id="messages-container">
                    @foreach($record->replies()->orderBy('created_at', 'asc')->get() as $reply)
                        @if($reply->is_admin)
                            <div class="msg-admin">
                                <div class="flex items-center">
                                    <span class="msg-name text-white">{{ $reply->admin?->name ?? 'Admin' }} <span class="text-blue-200 text-xs">(Admin)</span></span>
                                    <span class="msg-time text-blue-200">{{ $reply->created_at->format('d M Y H:i') }}</span>
                                </div>
                                <p class="msg-text text-white mt-1">{{ $reply->message }}</p>
                            </div>
                        @else
                            <div class="msg-user">
                                <div class="flex items-center">
                                    <span class="msg-name text-white">{{ $record->name }}</span>
                                    <span class="msg-time text-gray-400">{{ $reply->created_at->format('d M Y H:i') }}</span>
                                </div>
                                <p class="msg-text text-gray-200 mt-1">{{ $reply->message }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Quick Reply Box --}}
            @if(!$record->is_closed)
            <div class="quick-reply-box bg-gray-900">
                <textarea id="quick-reply-text" rows="2" placeholder="Ketik balasan Anda di sini..." 
                    onkeydown="if(event.key==='Enter' && event.ctrlKey) { event.preventDefault(); submitQuickReply(); }"></textarea>
                <button type="button" onclick="submitQuickReply()" id="send-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Kirim
                </button>
            </div>
            <p class="text-xs text-gray-500 text-center py-2 bg-gray-900/50">Tekan Ctrl+Enter untuk kirim cepat</p>
            @else
            <div class="p-4 bg-gray-900 text-center">
                <p class="text-gray-400">🔒 Percakapan sudah ditutup</p>
            </div>
            @endif
        </div>
    </div>

    <script>
        const messageId = {{ $record->id }};
        const userName = '{{ $record->name }}';
        let lastReplyCount = {{ $record->replies()->count() }};
        let pollingInterval;

        // CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

        // Scroll to bottom
        function scrollToBottom() {
            const container = document.getElementById('chat-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        // Refresh messages
        async function refreshMessages() {
            try {
                const response = await fetch(`/admin/api/contact-messages/${messageId}/replies`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    console.error('Response not OK:', response.status);
                    return;
                }
                
                const data = await response.json();
                console.log('Replies fetched:', data);
                
                if (data.replies && data.replies.length !== lastReplyCount) {
                    updateMessagesUI(data.replies);
                    lastReplyCount = data.replies.length;
                    scrollToBottom();
                    
                    if (data.replies.length > lastReplyCount) {
                        showToast('Pesan baru!', 'success');
                    }
                }
            } catch (error) {
                console.error('Error fetching messages:', error);
            }
        }

        // Update messages UI
        function updateMessagesUI(replies) {
            const container = document.getElementById('messages-container');
            if (!container) return;
            
            container.innerHTML = '';
            
            replies.forEach(reply => {
                const isAdmin = reply.is_admin;
                const adminName = reply.admin_name || 'Admin';
                
                let messageHtml;
                if (isAdmin) {
                    messageHtml = `
                        <div class="msg-admin">
                            <div class="flex items-center">
                                <span class="msg-name text-white">${escapeHtml(adminName)} <span class="text-blue-200 text-xs">(Admin)</span></span>
                                <span class="msg-time text-blue-200">${reply.created_at}</span>
                            </div>
                            <p class="msg-text text-white mt-1">${escapeHtml(reply.message)}</p>
                        </div>
                    `;
                } else {
                    messageHtml = `
                        <div class="msg-user">
                            <div class="flex items-center">
                                <span class="msg-name text-white">${escapeHtml(userName)}</span>
                                <span class="msg-time text-gray-400">${reply.created_at}</span>
                            </div>
                            <p class="msg-text text-gray-200 mt-1">${escapeHtml(reply.message)}</p>
                        </div>
                    `;
                }
                container.insertAdjacentHTML('beforeend', messageHtml);
            });
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg text-white font-semibold ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} shadow-xl z-[9999]`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // Submit quick reply
        async function submitQuickReply() {
            console.log('submitQuickReply called');
            const textarea = document.getElementById('quick-reply-text');
            const sendBtn = document.getElementById('send-btn');
            const message = textarea ? textarea.value.trim() : '';
            
            console.log('Message:', message);
            console.log('CSRF Token:', csrfToken);
            
            if (!message) {
                alert('Pesan tidak boleh kosong');
                return;
            }
            
            // Disable button
            if (sendBtn) {
                sendBtn.disabled = true;
                sendBtn.innerHTML = '⏳ Mengirim...';
            }
            
            try {
                console.log('Sending to:', `/admin/api/contact-messages/${messageId}/reply`);
                
                const response = await fetch(`/admin/api/contact-messages/${messageId}/reply`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ message: message })
                });
                
                console.log('Response status:', response.status);
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch(e) {
                    console.error('Failed to parse JSON:', e);
                    alert('Error: Response bukan JSON valid. Status: ' + response.status);
                    return;
                }
                
                if (data.success) {
                    textarea.value = '';
                    await refreshMessages();
                    showToast('✓ Balasan terkirim!', 'success');
                } else if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    alert('Gagal: ' + JSON.stringify(data));
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('Network error: ' + error.message);
            } finally {
                // Re-enable button
                if (sendBtn) {
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg> Kirim`;
                }
            }
        }

        // Start polling
        function startPolling() {
            pollingInterval = setInterval(refreshMessages, 3000);
        }

        // Stop polling when page is hidden
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                clearInterval(pollingInterval);
            } else {
                refreshMessages();
                startPolling();
            }
        });

        // Initialize
        console.log('Chat initialized for message ID:', messageId);
        scrollToBottom();
        startPolling();
    </script>
</x-filament::page>
