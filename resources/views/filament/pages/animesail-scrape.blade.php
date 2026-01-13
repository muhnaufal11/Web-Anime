<x-filament::page>
    <div class="space-y-6" @if($isScraping) wire:poll.1s="pollScrape" @endif>
        {{-- Header Card --}}
        <div class="bg-gradient-to-r from-orange-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 rounded-full p-3">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-bold">AnimeSail Scraper</h2>
                    <p class="text-white/80 mt-1">Scrape video servers dari AnimeSail</p>
                </div>
            </div>
        </div>

        {{-- Info Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center gap-3">
                    <div class="bg-orange-100 dark:bg-orange-900 rounded-full p-2">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Single Anime</p>
                        <p class="text-lg font-bold">All Episodes</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-2">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Video Servers</p>
                        <p class="text-lg font-bold">Auto Extract</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center gap-3">
                    <div class="bg-green-100 dark:bg-green-900 rounded-full p-2">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3z"/>
                            <path d="M3 7v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7z"/>
                            <path d="M17 5c0 1.657-3.134 3-7 3S3 6.657 3 5s3.134-3 7-3 7 1.343 7 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Sync to DB</p>
                        <p class="text-lg font-bold">Optional</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Form Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold">Scrape Configuration</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Configure scraping settings</p>
            </div>
            
            <div class="p-6">
                {{ $this->form }}
                
                {{-- Action Buttons --}}
                <div class="flex flex-wrap gap-3 mt-6">
                    <x-filament::button
                        wire:click="fetchEpisodeList"
                        color="secondary"
                        :disabled="$isScraping"
                    >
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                        </svg>
                        Fetch Episode List
                    </x-filament::button>
                    
                    <x-filament::button
                        wire:click="scrapeNow"
                        color="primary"
                        :disabled="$isScraping"
                    >
                        @if($isScraping)
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Scraping...
                        @else
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                            </svg>
                            Start Scrape
                        @endif
                    </x-filament::button>
                    
                    @if(!empty($scrapeResults))
                    <x-filament::button
                        wire:click="downloadResults"
                        color="success"
                    >
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Download JSON
                    </x-filament::button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Episode List Preview --}}
        @if(!empty($episodeList))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold">📺 {{ $animeInfo['title'] ?? 'Episode List' }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ count($episodeList) }} episodes found</p>
            </div>
            <div class="p-6 max-h-64 overflow-y-auto">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                    @foreach($episodeList as $episode)
                    <div class="bg-gray-100 dark:bg-gray-700 rounded px-3 py-2 text-sm">
                        <span class="font-medium">Ep {{ $episode['episode_number'] ?? '?' }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Progress & Logs --}}
        @if($isScraping || count($scrapeLogs) > 0)
        <div class="bg-gradient-to-r from-orange-50 to-red-50 dark:from-gray-900 dark:to-gray-800 rounded-lg p-5 border-2 border-orange-300 dark:border-orange-700 shadow-lg">
            {{-- Status Header --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    @if($isScraping)
                        <div class="relative">
                            <div class="animate-ping absolute h-3 w-3 rounded-full bg-orange-400 opacity-75"></div>
                            <div class="relative h-3 w-3 rounded-full bg-orange-500"></div>
                        </div>
                        <span class="text-sm font-bold text-orange-700 dark:text-orange-300">
                            @if($scrapeType === 'batch')
                                BATCH SCRAPING: {{ $batchCurrent }}/{{ $batchTotal }}
                            @else
                                SCRAPING IN PROGRESS
                            @endif
                        </span>
                    @else
                        @if($scrapeStatus === 'done')
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-bold text-green-700 dark:text-green-300">SCRAPE COMPLETE</span>
                        @elseif($scrapeStatus === 'error')
                            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-bold text-red-700 dark:text-red-300">SCRAPE FAILED</span>
                        @else
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">LOGS</span>
                        @endif
                    @endif
                </div>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $scrapeProgress }}%</span>
            </div>

            {{-- Progress Bar --}}
            @if($isScraping)
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mb-4">
                <div class="bg-gradient-to-r from-orange-500 to-red-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $scrapeProgress }}%"></div>
            </div>
            @endif

            {{-- Log Output --}}
            <div class="bg-gray-900 rounded-lg p-4 max-h-64 overflow-y-auto font-mono text-sm">
                @forelse($scrapeLogs as $log)
                    <div class="flex gap-2 text-gray-300">
                        <span class="text-gray-500">[{{ $log['time'] }}]</span>
                        <span>{!! e($log['message']) !!}</span>
                    </div>
                @empty
                    <span class="text-gray-500">No logs yet...</span>
                @endforelse
            </div>
        </div>
        @endif

        {{-- Results --}}
        @if(!empty($scrapeResults))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold">📊 Scrape Results</h3>
                @if($scrapeResults['type'] === 'anime')
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $scrapeResults['total_episodes'] ?? 0 }} episodes, {{ $scrapeResults['total_servers'] ?? 0 }} servers
                    </p>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $scrapeResults['total'] ?? 0 }} servers found
                    </p>
                @endif
            </div>
            <div class="p-6">
                @if($scrapeResults['type'] === 'anime' && !empty($scrapeResults['episodes']))
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @foreach($scrapeResults['episodes'] as $ep)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="font-medium mb-2">Episode {{ $ep['episode'] ?? '?' }} - {{ count($ep['servers'] ?? []) }} servers</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($ep['servers'] ?? [] as $server)
                                <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs px-2 py-1 rounded">
                                    {{ $server['name'] }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                @elseif($scrapeResults['type'] === 'episode' && !empty($scrapeResults['servers']))
                    <div class="space-y-2">
                        @foreach($scrapeResults['servers'] as $server)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 flex justify-between items-center">
                            <span class="font-medium">{{ $server['name'] }}</span>
                            <a href="{{ $server['url'] }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm truncate max-w-md">
                                {{ Str::limit($server['url'], 60) }}
                            </a>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Batch Results --}}
        @if(!empty($batchResults))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold">📊 Batch Results</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ count(array_filter($batchResults, fn($r) => $r['success'])) }}/{{ count($batchResults) }} successful
                </p>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left">Anime</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-center">Episodes</th>
                                <th class="px-4 py-2 text-center">Servers</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($batchResults as $result)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-2">
                                    <div>
                                        <p class="font-medium">{{ $result['title'] ?? 'Unknown' }}</p>
                                        <p class="text-xs text-gray-500">{{ Str::limit($result['url'], 50) }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    @if($result['success'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            ✅ Success
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            ❌ Failed
                                        </span>
                                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $result['error'] ?? 'Unknown error' }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center font-medium">{{ $result['episodes'] }}</td>
                                <td class="px-4 py-2 text-center font-medium">{{ $result['servers'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Quick Tips --}}
        <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <h4 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">💡 Tips</h4>
            <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                <li>• Paste URL anime dari AnimeSail (contoh: https://154.26.137.28/anime/one-piece/)</li>
                <li>• Klik "Fetch Episode List" untuk melihat daftar episode dulu</li>
                <li>• "Fetch Video Servers" akan mengambil embed URL (lebih lambat tapi lengkap)</li>
                <li>• Enable "Sync to Database" untuk menyimpan servers ke database lokal</li>
                <li>• Server internal AnimeSail (IP 154.26.137.28) otomatis di-skip</li>
            </ul>
        </div>
    </div>
</x-filament::page>
