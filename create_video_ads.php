<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Advertisement;

echo "=== Membuat Video Overlay Test Ad ===\n\n";

// Video Overlay Ad (dengan timer)
$videoOverlay = Advertisement::updateOrCreate(
    ['name' => 'Test Video Overlay Ad'],
    [
        'position' => 'video_overlay',
        'type' => 'image',
        'code' => null,
        'image_path' => null, // Akan menggunakan placeholder
        'link' => 'https://example.com/sponsor',
        'size' => '728x90',
        'pages' => ['watch'],
        'is_active' => true,
        'show_on_mobile' => true,
        'show_on_desktop' => true,
        'order' => 0,
    ]
);

// Update untuk menggunakan placeholder langsung di render
$videoOverlay->code = <<<HTML
<div class="flex flex-col items-center gap-4">
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl p-6 text-center max-w-2xl">
        <h3 class="text-2xl font-bold text-white mb-2">🎬 Sponsor Video</h3>
        <p class="text-white/80 mb-4">Terima kasih sudah menonton! Iklan ini membantu kami menyediakan konten gratis.</p>
        <div class="bg-white/20 rounded-lg p-4">
            <p class="text-white text-sm">Slot Iklan Video Overlay</p>
            <p class="text-white/60 text-xs mt-1">Kode AdSense akan tampil di sini</p>
        </div>
    </div>
</div>
HTML;
$videoOverlay->type = 'html';
$videoOverlay->save();

echo "✅ Video Overlay Ad: {$videoOverlay->name}\n";

// Video Before Ad
$videoBefore = Advertisement::updateOrCreate(
    ['name' => 'Test Video Before Ad'],
    [
        'position' => 'video_before',
        'type' => 'html',
        'code' => <<<HTML
<div class="max-w-7xl mx-auto px-4 py-2">
    <div class="bg-gradient-to-r from-blue-600/20 to-purple-600/20 border border-blue-500/30 rounded-lg p-3 text-center">
        <p class="text-gray-300 text-sm">📺 Slot Iklan Sebelum Video - 728x90</p>
    </div>
</div>
HTML,
        'size' => '728x90',
        'pages' => ['watch'],
        'is_active' => true,
        'show_on_mobile' => true,
        'show_on_desktop' => true,
        'order' => 0,
    ]
);
echo "✅ Video Before Ad: {$videoBefore->name}\n";

// Video After Ad
$videoAfter = Advertisement::updateOrCreate(
    ['name' => 'Test Video After Ad'],
    [
        'position' => 'video_after',
        'type' => 'html',
        'code' => <<<HTML
<div class="bg-gradient-to-r from-green-600/20 to-teal-600/20 border border-green-500/30 rounded-lg p-3 text-center">
    <p class="text-gray-300 text-sm">📺 Slot Iklan Setelah Video - 728x90</p>
</div>
HTML,
        'size' => '728x90',
        'pages' => ['watch'],
        'is_active' => true,
        'show_on_mobile' => true,
        'show_on_desktop' => true,
        'order' => 0,
    ]
);
echo "✅ Video After Ad: {$videoAfter->name}\n";

echo "\n=== Semua Video Ads Berhasil Dibuat ===\n";
echo "Total: 3 iklan video\n";
echo "\nPosisi iklan:\n";
echo "1. video_overlay - Muncul sebagai overlay dengan timer 5 detik\n";
echo "2. video_before - Muncul di atas video player\n";
echo "3. video_after - Muncul di bawah video player\n";
