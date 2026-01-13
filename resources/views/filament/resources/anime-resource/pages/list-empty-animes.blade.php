<x-filament::page>
    <div class="space-y-6">
        {{-- BAGIAN FILTER TAHUN --}}
        <div class="flex items-center gap-2 p-4 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <label for="filter-year" class="text-sm font-medium text-gray-700 dark:text-gray-200">Filter Tahun:</label>
            <select 
                id="filter-year" 
                wire:model="year" 
                class="block w-full max-w-xs text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            >
                <option value="">Semua Tahun</option>
                @foreach($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>

        {{-- HEADER INFO --}}
        <div class="flex items-center gap-3 pb-2 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-center w-10 h-10 bg-red-100 rounded-lg dark:bg-red-900/30">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Anime Belum Punya Video Server</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total: {{ number_format($totalAnimes ?? 0) }} anime perlu ditambahkan video</p>
            </div>
        </div>

        {{-- DAFTAR ANIME --}}
        <div class="grid gap-3">
            @forelse($animes ?? [] as $index => $anime)
                <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <span class="flex items-center justify-center w-8 h-8 font-bold text-gray-500 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-gray-400 text-xs">
                            {{ $index + 1 }}
                        </span>
                        <div class="relative w-12 h-16 overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-700">
                            @if($anime->poster_image)
                                <img 
                                    src="{{ asset('storage/' . $anime->poster_image) }}" 
                                    alt="Poster {{ $anime->title }}" 
                                    class="object-cover w-full h-full"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex items-center justify-center w-full h-full text-xs text-gray-400">No Poster</div>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $anime->title }}</h3>
                            <p class="text-xs space-x-2">
                                @if($anime->episodes_count == 0)
                                    <span class="text-red-500 font-medium">Belum ada episode sama sekali</span>
                                @else
                                    @if($anime->episodes_no_video > 0)
                                        <span class="text-red-500 font-medium">{{ $anime->episodes_no_video }} ep tanpa video</span>
                                    @endif
                                    @if($anime->episodes_no_sync > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                            🔄 {{ $anime->episodes_no_sync }} ep butuh sync
                                        </span>
                                    @endif
                                    @if($anime->episodes_no_manual > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300">
                                            📤 {{ $anime->episodes_no_manual }} ep butuh manual
                                        </span>
                                    @endif
                                @endif
                                <span class="text-gray-400">({{ $anime->release_year ?? '-' }})</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <x-filament::button
                            size="sm"
                            color="secondary"
                            icon="heroicon-s-clipboard-copy"
                            onclick="copyToClipboard('{{ addslashes($anime->title) }}')"
                        >
                            Copy Judul
                        </x-filament::button>

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
                <div class="p-10 text-center bg-green-50 rounded-xl border-2 border-dashed border-green-300 dark:bg-green-900/20 dark:border-green-700">
                    <p class="text-green-600 dark:text-green-400 font-medium">✅ Semua anime sudah punya video server!</p>
                </div>
            @endforelse
        </div>

        @if($animes instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="pt-4">
                {{ $animes->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    {{-- Script Copy --}}
    <script>
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { status: 'success', message: 'Berhasil disalin!' }
                    }));
                });
            } else {
                let textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { status: 'success', message: 'Berhasil disalin!' }
                    }));
                } catch (err) {
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { status: 'danger', message: 'Gagal menyalin!' }
                    }));
                }
                document.body.removeChild(textArea);
            }
        }
    </script>
</x-filament::page>