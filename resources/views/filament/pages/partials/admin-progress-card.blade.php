@php
    $rankEmoji = match($index) {
        0 => '🥇',
        1 => '🥈',
        2 => '🥉',
        default => '#' . ($index + 1),
    };
    $levelEmoji = match($progress['admin_level'] ?? 1) {
        1 => '🥉',
        2 => '🥈',
        3 => '🥇',
        4 => '💎',
        5 => '👑',
        default => '🔰',
    };
    $scoreBarHex = match(true) {
        $progress['performance_score'] >= 80 => '#22c55e',
        $progress['performance_score'] >= 60 => '#3b82f6',
        $progress['performance_score'] >= 40 => '#eab308',
        $progress['performance_score'] >= 20 => '#f97316',
        default => '#ef4444',
    };
    $actionBorder = match($progress['level_recommendation']['action']) {
        'promote' => 'border-l-4 border-l-green-500',
        'demote' => 'border-l-4 border-l-red-500',
        default => '',
    };
    $categoryBg = match($progress['category']) {
        'Sync' => 'bg-blue-50 dark:bg-blue-900/10',
        'Upload' => 'bg-green-50 dark:bg-green-900/10',
        default => 'bg-white dark:bg-gray-800',
    };
    
    // Target progress based on level (Hard Mode)
    $levelTarget = $progress['level_target'] ?? 50;
    $targetCurrent = match($progress['category']) {
        'Sync' => $progress['sync_episodes'],
        'Upload' => $progress['upload_episodes'],
        default => $progress['total_episodes'],
    };
    $targetProgress = min(100, ($targetCurrent / $levelTarget) * 100);
    $targetCount = $targetCurrent . '/' . $levelTarget;
    $targetBarHex = match($progress['category']) {
        'Sync' => '#3b82f6',
        'Upload' => '#22c55e',
        default => '#6b7280',
    };
@endphp

<div class="p-5 {{ $categoryBg }} rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 {{ $actionBorder }}">
    <div class="flex flex-col lg:flex-row lg:items-center gap-4">
        {{-- Rank & Name --}}
        <div class="flex items-center gap-3 lg:w-1/4">
            <span class="text-2xl">{{ $rankEmoji }}</span>
            <div>
                <h3 class="font-bold text-lg">{{ $progress['admin_name'] }}</h3>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">{{ $levelEmoji }} {{ $progress['level_label'] }}</span>
                    <span class="px-2 py-0.5 text-xs rounded {{ $progress['category'] === 'Sync' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                        {{ $progress['category_emoji'] }} {{ $progress['category'] }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Performance Score --}}
        <div class="lg:w-1/5">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-sm font-medium">Score</span>
                <span class="text-2xl font-bold">{{ $progress['performance_score'] }}/100</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                <div class="h-3 rounded-full transition-all" style="width: {{ $progress['performance_score'] }}%; background-color: {{ $scoreBarHex }}"></div>
            </div>
        </div>

        {{-- Target Progress --}}
        <div class="lg:w-1/5">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-sm font-medium">Target</span>
                <span class="text-lg font-bold">{{ $targetCount }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                <div class="h-3 rounded-full transition-all" style="width: {{ $targetProgress }}%; background-color: {{ $targetBarHex }}"></div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="flex flex-wrap gap-3 lg:w-1/4">
            @if($progress['category'] === 'Sync')
                <div class="text-center">
                    <div class="text-xl font-bold text-blue-600">{{ $progress['sync_episodes'] }}</div>
                    <div class="text-xs text-gray-500">🔄 Sync</div>
                </div>
            @else
                <div class="text-center">
                    <div class="text-xl font-bold text-green-600">{{ $progress['upload_episodes'] }}</div>
                    <div class="text-xs text-gray-500">📤 Upload</div>
                </div>
            @endif
            <div class="text-center">
                <div class="text-xl font-bold text-orange-600">{{ $progress['active_days'] }}/{{ $progress['days_passed'] }}</div>
                <div class="text-xs text-gray-500">📅 Aktif</div>
            </div>
            <div class="text-center">
                <div class="text-xl font-bold text-purple-600">Rp {{ number_format($progress['total_earnings'], 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500">💰 Earnings</div>
            </div>
        </div>

        {{-- Recommendation --}}
        <div class="lg:w-1/6">
            @if($progress['level_recommendation']['action'] === 'promote')
                <div class="px-3 py-2 bg-green-100 dark:bg-green-900/30 rounded-lg text-center">
                    <span class="text-green-700 dark:text-green-400 font-semibold">⬆️ Naik Level</span>
                    <p class="text-xs text-green-600 dark:text-green-500 mt-1">→ Level {{ $progress['level_recommendation']['recommended_level'] }}</p>
                </div>
            @elseif($progress['level_recommendation']['action'] === 'demote')
                <div class="px-3 py-2 bg-red-100 dark:bg-red-900/30 rounded-lg text-center">
                    <span class="text-red-700 dark:text-red-400 font-semibold">⬇️ Turun Level</span>
                    <p class="text-xs text-red-600 dark:text-red-500 mt-1">→ Level {{ $progress['level_recommendation']['recommended_level'] }}</p>
                </div>
            @else
                <div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-center">
                    <span class="text-gray-600 dark:text-gray-400 font-semibold">➡️ Maintain</span>
                    <p class="text-xs text-gray-500 mt-1">Level OK</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Detail Stats Row --}}
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
        <div>
            <span class="text-gray-500">Consistency:</span>
            <span class="font-semibold ml-1">{{ $progress['consistency_score'] }}%</span>
        </div>
        <div>
            <span class="text-gray-500">Approval:</span>
            <span class="font-semibold ml-1">{{ $progress['approval_rate'] }}%</span>
        </div>
        <div>
            <span class="text-gray-500">Avg/Day:</span>
            <span class="font-semibold ml-1">{{ $progress['avg_per_day'] }} ep</span>
        </div>
        <div>
            <span class="text-gray-500">Pending:</span>
            <span class="font-semibold ml-1 text-yellow-600">{{ $progress['pending_count'] }}</span>
        </div>
        <div>
            <span class="text-gray-500">Approved:</span>
            <span class="font-semibold ml-1 text-green-600">{{ $progress['approved_count'] }}</span>
        </div>
    </div>
</div>
