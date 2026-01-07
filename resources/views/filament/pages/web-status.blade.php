<x-filament::page>
    <div class="space-y-6">
        {{-- Current Status Card --}}
        <div class="p-6 rounded-xl border-2 {{ match($currentStatus['status']) {
            'online' => 'bg-green-50 dark:bg-green-900/20 border-green-500',
            'maintenance' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500',
            'down' => 'bg-red-50 dark:bg-red-900/20 border-red-500',
            'degraded' => 'bg-orange-50 dark:bg-orange-900/20 border-orange-500',
            'update' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-500',
            default => 'bg-gray-50 dark:bg-gray-800 border-gray-300',
        } }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="text-5xl">{{ $currentStatus['config']['emoji'] }}</span>
                    <div>
                        <h2 class="text-2xl font-bold">Status Saat Ini: {{ $currentStatus['config']['label'] }}</h2>
                        <p class="text-gray-600 dark:text-gray-400">{{ $currentStatus['config']['description'] }}</p>
                        @if($currentStatus['message'])
                            <p class="mt-2 text-sm bg-white/50 dark:bg-black/20 px-3 py-1 rounded inline-block">
                                💬 {{ $currentStatus['message'] }}
                            </p>
                        @endif
                    </div>
                </div>
                <div class="text-right text-sm text-gray-500">
                    <p>Terakhir diupdate:</p>
                    <p class="font-semibold">{{ $currentStatus['updated_at'] }}</p>
                    <p>oleh {{ $currentStatus['updated_by'] }}</p>
                </div>
            </div>

            @if($currentStatus['status'] === 'maintenance')
                <div class="mt-4 p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-400">
                        🔑 Bypass URL (untuk admin): 
                        <a href="{{ $bypassUrl }}" target="_blank" class="underline hover:no-underline">
                            {{ $bypassUrl }}
                        </a>
                    </p>
                </div>
            @endif
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            {{-- Update Status Form --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold mb-4">📊 Update Status Website</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Status</label>
                        <select wire:model="status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            @foreach($statuses as $key => $config)
                                <option value="{{ $key }}">{{ $config['emoji'] }} {{ $config['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Keterangan</label>
                        <textarea 
                            wire:model="message" 
                            rows="3" 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                            placeholder="Contoh: Sedang update fitur baru..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Estimasi Selesai (opsional)</label>
                        <input 
                            type="text" 
                            wire:model="estimatedTime" 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                            placeholder="Contoh: 30 menit, 2 jam, dst"
                        />
                    </div>

                    <button 
                        wire:click="updateStatus"
                        class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition"
                    >
                        🚀 Update Status & Kirim ke Discord
                    </button>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold mb-4">⚡ Quick Actions</h3>
                
                <div class="grid grid-cols-2 gap-3">
                    <button 
                        wire:click="$set('status', 'online'); $call('updateStatus')"
                        class="p-4 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-900/50 rounded-lg text-center transition"
                    >
                        <span class="text-3xl">🟢</span>
                        <p class="font-medium mt-1">Set Online</p>
                    </button>

                    <button 
                        wire:click="$set('status', 'maintenance'); $call('updateStatus')"
                        class="p-4 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:hover:bg-yellow-900/50 rounded-lg text-center transition"
                    >
                        <span class="text-3xl">🟡</span>
                        <p class="font-medium mt-1">Set Maintenance</p>
                    </button>

                    <button 
                        wire:click="$set('status', 'down'); $call('updateStatus')"
                        class="p-4 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 rounded-lg text-center transition"
                    >
                        <span class="text-3xl">🔴</span>
                        <p class="font-medium mt-1">Set Down</p>
                    </button>

                    <button 
                        wire:click="$set('status', 'update'); $call('updateStatus')"
                        class="p-4 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 rounded-lg text-center transition"
                    >
                        <span class="text-3xl">🔵</span>
                        <p class="font-medium mt-1">Set Update</p>
                    </button>
                </div>
            </div>
        </div>

        {{-- Custom Announcement --}}
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold mb-4">📢 Kirim Pengumuman Custom</h3>
            
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Tipe</label>
                    <select wire:model="announcementType" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        <option value="info">ℹ️ Info</option>
                        <option value="success">✅ Success</option>
                        <option value="warning">⚠️ Warning</option>
                        <option value="error">❌ Error</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2">Judul</label>
                    <input 
                        type="text" 
                        wire:model="announcementTitle" 
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                        placeholder="Contoh: Fitur Baru Tersedia!"
                    />
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium mb-2">Pesan</label>
                <textarea 
                    wire:model="announcementMessage" 
                    rows="3" 
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                    placeholder="Tulis pesan pengumuman..."
                ></textarea>
            </div>

            <button 
                wire:click="sendAnnouncement"
                class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition"
            >
                📤 Kirim Pengumuman ke Discord
            </button>
        </div>

        {{-- Health Monitor Section --}}
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">🏥 Auto Health Monitor</h3>
                <button 
                    wire:click="runHealthCheck"
                    class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition"
                >
                    🔄 Run Now
                </button>
            </div>
            
            <div class="grid md:grid-cols-3 gap-4 mb-4">
                <div class="p-4 rounded-lg {{ $healthStatus === 'healthy' ? 'bg-green-100 dark:bg-green-900/30' : ($healthStatus === 'unhealthy' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gray-100 dark:bg-gray-700') }}">
                    <div class="text-sm text-gray-500 mb-1">Status</div>
                    <div class="text-xl font-bold {{ $healthStatus === 'healthy' ? 'text-green-600' : ($healthStatus === 'unhealthy' ? 'text-red-600' : 'text-gray-600') }}">
                        {{ $healthStatus === 'healthy' ? '✅ Healthy' : ($healthStatus === 'unhealthy' ? '❌ Unhealthy' : '❓ Unknown') }}
                    </div>
                </div>
                <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <div class="text-sm text-gray-500 mb-1">Last Check</div>
                    <div class="text-lg font-semibold">{{ $healthLastRun }}</div>
                </div>
                <div class="p-4 {{ $healthFailures > 0 ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-gray-100 dark:bg-gray-700' }} rounded-lg">
                    <div class="text-sm text-gray-500 mb-1">Consecutive Failures</div>
                    <div class="text-xl font-bold {{ $healthFailures > 0 ? 'text-yellow-600' : 'text-gray-600' }}">{{ $healthFailures }}/2</div>
                </div>
            </div>

            @if(count($healthResults) > 0)
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 class="font-semibold mb-3">Endpoint Status:</h4>
                <div class="grid md:grid-cols-2 gap-3">
                    @foreach($healthResults as $name => $result)
                    <div class="p-3 rounded-lg {{ $result['healthy'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">{{ ucfirst($name) }}</span>
                            <span class="text-sm {{ $result['healthy'] ? 'text-green-600' : 'text-red-600' }}">
                                {{ $result['healthy'] ? '✅ OK' : '❌ FAIL' }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                            {{ $result['url'] }} • {{ $result['response_time'] }}ms
                            @if($result['status_code'])
                                • HTTP {{ $result['status_code'] }}
                            @endif
                        </div>
                        @if($result['error'])
                            <div class="text-xs text-red-500 mt-1">{{ Str::limit($result['error'], 80) }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm">
                <strong>ℹ️ Info:</strong> Health check berjalan otomatis setiap 5 menit. 
                Notifikasi Discord akan dikirim jika website down (setelah 2x gagal berturut-turut) atau ketika recovery.
            </div>
        </div>

        {{-- Status Legend --}}
        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
            <h3 class="font-semibold mb-3">📋 Keterangan Status:</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
                @foreach($statuses as $key => $config)
                    <div class="flex items-center gap-2">
                        <span>{{ $config['emoji'] }}</span>
                        <span><strong>{{ $config['label'] }}</strong> - {{ $config['description'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament::page>
