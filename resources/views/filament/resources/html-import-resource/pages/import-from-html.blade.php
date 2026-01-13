<x-filament::page>
    <form wire:submit.prevent="startImport">
        {{ $this->form }}
        
        @if($parsedData)
            <x-filament::card class="mt-6">
                <h3 class="text-lg font-bold mb-4">📋 Preview Data</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        @if(!empty($parsedData['poster_url']))
                            <img src="{{ $parsedData['poster_url'] }}" 
                                 alt="Poster" 
                                 class="w-48 rounded-lg shadow-lg mb-4">
                        @endif
                    </div>
                    
                    <div class="space-y-2">
                        <p><strong>Judul:</strong> {{ $parsedData['title'] }}</p>
                        <p><strong>Tipe:</strong> {{ $parsedData['type'] }}</p>
                        <p><strong>Status:</strong> {{ $parsedData['status'] }}</p>
                        <p><strong>Rating:</strong> {{ $parsedData['rating'] }}</p>
                        <p><strong>Tahun:</strong> {{ $parsedData['release_year'] ?? '-' }}</p>
                        <p><strong>Genre:</strong> {{ implode(', ', $parsedData['genres']) }}</p>
                        <p><strong>Total Episode:</strong> {{ count($parsedData['episodes']) }}</p>
                    </div>
                </div>
                
                @if(!empty($parsedData['synopsis']))
                    <div class="mt-4">
                        <strong>Synopsis:</strong>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($parsedData['synopsis'], 500) }}</p>
                    </div>
                @endif
                
                @if(count($parsedData['episodes']) > 0)
                    <div class="mt-4">
                        <strong>Daftar Episode:</strong>
                        <div class="mt-2 max-h-48 overflow-y-auto bg-gray-100 dark:bg-gray-800 rounded-lg p-3">
                            @foreach($parsedData['episodes'] as $ep)
                                <div class="flex justify-between items-center py-1 border-b border-gray-200 dark:border-gray-700 last:border-0">
                                    <span>Episode {{ $ep['number'] }}</span>
                                    <a href="{{ $ep['url'] }}" target="_blank" class="text-primary-500 text-sm hover:underline">
                                        {{ Str::limit($ep['url'], 50) }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <div class="mt-6 flex gap-3">
                    <x-filament::button type="submit" color="success" :disabled="$isImporting">
                        @if($isImporting)
                            <span class="w-4 h-4 mr-2 animate-spin">⏳</span>
                            Importing...
                        @else
                            🚀 Mulai Import
                        @endif
                    </x-filament::button>
                    <x-filament::button color="primary" wire:click="downloadAllEpisodeHtmls" type="button">
                        ⬇️ Download Semua HTML Episode
                    </x-filament::button>
                    
                    <x-filament::button type="button" color="secondary" wire:click="clearData">
                        🗑️ Clear
                    </x-filament::button>
                </div>
            </x-filament::card>
        @endif
        
        @if(count($importProgress) > 0)
            <x-filament::card class="mt-6">
                <h3 class="text-lg font-bold mb-4">📊 Progress Import</h3>
                <div class="bg-gray-900 text-green-400 font-mono text-sm p-4 rounded-lg max-h-96 overflow-y-auto">
                    @foreach($importProgress as $log)
                        <div>{{ $log }}</div>
                    @endforeach
                </div>
            </x-filament::card>
        @endif
    </form>
</x-filament::page>
