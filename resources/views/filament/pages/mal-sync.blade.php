<x-filament::page>
    <div class="space-y-6" @if($isSyncing) wire:poll.500ms="pollSync" @endif>
        {{-- Header Card --}}
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 rounded-full p-3">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-bold">MyAnimeList Sync</h2>
                    <p class="text-white/80 mt-1">Import anime data dari MyAnimeList database</p>
                </div>
            </div>
        </div>

        {{-- Info Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center gap-3">
                    <div class="bg-yellow-100 dark:bg-yellow-900 rounded-full p-2">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Top Anime</p>
                        <p class="text-lg font-bold">By Rating</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-2">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Seasonal</p>
                        <p class="text-lg font-bold">By Season</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center gap-3">
                    <div class="bg-green-100 dark:bg-green-900 rounded-full p-2">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Search</p>
                        <p class="text-lg font-bold">Specific Anime</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center gap-3">
                    <div class="bg-purple-100 dark:bg-purple-900 rounded-full p-2">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">MAL ID</p>
                        <p class="text-lg font-bold">By ID Number</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Form Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold">Sync Configuration</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Configure your anime sync settings</p>
            </div>
            
            <form wire:submit.prevent="syncAnime" class="p-6">
                {{ $this->form }}
                
                {{-- Progress & Logs - Always Show When Syncing or Has Logs --}}
                @if($isSyncing || count($syncLogs) > 0)
                <div class="mt-6 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-900 dark:to-gray-800 rounded-lg p-5 border-2 border-blue-300 dark:border-blue-700 shadow-lg">
                    {{-- Status Header --}}
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            @if($isSyncing)
                                <div class="relative">
                                    <div class="animate-ping absolute h-3 w-3 rounded-full bg-blue-400 opacity-75"></div>
                                    <div class="relative h-3 w-3 rounded-full bg-blue-500"></div>
                                </div>
                                <span class="text-sm font-bold text-blue-700 dark:text-blue-300">SYNC IN PROGRESS</span>
                            @else
                                <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                <span class="text-sm font-bold text-green-700 dark:text-green-300">SYNC COMPLETED</span>
                            @endif
                        </div>
                        @if($isSyncing)
                            <span class="text-xs font-mono text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-700 px-3 py-1 rounded-full">{{ $syncProgress }}%</span>
                        @endif
                    </div>
                    
                    {{-- Progress Bar --}}
                    @if($isSyncing)
                    <div class="mb-5">
                        <div class="w-full h-4 rounded-full overflow-hidden shadow-inner bg-gray-200 dark:bg-gray-700">
                            <!-- Colored progress fill -->
                            <div
                                class="h-4 rounded-full transition-all duration-300 ease-out flex items-center justify-end px-2 bg-gradient-to-r from-red-600 via-orange-500 to-yellow-500"
                                style="width: {{ $syncProgress }}%; background: linear-gradient(90deg, #dc2626 0%, #f97316 50%, #eab308 100%);"
                            >
                                @if($syncProgress > 10)
                                    <span class="text-xs font-bold text-white drop-shadow-sm">{{ $syncProgress }}%</span>
                                @endif
                            </div>
                        </div>
                        <!-- Thin accent line for extra visibility -->
                        <div class="mt-2 h-1 rounded-full bg-gradient-to-r from-red-600 via-orange-500 to-yellow-500" style="width: {{ max(10, $syncProgress) }}%"></div>
                    </div>
                    @endif
                    
                    {{-- Logs Section --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-inner border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-300 dark:border-gray-600">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Activity Log</span>
                            <span class="ml-auto text-xs text-gray-500 dark:text-gray-400 font-mono">{{ count($syncLogs) }} entries</span>
                        </div>
                        <div class="space-y-2 max-h-80 overflow-y-auto custom-scrollbar">
                            @forelse($syncLogs as $index => $log)
                            <div class="flex items-start gap-3 text-sm py-2 px-3 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors animate-fadeIn">
                                <span class="text-xs text-blue-600 dark:text-blue-400 font-mono font-bold flex-shrink-0 bg-blue-100 dark:bg-blue-900/30 px-2 py-1 rounded">{{ $log['time'] }}</span>
                                <span class="text-gray-800 dark:text-gray-200 flex-1 leading-relaxed">{{ $log['message'] }}</span>
                            </div>
                            @empty
                            <div class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                                No logs yet...
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endif
                
                <div class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p class="font-medium">📌 Tips:</p>
                        <ul class="mt-2 space-y-1 list-disc list-inside">
                            <li>Start dengan limit 10 untuk testing</li>
                            <li>Download images akan memakan waktu lebih lama</li>
                            <li>Top anime berisi anime dengan rating tertinggi</li>
                        </ul>
                    </div>
                    
                    <x-filament::button 
                        type="submit"
                        size="lg"
                        :disabled="$isSyncing"
                        wire:loading.attr="disabled"
                        wire:target="syncAnime"
                    >
                        @if($isSyncing)
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Syncing... {{ $syncProgress }}%</span>
                        @else
                            <span wire:loading.remove wire:target="syncAnime">🚀 Start Sync</span>
                            <span wire:loading wire:target="syncAnime">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        @endif
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Instructions Card --}}
        <div class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-gray-800 dark:to-gray-700 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                Cara Penggunaan
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <span class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold flex-shrink-0">1</span>
                        <div>
                            <h4 class="font-semibold mb-1">Pilih Sync Type</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Pilih apakah mau sync top anime, seasonal, atau search spesifik</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <span class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold flex-shrink-0">2</span>
                        <div>
                            <h4 class="font-semibold mb-1">Atur Settings</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Tentukan limit (berapa anime) dan apakah mau download gambar</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <span class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold flex-shrink-0">3</span>
                        <div>
                            <h4 class="font-semibold mb-1">Klik Start Sync</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Tunggu proses selesai, anime akan otomatis masuk ke database</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }
        
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.5);
            border-radius: 4px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.8);
        }
    </style>
</x-filament::page>
