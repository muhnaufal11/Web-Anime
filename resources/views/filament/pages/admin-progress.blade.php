<x-filament::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="p-6 bg-gradient-to-r from-primary-500 to-primary-700 rounded-xl text-white">
            <h2 class="text-2xl font-bold">📊 {{ $isSuperAdmin ? 'Admin Performance Dashboard' : 'My Performance' }}</h2>
            <p class="text-primary-100">{{ $month }} - {{ $isSuperAdmin ? 'Evaluasi performa untuk naik level' : 'Lihat progress kerja kamu' }}</p>
        </div>

        {{-- Legend - Only for Superadmin --}}
        @if($isSuperAdmin)
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap gap-6">
                <div>
                    <h3 class="font-semibold mb-2">Level Badges:</h3>
                    <div class="flex flex-wrap gap-3 text-sm">
                        <span>🥉 Level 1</span>
                        <span>🥈 Level 2</span>
                        <span>🥇 Level 3</span>
                        <span>💎 Level 4</span>
                        <span>👑 Level 5</span>
                    </div>
                </div>
                <div>
                    <h3 class="font-semibold mb-2">Kategori Admin:</h3>
                    <div class="flex flex-wrap gap-3 text-sm">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">🔄 Admin Sync</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded">📤 Admin Upload</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @php
            $syncAdmins = collect($adminProgress)->filter(fn($p) => $p['category'] === 'Sync');
            $uploadAdmins = collect($adminProgress)->filter(fn($p) => $p['category'] === 'Upload');
            $otherAdmins = collect($adminProgress)->filter(fn($p) => !in_array($p['category'], ['Sync', 'Upload']));
        @endphp

        @if($isSuperAdmin)
            {{-- SUPERADMIN VIEW: Show all admins grouped by category --}}
            
            {{-- Admin Sync Section --}}
            @if($syncAdmins->count() > 0)
            <div class="space-y-4">
                <div class="flex items-center gap-3 pb-2 border-b-2 border-blue-500">
                    <span class="text-2xl">🔄</span>
                    <div>
                        <h2 class="text-xl font-bold text-blue-600 dark:text-blue-400">Admin Sync</h2>
                        <p class="text-sm text-gray-500">Target per Level: Lv1=200 | Lv2=350 | Lv3=500 | Lv4=700 | Lv5=900 sync</p>
                    </div>
                </div>

                <div class="grid gap-4">
                    @foreach($syncAdmins as $index => $progress)
                        @include('filament.pages.partials.admin-progress-card', ['progress' => $progress, 'index' => $loop->index])
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Admin Upload Section --}}
            @if($uploadAdmins->count() > 0)
            <div class="space-y-4 mt-8">
                <div class="flex items-center gap-3 pb-2 border-b-2 border-green-500">
                    <span class="text-2xl">📤</span>
                    <div>
                        <h2 class="text-xl font-bold text-green-600 dark:text-green-400">Admin Upload</h2>
                        <p class="text-sm text-gray-500">Target per Level: Lv1=50 | Lv2=80 | Lv3=110 | Lv4=140 | Lv5=180 upload</p>
                    </div>
                </div>

                <div class="grid gap-4">
                    @foreach($uploadAdmins as $index => $progress)
                        @include('filament.pages.partials.admin-progress-card', ['progress' => $progress, 'index' => $loop->index])
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Other Admins (Legacy) --}}
            @if($otherAdmins->count() > 0)
            <div class="space-y-4 mt-8">
                <div class="flex items-center gap-3 pb-2 border-b-2 border-gray-500">
                    <span class="text-2xl">👤</span>
                    <div>
                        <h2 class="text-xl font-bold text-gray-600 dark:text-gray-400">Admin Lainnya</h2>
                        <p class="text-sm text-gray-500">Admin dengan role legacy</p>
                    </div>
                </div>

                <div class="grid gap-4">
                    @foreach($otherAdmins as $index => $progress)
                        @include('filament.pages.partials.admin-progress-card', ['progress' => $progress, 'index' => $loop->index])
                    @endforeach
                </div>
            </div>
            @endif
        @else
            {{-- ADMIN VIEW: Show only their own progress --}}
            @foreach($adminProgress as $progress)
                @include('filament.pages.partials.admin-progress-card-self', ['progress' => $progress])
            @endforeach
        @endif

        {{-- Scoring Guide --}}
        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg mt-8">
            <h3 class="font-semibold mb-3">📋 Kriteria Penilaian (Hard Mode):</h3>
            <div class="grid md:grid-cols-2 gap-4 text-sm">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="font-semibold text-blue-700 dark:text-blue-400 mb-2">🔄 Admin Sync</h4>
                    <ul class="text-gray-600 dark:text-gray-400 space-y-1">
                        <li>• Target: Lv1=200 | Lv2=350 | Lv3=500 | Lv4=700 | Lv5=900</li>
                        <li>• Cap Gaji: 300k → 525k → 750k → 1.05jt → 1.35jt</li>
                        <li>• Minimal 26 hari aktif & 100% approval rate</li>
                    </ul>
                </div>
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <h4 class="font-semibold text-green-700 dark:text-green-400 mb-2">📤 Admin Upload</h4>
                    <ul class="text-gray-600 dark:text-gray-400 space-y-1">
                        <li>• Target: Lv1=50 | Lv2=80 | Lv3=110 | Lv4=140 | Lv5=180</li>
                        <li>• Cap Gaji: 500k → 800k → 1.1jt → 1.4jt → 1.8jt</li>
                        <li>• Minimal 26 hari aktif & 100% approval rate</li>
                    </ul>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 text-sm">
                <strong>🔥 Syarat Naik Level (Hard Mode):</strong>
                <span class="ml-2 text-green-600">6 bulan berturut-turut memenuhi SEMUA syarat → Naik 1 level</span>
                <br class="md:hidden">
                <span class="md:mx-2 md:inline block mt-1 md:mt-0">|</span>
                <span class="text-red-600">Gagal 1 syarat = streak reset ke 0</span>
            </div>
        </div>
    </div>
</x-filament::page>
