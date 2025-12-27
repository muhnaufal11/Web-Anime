<x-filament::page>
    <div class="grid gap-4">
        @forelse($animes as $index => $anime)
            <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <span class="flex items-center justify-center w-8 h-8 font-bold text-gray-400 bg-gray-100 rounded-full dark:bg-gray-700">
                        {{ $index + 1 }}
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $anime->title }}</h3>
                        <p class="text-xs text-gray-500 italic">Belum ada episode/video server</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    {{-- Tombol Copy Judul --}}
                    <x-filament::button 
                        size="sm" 
                        color="secondary" 
                        icon="heroicon-s-clipboard-copy"
                        onclick="navigator.clipboard.writeText('{{ $anime->title }}'); alert('Judul disalin: {{ $anime->title }}')"
                    >
                        Copy Judul
                    </x-filament::button>

                    {{-- Tombol Edit: Menggunakan $anime->id untuk memastikan parameter record terisi --}}
                    <x-filament::button 
                        size="sm" 
                        tag="a" 
                        href="{{ \App\Filament\Resources\AnimeResource::getUrl('edit', ['record' => $anime->id]) }}"
                        icon="heroicon-s-pencil"
                    >
                        Edit
                    </x-filament::button>
                </div>
            </div>
        @empty
            <div class="p-10 text-center bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 dark:bg-gray-900">
                <p class="text-gray-500">✅ Semua anime sudah memiliki episode dan video server.</p>
            </div>
        @endforelse
    </div>
</x-filament::page>