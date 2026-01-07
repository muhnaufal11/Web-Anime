<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Advertisement;

echo "=== Membuat Sample Iklan untuk Semua Posisi ===\n\n";

$sampleAds = [
    // Header Ads
    [
        'name' => 'Sample Header Top Banner',
        'position' => 'header_top',
        'type' => 'html',
        'size' => '728x90',
        'code' => <<<HTML
<div class="w-full bg-gradient-to-r from-blue-900 via-purple-900 to-blue-900 py-2">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-center gap-4 text-white">
            <span class="text-yellow-400 text-xl">⭐</span>
            <p class="text-sm font-medium">Slot Iklan Header Top - 728x90 Leaderboard</p>
            <span class="px-2 py-0.5 bg-yellow-500 text-black text-xs font-bold rounded">ADS</span>
        </div>
    </div>
</div>
HTML,
    ],
    [
        'name' => 'Sample Header Bottom Banner',
        'position' => 'header_bottom',
        'type' => 'html',
        'size' => '728x90',
        'code' => <<<HTML
<div class="max-w-7xl mx-auto px-4 py-3">
    <div class="bg-gradient-to-r from-emerald-600/20 to-teal-600/20 border border-emerald-500/30 rounded-xl p-4 text-center">
        <div class="flex items-center justify-center gap-3">
            <span class="text-2xl">📢</span>
            <div>
                <p class="text-white font-bold">Slot Iklan Header Bottom</p>
                <p class="text-gray-400 text-xs">728x90 Leaderboard - Google AdSense</p>
            </div>
            <span class="px-2 py-1 bg-emerald-500/20 text-emerald-400 text-xs font-bold rounded border border-emerald-500/30">IKLAN</span>
        </div>
    </div>
</div>
HTML,
    ],

    // Sidebar Ads
    [
        'name' => 'Sample Sidebar Top',
        'position' => 'sidebar_top',
        'type' => 'html',
        'size' => '300x250',
        'code' => <<<HTML
<div class="bg-gradient-to-br from-orange-600/20 to-red-600/20 border border-orange-500/30 rounded-xl p-4">
    <div class="text-center">
        <div class="w-full h-32 bg-black/30 rounded-lg flex items-center justify-center mb-3">
            <div class="text-center">
                <span class="text-4xl">🎯</span>
                <p class="text-gray-400 text-xs mt-2">300x250</p>
            </div>
        </div>
        <p class="text-white text-sm font-bold">Sidebar Top Ad</p>
        <p class="text-orange-400 text-xs">Medium Rectangle</p>
    </div>
</div>
HTML,
    ],
    [
        'name' => 'Sample Sidebar Bottom',
        'position' => 'sidebar_bottom',
        'type' => 'html',
        'size' => '300x600',
        'code' => <<<HTML
<div class="bg-gradient-to-br from-pink-600/20 to-purple-600/20 border border-pink-500/30 rounded-xl p-4">
    <div class="text-center">
        <div class="w-full h-48 bg-black/30 rounded-lg flex items-center justify-center mb-3">
            <div class="text-center">
                <span class="text-5xl">📱</span>
                <p class="text-gray-400 text-xs mt-2">300x600</p>
            </div>
        </div>
        <p class="text-white text-sm font-bold">Sidebar Bottom Ad</p>
        <p class="text-pink-400 text-xs">Half Page / Large Skyscraper</p>
    </div>
</div>
HTML,
    ],

    // Content Ads
    [
        'name' => 'Sample Content Top',
        'position' => 'content_top',
        'type' => 'html',
        'size' => '728x90',
        'code' => <<<HTML
<div class="bg-gradient-to-r from-cyan-600/20 to-blue-600/20 border border-cyan-500/30 rounded-xl p-4 my-4">
    <div class="flex items-center justify-center gap-4">
        <span class="text-3xl">📺</span>
        <div class="text-center">
            <p class="text-white font-bold">Iklan Konten Atas</p>
            <p class="text-cyan-400 text-xs">728x90 Leaderboard</p>
        </div>
        <span class="px-2 py-1 bg-cyan-500/20 text-cyan-300 text-xs rounded border border-cyan-500/30">AD</span>
    </div>
</div>
HTML,
    ],
    [
        'name' => 'Sample Content Middle',
        'position' => 'content_middle',
        'type' => 'html',
        'size' => '336x280',
        'code' => <<<HTML
<div class="bg-gradient-to-r from-violet-600/20 to-fuchsia-600/20 border border-violet-500/30 rounded-xl p-6 my-6">
    <div class="flex flex-col md:flex-row items-center justify-center gap-4">
        <div class="w-20 h-20 bg-black/30 rounded-lg flex items-center justify-center">
            <span class="text-4xl">🎮</span>
        </div>
        <div class="text-center md:text-left">
            <p class="text-white font-bold text-lg">Iklan Tengah Konten</p>
            <p class="text-violet-400 text-sm">336x280 Large Rectangle</p>
            <p class="text-gray-500 text-xs mt-1">Posisi strategis di tengah artikel/list</p>
        </div>
    </div>
</div>
HTML,
    ],
    [
        'name' => 'Sample Content Bottom',
        'position' => 'content_bottom',
        'type' => 'html',
        'size' => '728x90',
        'code' => <<<HTML
<div class="bg-gradient-to-r from-amber-600/20 to-yellow-600/20 border border-amber-500/30 rounded-xl p-4 my-4">
    <div class="flex items-center justify-center gap-4">
        <span class="text-3xl">⬇️</span>
        <div class="text-center">
            <p class="text-white font-bold">Iklan Konten Bawah</p>
            <p class="text-amber-400 text-xs">728x90 Leaderboard</p>
        </div>
    </div>
</div>
HTML,
    ],

    // Footer Ad
    [
        'name' => 'Sample Footer Banner',
        'position' => 'footer',
        'type' => 'html',
        'size' => '970x90',
        'code' => <<<HTML
<div class="w-full bg-gradient-to-r from-gray-800 to-gray-900 py-4 border-t border-gray-700">
    <div class="max-w-7xl mx-auto px-4">
        <div class="bg-black/30 rounded-xl p-4 text-center border border-gray-600/30">
            <div class="flex items-center justify-center gap-4">
                <span class="text-2xl">🏷️</span>
                <div>
                    <p class="text-white font-bold">Footer Advertisement</p>
                    <p class="text-gray-400 text-xs">970x90 Large Leaderboard</p>
                </div>
            </div>
        </div>
    </div>
</div>
HTML,
    ],

    // Video Ads
    [
        'name' => 'Sample Video Overlay',
        'position' => 'video_overlay',
        'type' => 'html',
        'size' => '640x480',
        'pages' => ['watch'],
        'code' => <<<HTML
<div class="flex flex-col items-center gap-4">
    <div class="bg-gradient-to-br from-red-600 via-purple-600 to-blue-600 rounded-2xl p-8 text-center max-w-xl shadow-2xl">
        <div class="w-24 h-24 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-4">
            <span class="text-5xl">🎬</span>
        </div>
        <h3 class="text-2xl font-black text-white mb-2">Video Sponsor</h3>
        <p class="text-white/80 mb-4">Iklan overlay ini muncul sebelum video diputar</p>
        <div class="bg-black/30 rounded-xl p-4 border border-white/10">
            <p class="text-white/60 text-sm">Slot untuk Google AdSense</p>
            <p class="text-white/40 text-xs mt-1">atau iklan video kustom</p>
        </div>
    </div>
</div>
HTML,
    ],
    [
        'name' => 'Sample Video Before',
        'position' => 'video_before',
        'type' => 'html',
        'size' => '728x90',
        'pages' => ['watch'],
        'code' => <<<HTML
<div class="max-w-7xl mx-auto px-4 py-2">
    <div class="bg-gradient-to-r from-indigo-600/20 to-blue-600/20 border border-indigo-500/30 rounded-lg p-3">
        <div class="flex items-center justify-center gap-3">
            <span class="text-xl">▶️</span>
            <p class="text-white text-sm font-medium">Iklan Sebelum Video Player</p>
            <span class="px-2 py-0.5 bg-indigo-500/30 text-indigo-300 text-xs rounded">728x90</span>
        </div>
    </div>
</div>
HTML,
    ],
    [
        'name' => 'Sample Video After',
        'position' => 'video_after',
        'type' => 'html',
        'size' => '728x90',
        'pages' => ['watch'],
        'code' => <<<HTML
<div class="bg-gradient-to-r from-teal-600/20 to-green-600/20 border border-teal-500/30 rounded-lg p-3 mt-4">
    <div class="flex items-center justify-center gap-3">
        <span class="text-xl">⏹️</span>
        <p class="text-white text-sm font-medium">Iklan Setelah Video Player</p>
        <span class="px-2 py-0.5 bg-teal-500/30 text-teal-300 text-xs rounded">728x90</span>
    </div>
</div>
HTML,
    ],

    // Popup Ad
    [
        'name' => 'Sample Popup Ad',
        'position' => 'popup',
        'type' => 'html',
        'size' => '300x250',
        'code' => <<<HTML
<div class="bg-gradient-to-br from-rose-600/30 to-pink-600/30 border-2 border-rose-500/50 rounded-2xl p-6 text-center">
    <div class="w-16 h-16 mx-auto bg-rose-500/20 rounded-full flex items-center justify-center mb-4">
        <span class="text-3xl">🎁</span>
    </div>
    <h4 class="text-white font-bold text-lg mb-2">Popup Advertisement</h4>
    <p class="text-gray-300 text-sm mb-4">Promo spesial untuk kamu!</p>
    <div class="bg-black/20 rounded-lg p-3">
        <p class="text-rose-400 text-xs">300x250 Medium Rectangle</p>
    </div>
</div>
HTML,
    ],

    // Floating Ad
    [
        'name' => 'Sample Floating Ad',
        'position' => 'floating',
        'type' => 'html',
        'size' => '320x50',
        'code' => <<<HTML
<div class="bg-gradient-to-r from-slate-800 to-slate-900 border border-slate-600/50 rounded-lg p-3 shadow-xl">
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xl">📌</span>
            <div>
                <p class="text-white text-sm font-medium">Floating Ad</p>
                <p class="text-gray-400 text-xs">320x50 Mobile Banner</p>
            </div>
        </div>
        <button onclick="this.closest('.floating-ad-wrapper')?.remove()" class="text-gray-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>
HTML,
    ],
];

$created = 0;
foreach ($sampleAds as $adData) {
    $ad = Advertisement::updateOrCreate(
        ['name' => $adData['name']],
        [
            'position' => $adData['position'],
            'type' => $adData['type'],
            'size' => $adData['size'],
            'code' => $adData['code'],
            'pages' => $adData['pages'] ?? null,
            'is_active' => true,
            'show_on_mobile' => true,
            'show_on_desktop' => true,
            'order' => 0,
        ]
    );
    echo "✅ {$ad->position}: {$ad->name}\n";
    $created++;
}

echo "\n=== Selesai! ===\n";
echo "Total {$created} sample iklan berhasil dibuat/diupdate.\n\n";

echo "Daftar Posisi Iklan:\n";
echo "────────────────────────────────────────\n";
echo "📍 header_top     - Banner atas navbar\n";
echo "📍 header_bottom  - Banner bawah navbar\n";
echo "📍 sidebar_top    - Sidebar atas (300x250)\n";
echo "📍 sidebar_bottom - Sidebar bawah (300x600)\n";
echo "📍 content_top    - Atas konten\n";
echo "📍 content_middle - Tengah konten\n";
echo "📍 content_bottom - Bawah konten\n";
echo "📍 footer         - Footer website\n";
echo "📍 video_overlay  - Overlay video dengan timer\n";
echo "📍 video_before   - Sebelum video player\n";
echo "📍 video_after    - Setelah video player\n";
echo "📍 popup          - Popup modal\n";
echo "📍 floating       - Floating/sticky bottom\n";
