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

        {{-- DAFTAR ANIME --}}
        <div class="grid gap-4">
            @forelse($animes as $index => $anime)
                <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <span class="flex items-center justify-center w-8 h-8 font-bold text-gray-400 bg-gray-100 rounded-full dark:bg-gray-700 text-xs">
                            {{ $index + 1 }}
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $anime->title }}</h3>
                            <p class="text-xs italic">
                                @if($anime->episodes_count == 0)
                                    <span class="text-red-500 font-medium">Belum ada episode sama sekali</span>
                                @else
                                    <span class="text-orange-500 font-medium">{{ $anime->missing_video_count }} episode belum punya link video</span>
                                @endif
                                <span class="text-gray-400 ml-1">({{ $anime->release_year ?? 'Tahun TBD' }})</span>
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
                <div class="p-10 text-center bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                    <p class="text-gray-500 italic">Tidak ada anime kosong ditemukan. ✅</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Script Copy dengan Notifikasi Filament Toast --}}
    <script>
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    new FilamentNotification()
                        .title('Berhasil disalin!')
                        .body('Judul "' + text + '" sudah ada di clipboard.')
                        .success()
                        .send();
                });
            } else {
                let textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                textArea.style.top = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    new FilamentNotification()
                        .title('Berhasil disalin!')
                        .success()
                        .send();
                } catch (err) {
                    new FilamentNotification()
                        .title('Gagal menyalin')
                        .danger()
                        .send();
                }
                document.body.removeChild(textArea);
            }
        }
    </script>
</x-filament::page>