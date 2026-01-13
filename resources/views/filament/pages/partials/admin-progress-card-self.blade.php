@php
    $levelEmoji = match($progress['admin_level'] ?? 1) {
        1 => '🥉',
        2 => '🥈',
        3 => '🥇',
        4 => '💎',
        5 => '👑',
        default => '🔰',
    };
    $scoreColor = match(true) {
        $progress['performance_score'] >= 80 => 'text-green-600',
        $progress['performance_score'] >= 60 => 'text-blue-600',
        $progress['performance_score'] >= 40 => 'text-yellow-600',
        $progress['performance_score'] >= 20 => 'text-orange-600',
        default => 'text-red-600',
    };
    $scoreBarHex = match(true) {
        $progress['performance_score'] >= 80 => '#22c55e',
        $progress['performance_score'] >= 60 => '#3b82f6',
        $progress['performance_score'] >= 40 => '#eab308',
        $progress['performance_score'] >= 20 => '#f97316',
        default => '#ef4444',
    };
    $categoryBg = match($progress['category']) {
        'Sync' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
        'Upload' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
        default => 'bg-white dark:bg-gray-800',
    };
    
    // Target progress based on level (Hard Mode)
    $targetMax = $progress['level_target'] ?? 50;
    $targetCurrent = match($progress['category']) {
        'Sync' => $progress['sync_episodes'],
        'Upload' => $progress['upload_episodes'],
        default => $progress['total_episodes'],
    };
    $targetProgress = min(100, ($targetCurrent / $targetMax) * 100);
    $consecutiveMonths = $progress['consecutive_months'] ?? 0;
    $monthsToPromotion = max(0, 6 - $consecutiveMonths);
    $targetBarHex = match($progress['category']) {
        'Sync' => '#3b82f6',
        'Upload' => '#22c55e',
        default => '#6b7280',
    };
@endphp

<div class="p-6 {{ $categoryBg }} rounded-xl border-2">
    {{-- Personal Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center text-3xl">
                {{ $progress['category_emoji'] }}
            </div>
            <div>
                <h2 class="text-2xl font-bold">{{ $progress['admin_name'] }}</h2>
                <div class="flex items-center gap-3 mt-1">
                    <span class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm font-medium">
                        {{ $levelEmoji }} {{ $progress['level_label'] }}
                    </span>
                    <span class="px-3 py-1 {{ $progress['category'] === 'Sync' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }} rounded-full text-sm font-medium">
                        {{ $progress['category_emoji'] }} Admin {{ $progress['category'] }}
                    </span>
                </div>
            </div>
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-500">Performance Score</div>
            <div class="text-4xl font-bold {{ $scoreColor }}">{{ $progress['performance_score'] }}/100</div>
        </div>
    </div>

    {{-- Main Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        {{-- Target Progress --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl text-center">
            <div class="text-sm text-gray-500 mb-1">🎯 Target Bulan Ini</div>
            <div class="text-3xl font-bold" style="color: {{ $targetBarHex }}">
                {{ $targetCurrent }}/{{ $targetMax }}
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="h-2.5 rounded-full" style="width: {{ $targetProgress }}%; background-color: {{ $targetBarHex }}"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1">{{ number_format($targetProgress, 1) }}% tercapai</div>
        </div>

        {{-- Active Days --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl text-center">
            <div class="text-sm text-gray-500 mb-1">📅 Hari Aktif</div>
            <div class="text-3xl font-bold text-orange-600">{{ $progress['active_days'] }}</div>
            <div class="text-xs text-gray-500 mt-1">dari {{ $progress['days_passed'] }} hari</div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="bg-orange-500 h-2.5 rounded-full" style="width: {{ $progress['consistency_score'] }}%"></div>
            </div>
        </div>

        {{-- Approval Rate --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl text-center">
            <div class="text-sm text-gray-500 mb-1">✅ Approval Rate</div>
            <div class="text-3xl font-bold text-purple-600">{{ $progress['approval_rate'] }}%</div>
            <div class="text-xs text-gray-500 mt-1">{{ $progress['approved_count'] }} approved</div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="bg-purple-500 h-2.5 rounded-full" style="width: {{ $progress['approval_rate'] }}%"></div>
            </div>
        </div>

        {{-- Total Earnings --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl text-center">
            <div class="text-sm text-gray-500 mb-1">💰 Total Earnings</div>
            <div class="text-2xl font-bold text-green-600">Rp {{ number_format($progress['total_earnings'], 0, ',', '.') }}</div>
            <div class="text-xs text-gray-500 mt-1">Bulan ini</div>
        </div>
    </div>

    {{-- Performance Score Bar --}}
    <div class="p-4 bg-white dark:bg-gray-800 rounded-xl mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="font-semibold">Performance Score</span>
            <span class="text-lg font-bold" style="color: {{ $scoreBarHex }}">{{ $progress['performance_score'] }}/100</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
            <div class="h-4 rounded-full transition-all" style="width: {{ $progress['performance_score'] }}%; background-color: {{ $scoreBarHex }}"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-500 mt-2">
            <span>0</span>
            <span class="text-red-500">20</span>
            <span class="text-orange-500">40</span>
            <span class="text-yellow-500">60</span>
            <span class="text-green-500">80</span>
            <span>100</span>
        </div>
    </div>

    {{-- Detailed Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
            <div class="text-2xl font-bold text-blue-600">{{ $progress['sync_episodes'] }}</div>
            <div class="text-xs text-gray-500">🔄 Sync Episodes</div>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
            <div class="text-2xl font-bold text-green-600">{{ $progress['upload_episodes'] }}</div>
            <div class="text-xs text-gray-500">📤 Upload Episodes</div>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
            <div class="text-2xl font-bold text-yellow-600">{{ $progress['pending_count'] }}</div>
            <div class="text-xs text-gray-500">⏳ Pending</div>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
            <div class="text-2xl font-bold text-purple-600">{{ $progress['approved_count'] }}</div>
            <div class="text-xs text-gray-500">✅ Approved</div>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
            <div class="text-2xl font-bold text-red-600">{{ $progress['rejected_count'] ?? 0 }}</div>
            <div class="text-xs text-gray-500">❌ Rejected</div>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
            <div class="text-2xl font-bold text-gray-600">{{ $progress['avg_per_day'] }}</div>
            <div class="text-xs text-gray-500">📊 Avg/Day</div>
        </div>
    </div>

    {{-- Tips Section --}}
    <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
        <h4 class="font-semibold text-yellow-800 dark:text-yellow-400 mb-2">💡 Tips Naik Level (Hard Mode):</h4>
        <ul class="text-sm text-yellow-700 dark:text-yellow-500 space-y-1">
            <li>• 🎯 <strong>Target Level {{ $progress['admin_level'] }}:</strong> {{ $targetMax }} {{ $progress['category'] === 'Sync' ? 'sync' : 'upload' }}/bulan</li>
            <li>• 📅 <strong>Hari Aktif:</strong> Minimal 26 hari/bulan (sekarang: {{ $progress['active_days'] }} hari)</li>
            <li>• ✅ <strong>Approval Rate:</strong> Harus 100% (sekarang: {{ $progress['approval_rate'] }}%)</li>
            <li>• 🔥 <strong>Streak:</strong> {{ $consecutiveMonths }}/6 bulan berturut-turut. {{ $monthsToPromotion > 0 ? $monthsToPromotion . ' bulan lagi untuk naik level!' : '🎉 Siap naik level!' }}</li>
            <li>• ⚠️ Gagal memenuhi 1 syarat = streak reset ke 0!</li>
        </ul>
    </div>
</div>
